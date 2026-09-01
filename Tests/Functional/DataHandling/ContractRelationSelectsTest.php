<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\DataHandling;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The optional relation selects of a contract - `organisational_unit`, `function_type`
 * and `location` - and what the DataHandler persists for their "please select" item
 * (ACE-488, the branch `2` counterpart of ACE-489).
 *
 * The item value used to be `''` (respectively `null`). DataHandler's select evaluation
 * turned that into an empty string, which PostgreSQL rejects as integer input
 * (`SQLSTATE[22P02]`) while MySQL and MariaDB coerce it to `0` and SQLite stores it as
 * text. Saving a contract with one of the selects empty therefore failed on PostgreSQL
 * only - the default sqlite run never showed it.
 *
 * The test submits exactly the item value the TCA declares for "please select", i.e.
 * what FormEngine hands to the DataHandler, and asserts an integer `0` in every column.
 * With the fix reverted it is red on PostgreSQL (the exception) and on SQLite as well
 * (`''` stored as text is not the integer `0`).
 *
 * The columns deliberately stay nullable on this line - TYPO3 v12 Extbase persists a
 * detached relation as `NULL` (see `ContractFactoryTest` in `academic_persons_edit`),
 * so the `NOT NULL` shape of `main` is not available here. This test covers the
 * DataHandler write, which never produces `NULL`.
 */
final class ContractRelationSelectsTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';
    private const RELATION_COLUMNS = ['organisational_unit', 'function_type', 'location'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/BeUsers.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PageTree.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/MinimumProfile.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function contractWithEmptyRelationSelectsIsPersistedWithZeroInEveryRelationColumn(): void
    {
        $contractData = [
            'pid' => 2,
            'profile' => 1,
            'position' => 'Professor',
        ];
        foreach (self::RELATION_COLUMNS as $columnName) {
            $contractData[$columnName] = $this->pleaseSelectItemValue($columnName);
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([self::TABLE_CONTRACT => ['NEW1' => $contractData]], []);
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $rows = $this->getConnectionPool()->getConnectionForTable(self::TABLE_CONTRACT)
            ->select(['*'], self::TABLE_CONTRACT, ['profile' => 1])
            ->fetchAllAssociative();
        $this->assertCount(1, $rows);
        foreach (self::RELATION_COLUMNS as $columnName) {
            $this->assertSame(0, (int)$rows[0][$columnName], sprintf('Column "%s" is not 0.', $columnName));
            $this->assertNotSame('', $rows[0][$columnName], sprintf('Column "%s" holds an empty string.', $columnName));
            $this->assertNotNull($rows[0][$columnName], sprintf('Column "%s" is NULL.', $columnName));
        }
    }

    /**
     * A contract row that still holds `NULL` in the three columns - every row saved before
     * this change with an empty relation - is repaired when the DataHandler copies it: the
     * copied `NULL` is evaluated to the new field default `0` instead of the empty string
     * PostgreSQL rejects. `copy` and `localize` share that evaluation (`copyRecord()`), so
     * this covers the localisation of such a contract as well.
     */
    #[Test]
    public function copyingAContractWithNullRelationsPersistsZeroInEveryRelationColumn(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/contractWithNullRelations.csv');

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [self::TABLE_CONTRACT => [1 => ['copy' => 2]]]);
        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);
        $rows = $this->getConnectionPool()->getConnectionForTable(self::TABLE_CONTRACT)
            ->select(['*'], self::TABLE_CONTRACT, [], [], ['uid' => 'ASC'])
            ->fetchAllAssociative();
        $this->assertCount(2, $rows);
        $copiedRow = $rows[1];
        foreach (self::RELATION_COLUMNS as $columnName) {
            $this->assertSame(0, (int)$copiedRow[$columnName], sprintf('Copied column "%s" is not 0.', $columnName));
            $this->assertNotSame('', $copiedRow[$columnName], sprintf('Copied column "%s" holds an empty string.', $columnName));
            $this->assertNotNull($copiedRow[$columnName], sprintf('Copied column "%s" is NULL.', $columnName));
        }
    }

    /**
     * @return int|string|null The value FormEngine submits for the "please select" item.
     */
    private function pleaseSelectItemValue(string $columnName): int|string|null
    {
        $items = $GLOBALS['TCA'][self::TABLE_CONTRACT]['columns'][$columnName]['config']['items'] ?? [];
        $this->assertNotSame([], $items, sprintf('No items declared for "%s".', $columnName));
        return $items[0]['value'];
    }
}
