<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\DataHandling;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * A contract is an inline child of two records at once: of its profile, which owns
 * the order the frontend renders, and of its organisational unit.
 *
 * Both relations declared `foreign_sortby = sorting` until the unit relation gained
 * `organisational_unit_sorting`, and
 * `RelationHandler::writeForeignField()` numbers the children of the saved parent
 * 1..n into that column. Saving an organisational unit therefore renumbered its
 * contracts across every profile that owns one of them.
 *
 * The fixture is built so the assertions fail on every DBMS when the sort column is
 * shared: the unit lists the contracts of two profiles in an order that contradicts
 * the order of both profiles.
 */
final class ContractSortingPerParentTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE_UNIT = 'tx_academicpersons_domain_model_organisational_unit';
    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        parent::tearDown();
    }

    /**
     * Saving the organisational unit in an order that contradicts both profiles
     * leaves the order of each profile untouched.
     */
    #[Test]
    public function savingAnOrganisationalUnitKeepsTheContractOrderOfEveryProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');

        $this->saveInlineChildren(self::TABLE_UNIT, 1, 'contracts', [2, 4, 1, 3]);

        $this->assertSame(
            [1, 2],
            $this->fetchChildUids('profile', 1, 'sorting'),
            'Saving the organisational unit rearranged the contracts of profile 1.',
        );
        $this->assertSame(
            [3, 4],
            $this->fetchChildUids('profile', 2, 'sorting'),
            'Saving the organisational unit rearranged the contracts of profile 2.',
        );
    }

    /**
     * The organisational unit keeps the arrangement it was saved with, in a sort
     * column of its own.
     */
    #[Test]
    public function savingAnOrganisationalUnitStoresItsOwnArrangement(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');

        $this->saveInlineChildren(self::TABLE_UNIT, 1, 'contracts', [2, 4, 1, 3]);

        $this->assertSame(
            [2, 4, 1, 3],
            $this->fetchChildUids('organisational_unit', 1, 'organisational_unit_sorting'),
            'The organisational unit did not keep the order it was saved with.',
        );
    }

    /**
     * Saving a profile still writes the shared `sorting` column - the order the
     * frontend renders - and leaves the arrangement of the unit alone.
     */
    #[Test]
    public function savingAProfileKeepsTheArrangementOfTheOrganisationalUnit(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');
        $this->saveInlineChildren(self::TABLE_UNIT, 1, 'contracts', [2, 4, 1, 3]);

        $this->saveInlineChildren('tx_academicpersons_domain_model_profile', 1, 'contracts', [2, 1]);

        $this->assertSame(
            [2, 1],
            $this->fetchChildUids('profile', 1, 'sorting'),
            'Saving the profile did not rearrange its own contracts.',
        );
        $this->assertSame(
            [2, 4, 1, 3],
            $this->fetchChildUids('organisational_unit', 1, 'organisational_unit_sorting'),
            'Saving the profile rearranged the contracts of the organisational unit.',
        );
    }

    /**
     * The path editors actually use: a contract is created in the inline list of its
     * profile and picks its organisational unit in the `organisational_unit` select
     * of its own form. The unit is not saved, so nothing renumbers its list - the new
     * contract has to be appended to it, not left at 0 above everything else.
     */
    #[Test]
    public function aContractCreatedOnTheProfileIsAppendedToItsOrganisationalUnit(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');
        $this->saveInlineChildren(self::TABLE_UNIT, 1, 'contracts', [2, 4, 1, 3]);

        $newUid = $this->createContractOnProfile(1, 1);

        $this->assertSame(
            [2, 4, 1, 3, $newUid],
            $this->fetchChildUids('organisational_unit', 1, 'organisational_unit_sorting'),
            'The new contract was not appended to the list of its organisational unit.',
        );
    }

    /**
     * Two of them in a row land behind each other rather than tying: the data map is
     * processed record by record, so the second one sees the first one stored. The
     * ranks themselves are asserted, not the resulting order - two records tied at 0
     * come back in uid order on SQLite and would hide the defect.
     */
    #[Test]
    public function twoContractsCreatedInARowDoNotTie(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');
        $this->saveInlineChildren(self::TABLE_UNIT, 1, 'contracts', [2, 4, 1, 3]);

        $firstUid = $this->createContractOnProfile(1, 1);
        $secondUid = $this->createContractOnProfile(2, 1);

        $this->assertSame(5, $this->fetchColumn(self::TABLE_CONTRACT, $firstUid, 'organisational_unit_sorting'));
        $this->assertSame(6, $this->fetchColumn(self::TABLE_CONTRACT, $secondUid, 'organisational_unit_sorting'));
    }

    /**
     * A contract that changes its organisational unit is appended to the new one: the
     * rank it had in the old unit says nothing about where it belongs in the new list.
     */
    #[Test]
    public function aContractThatChangesItsUnitIsAppendedToTheNewOne(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');
        $this->saveInlineChildren(self::TABLE_UNIT, 1, 'contracts', [2, 4, 1, 3]);
        $this->updateRecord(self::TABLE_CONTRACT, 3, ['organisational_unit' => 2]);

        $this->updateRecord(self::TABLE_CONTRACT, 1, ['organisational_unit' => 2]);

        $this->assertSame([2, 4], $this->fetchChildUids('organisational_unit', 1, 'organisational_unit_sorting'));
        $this->assertSame([3, 1], $this->fetchChildUids('organisational_unit', 2, 'organisational_unit_sorting'));
    }

    /**
     * Saving a contract without touching its organisational unit leaves its rank
     * alone - that is what makes an arrangement made in the unit form survive. It is
     * the guard against the hook being too eager, so unlike its siblings it passes
     * without the hook as well.
     */
    #[Test]
    public function savingAContractKeepsItsRankInTheUnit(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');
        $this->saveInlineChildren(self::TABLE_UNIT, 1, 'contracts', [2, 4, 1, 3]);

        $this->updateRecord(self::TABLE_CONTRACT, 1, ['position' => 'Emeritus']);

        $this->assertSame([2, 4, 1, 3], $this->fetchChildUids('organisational_unit', 1, 'organisational_unit_sorting'));
    }

    /**
     * Clearing the organisational unit resets the column: a rank in a list the record
     * left would be restored together with the unit by some later, unrelated save.
     */
    #[Test]
    public function clearingTheUnitResetsTheRank(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');
        $this->saveInlineChildren(self::TABLE_UNIT, 1, 'contracts', [2, 4, 1, 3]);

        $this->updateRecord(self::TABLE_CONTRACT, 1, ['organisational_unit' => 0]);

        $this->assertSame(0, $this->fetchColumn(self::TABLE_CONTRACT, 1, 'organisational_unit_sorting'));
        $this->assertSame([2, 4, 3], $this->fetchChildUids('organisational_unit', 1, 'organisational_unit_sorting'));
    }

    /**
     * A `copy` command on the contract itself runs `copyRecord()`, which submits a
     * nested data map - so this one is ranked by the data map half. The path the
     * cmdmap half exists for is the cascaded one, below.
     */
    #[Test]
    public function aCopiedContractIsAppendedToItsUnit(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');
        $this->saveInlineChildren(self::TABLE_UNIT, 1, 'contracts', [2, 4, 1, 3]);

        $copyUid = $this->copyRecord(self::TABLE_CONTRACT, 1, 100);

        $this->assertSame(
            [2, 4, 1, 3, $copyUid],
            $this->fetchChildUids('organisational_unit', 1, 'organisational_unit_sorting'),
        );
    }

    /**
     * Creates a contract the way the profile form does: the record carries the unit in
     * its own `organisational_unit` select, and the profile relation lists it.
     */
    private function createContractOnProfile(int $profileUid, int $unitUid): int
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $newId = StringUtility::getUniqueId('NEW');
        $existingUids = $this->fetchChildUids('profile', $profileUid, 'sorting');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                self::TABLE_CONTRACT => [
                    $newId => [
                        'pid' => 100,
                        'profile' => $profileUid,
                        'organisational_unit' => $unitUid,
                        'position' => 'Assistant',
                    ],
                ],
                'tx_academicpersons_domain_model_profile' => [
                    $profileUid => ['contracts' => implode(',', [...$existingUids, $newId])],
                ],
            ],
            [],
            $backendUser,
        );
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        return (int)$dataHandler->substNEWwithIDs[$newId];
    }

    /**
     * @param array<string, int|string> $values
     */
    private function updateRecord(string $tableName, int $uid, array $values): void
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([$tableName => [$uid => $values]], [], $backendUser);
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    private function copyRecord(string $tableName, int $uid, int $targetPageUid): int
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$tableName => [$uid => ['copy' => $targetPageUid]]], $backendUser);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        return (int)($dataHandler->copyMappingArray_merged[$tableName][$uid] ?? 0);
    }

    private function fetchColumn(string $tableName, int $uid, string $columnName): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->select($columnName)
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * The window between the database analyzer and the upgrade wizard: the column
     * exists, every contract still sits at 0, and the wizard has not run. A save
     * must not take position 1 there - the wizard would read it as an arrangement
     * and append the rest of the unit behind it, which reverses the order it exists
     * to preserve.
     */
    #[Test]
    public function savingAContractOfAnUnseededUnitLeavesItUnranked(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');

        $this->updateRecord(self::TABLE_CONTRACT, 2, ['position' => 'Emeritus']);

        $this->assertSame(
            0,
            $this->fetchColumn(self::TABLE_CONTRACT, 2, 'organisational_unit_sorting'),
            'The contract took a position in a unit the upgrade wizard has not seeded yet.',
        );
    }

    /**
     * The first contract of a unit is a different matter: there is no list for the
     * wizard to have an opinion about, so it starts one.
     */
    #[Test]
    public function theFirstContractOfAUnitTakesTheFirstPosition(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');

        $newUid = $this->createContractOnProfile(1, 2);

        $this->assertSame(1, $this->fetchColumn(self::TABLE_CONTRACT, $newUid, 'organisational_unit_sorting'));
    }

    /**
     * Copying the **owning** parent is the path the cmdmap half of the hook exists
     * for, and the one a direct copy does not exercise: a cascaded inline child is
     * created by `copyRecord_raw()` -> `insertDB()` with the full database row, no
     * data map involved and nothing that drops a column without a TCA `columns`
     * entry - so the copy arrives carrying the position of the record it was copied
     * from, and ties with it.
     */
    #[Test]
    public function copyingAProfileAppendsTheCopiedContractsToTheirUnit(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnit.csv');
        $this->saveInlineChildren(self::TABLE_UNIT, 1, 'contracts', [2, 4, 1, 3]);

        $copies = $this->copyRecordWithMapping('tx_academicpersons_domain_model_profile', 1, 100, self::TABLE_CONTRACT);

        $this->assertCount(2, $copies, 'Expected both contracts of the profile to be copied.');
        $ranks = $this->fetchRanks(self::TABLE_CONTRACT, 'organisational_unit', 1, 'organisational_unit_sorting');
        $this->assertSame(
            array_values(array_unique($ranks)),
            array_values($ranks),
            'Two contracts of the organisational unit share a position: ' . json_encode($ranks),
        );
        // The copies are appended in the order their originals hold in the unit, not
        // in the order the copy run happened to create them: contract 2 sits before
        // contract 1 there, so its copy does too.
        $this->assertSame(
            [2, 4, 1, 3, $copies[2], $copies[1]],
            $this->fetchChildUids('organisational_unit', 1, 'organisational_unit_sorting'),
        );
    }

    /**
     * @return array<int, int> The records of $childTable the run created, source uid => new uid.
     */
    private function copyRecordWithMapping(string $tableName, int $uid, int $targetPageUid, string $childTable): array
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        // Copying reaches DataHandler::getLanguageService(), which is typed against
        // $GLOBALS['LANG'].
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$tableName => [$uid => ['copy' => $targetPageUid]]], $backendUser);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        $mapping = [];
        foreach ($dataHandler->copyMappingArray_merged[$childTable] ?? [] as $sourceUid => $newUid) {
            $mapping[(int)$sourceUid] = (int)$newUid;
        }
        return $mapping;
    }

    /**
     * @return array<int, int> uid => rank, in the order the sort column puts them in.
     */
    private function fetchRanks(string $tableName, string $parentField, int $parentUid, string $sortField): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', $sortField)
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentField,
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT),
                ),
            )
            ->orderBy($sortField)
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $ranks = [];
        foreach ($rows as $row) {
            $ranks[(int)$row['uid']] = (int)$row[$sortField];
        }
        return $ranks;
    }

    /**
     * @param list<int> $childUids
     */
    private function saveInlineChildren(string $tableName, int $uid, string $fieldName, array $childUids): void
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [$tableName => [$uid => [$fieldName => implode(',', $childUids)]]],
            [],
            $backendUser,
        );
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    /**
     * The uids of the contracts of one parent, in the order the given sort column
     * puts them in - `uid` settling ties, the way the inline relation reads them.
     *
     * @return list<int>
     */
    private function fetchChildUids(string $parentField, int $parentUid, string $sortField): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTRACT);
        $queryBuilder->getRestrictions()->removeAll();
        $uids = $queryBuilder
            ->select('uid')
            ->from(self::TABLE_CONTRACT)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentField,
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT),
                ),
            )
            ->orderBy($sortField)
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();
        return array_map(intval(...), $uids);
    }
}
