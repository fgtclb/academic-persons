<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\DataHandling;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A contract is workspace aware, and editing one in a workspace creates a version
 * of it. `DataHandler::process_datamap()` does that through a **nested**
 * DataHandler that runs a `version` command, which reaches
 * `versionizeRecord()` -> `copyRecord_raw()` -> `insertNewCopyVersion()`: the whole
 * database row is copied, `organisational_unit_sorting` included, and the pair is
 * registered in `copyMappingArray_merged` exactly the way a copy is.
 *
 * The cmdmap half of {@see \FGTCLB\AcademicPersons\Hook\ContractSortingHook} must
 * therefore tell a version from a copy. It would otherwise read "same parent, same
 * position as the record it came from" as a position inherited by a copy and move
 * the version to the end of the unit's list - from an edit that never touched the
 * unit, and permanently once the workspace is published.
 */
final class ContractSortingInWorkspaceTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE_UNIT = 'tx_academicpersons_domain_model_organisational_unit';
    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';
    private const FIXTURE = __DIR__ . '/Fixtures/ContractSortingPerParent/twoProfilesOneOrganisationalUnitWorkspace.csv';

    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
        'typo3/cms-workspaces',
    ];

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
        parent::tearDown();
    }

    /**
     * Editing a contract in a workspace leaves its position in the organisational
     * unit alone - the version carries the position of the record it versions.
     */
    #[Test]
    public function editingAContractInAWorkspaceKeepsItsPositionInTheUnit(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->saveInLiveWorkspace(self::TABLE_UNIT, 1, ['contracts' => '2,4,1,3']);
        $rankBefore = $this->fetchRank(1);
        $this->assertSame(3, $rankBefore, 'The fixture is not arranged as the assertion needs it.');

        $this->saveInWorkspace(self::TABLE_CONTRACT, 1, ['position' => 'Emeritus'], 1);

        $versionUid = $this->fetchVersionUid(1);
        $this->assertGreaterThan(0, $versionUid, 'Expected the edit to create a workspace version.');
        $this->assertSame(3, $this->fetchRank($versionUid), 'The workspace version was moved to the end of the unit.');
        $this->assertSame(3, $this->fetchRank(1), 'The live record was moved.');
    }

    /**
     * A genuine copy made inside a workspace is still appended: it is a new record
     * of the unit, not a version of one - `t3ver_oid` stays 0 and it carries the
     * NEW placeholder state.
     */
    #[Test]
    public function aContractCopiedInAWorkspaceIsStillAppendedToTheUnit(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->saveInLiveWorkspace(self::TABLE_UNIT, 1, ['contracts' => '2,4,1,3']);

        $copyUid = $this->copyInWorkspace(self::TABLE_CONTRACT, 1, 100, 1);

        $this->assertGreaterThan(0, $copyUid, 'Expected the copy to be created.');
        $this->assertSame(5, $this->fetchRank($copyUid), 'The copy was not appended to the unit.');
        $this->assertSame(3, $this->fetchRank(1), 'The record the copy was made from was moved.');
    }

    /**
     * @param array<string, int|string> $values
     */
    private function saveInLiveWorkspace(string $tableName, int $uid, array $values): void
    {
        $this->saveInWorkspace($tableName, $uid, $values, 0);
    }

    /**
     * @param array<string, int|string> $values
     */
    private function saveInWorkspace(string $tableName, int $uid, array $values, int $workspaceId): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = $workspaceId;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([$tableName => [$uid => $values]], [], $backendUser);
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    private function copyInWorkspace(string $tableName, int $uid, int $targetPageUid, int $workspaceId): int
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = $workspaceId;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$tableName => [$uid => ['copy' => $targetPageUid]]], $backendUser);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        return (int)($dataHandler->copyMappingArray_merged[$tableName][$uid] ?? 0);
    }

    private function fetchVersionUid(int $liveUid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTRACT);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->select('uid')
            ->from(self::TABLE_CONTRACT)
            ->where(
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter($liveUid, Connection::PARAM_INT)),
            )
            ->orderBy('uid')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }

    private function fetchRank(int $uid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTRACT);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->select('organisational_unit_sorting')
            ->from(self::TABLE_CONTRACT)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
