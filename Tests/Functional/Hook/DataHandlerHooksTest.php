<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Hook;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Testbase;

class DataHandlerHooksTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    private const TABLE_PROFILE = 'tx_academicpersons_domain_model_profile';
    private const TABLE_REFERENCE = 'sys_file_reference';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private DataHandler $dataHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/BeUsers.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PageTree.csv');

        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    #[Test]
    public function insertingNewRecordWillGenerateAndSaveAlphaFieldValues(): void
    {
        $dataMap = [
            'tx_academicpersons_domain_model_profile' => [
                'NEW1690182935' => [
                    'pid' => 2,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ],
        ];

        $this->dataHandler->start($dataMap, []);
        $this->dataHandler->process_datamap();

        $records = $this->getAllRecords('tx_academicpersons_domain_model_profile', true);

        $this->assertCount(1, $records);
        $this->assertSame('j', $records[1]['first_name_alpha']);
        $this->assertSame('d', $records[1]['last_name_alpha']);
    }

    #[Test]
    public function updatingRecordWillGenerateAndSaveNewAlphaFieldValues(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/MinimumProfile.csv');

        $dataMap = [
            'tx_academicpersons_domain_model_profile' => [
                '1' => [
                    'pid' => 2,
                    'first_name' => 'Johnny',
                    'last_name' => 'English',
                ],
            ],
        ];

        $this->dataHandler->start($dataMap, []);
        $this->dataHandler->process_datamap();

        $records = $this->getAllRecords('tx_academicpersons_domain_model_profile', true);

        $this->assertCount(1, $records);
        $this->assertSame('j', $records[1]['first_name_alpha']);
        $this->assertSame('e', $records[1]['last_name_alpha']);
    }

    /**
     * ACE-506: the reference of a profile created together with its image in one
     * datamap carries the profile name. The relation of a new record is wired by the
     * remap stack, and the DataHandler defers the `afterDatabaseOperations` hook of
     * such a record until the stack ran - this pins that the hook sees the reference.
     */
    #[Test]
    public function creatingAProfileWithAnImageWritesTheReferenceMetadata(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/IndexedImage.csv');
        $dataMap = [
            self::TABLE_PROFILE => [
                'NEW1' => [
                    'pid' => 2,
                    'title' => 'Dr.',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'image' => 'NEW2',
                ],
            ],
            self::TABLE_REFERENCE => [
                'NEW2' => [
                    'pid' => 2,
                    'uid_local' => 1,
                    'tablenames' => self::TABLE_PROFILE,
                    'fieldname' => 'image',
                ],
            ],
        ];

        $this->dataHandler->start($dataMap, []);
        $this->dataHandler->process_datamap();

        $this->assertSame([], $this->dataHandler->errorLog);
        $profileUid = (int)$this->dataHandler->substNEWwithIDs['NEW1'];
        $this->assertSame(
            [['uid_foreign' => $profileUid, 'title' => 'Dr. John Doe', 'alternative' => 'Dr. John Doe']],
            $this->fetchReferenceMetadata(),
        );
    }

    #[Test]
    public function updatingANameColumnRewritesTheReferenceMetadata(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/IndexedImage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/MinimumProfile.csv');
        $this->insertImageReference(uid: 1, profileUid: 1);
        $dataMap = [
            self::TABLE_PROFILE => [
                '1' => ['first_name' => 'Johnny'],
            ],
        ];

        $this->dataHandler->start($dataMap, []);
        $this->dataHandler->process_datamap();

        $this->assertSame(
            [['uid_foreign' => 1, 'title' => 'Johnny Duck', 'alternative' => 'Johnny Duck']],
            $this->fetchReferenceMetadata(),
        );
    }

    #[Test]
    public function updatingAnotherColumnLeavesTheReferenceMetadataAlone(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/IndexedImage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/MinimumProfile.csv');
        $this->insertImageReference(uid: 1, profileUid: 1);
        $dataMap = [
            self::TABLE_PROFILE => [
                '1' => ['website' => 'https://example.com/'],
            ],
        ];

        $this->dataHandler->start($dataMap, []);
        $this->dataHandler->process_datamap();

        $this->assertSame(
            [['uid_foreign' => 1, 'title' => '', 'alternative' => '']],
            $this->fetchReferenceMetadata(),
        );
    }

    /**
     * Localizing a profile localizes its reference too - the DataHandler copies the
     * reference and prefixes its `prefixLangTitle` columns - and the hook rewrites the
     * localized reference from the translation row, so the copied text never stays.
     */
    #[Test]
    public function localizingAProfileWritesTheMetadataOfTheLocalizedReference(): void
    {
        $this->writeSiteConfiguration(
            identifier: 'hook-test',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: '/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
        $this->importCSVDataSet(__DIR__ . '/Fixtures/IndexedImage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/MinimumProfile.csv');
        $this->insertImageReference(uid: 1, profileUid: 1);
        $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_REFERENCE)
            ->update(self::TABLE_REFERENCE, ['title' => 'Stale', 'alternative' => 'Stale'], ['uid' => 1]);

        $this->dataHandler->start([], [self::TABLE_PROFILE => [1 => ['localize' => 1]]]);
        $this->dataHandler->process_cmdmap();

        $this->assertSame([], $this->dataHandler->errorLog);
        $translationUid = (int)$this->dataHandler->copyMappingArray_merged[self::TABLE_PROFILE][1];
        $this->assertSame(
            [
                ['uid_foreign' => 1, 'title' => 'Stale', 'alternative' => 'Stale'],
                ['uid_foreign' => $translationUid, 'title' => 'Scrooge Duck', 'alternative' => 'Scrooge Duck'],
            ],
            $this->fetchReferenceMetadata(),
        );
    }

    private function insertImageReference(int $uid, int $profileUid): void
    {
        $this->insertRecord(self::TABLE_REFERENCE, [
            'uid' => $uid,
            'pid' => 2,
            'uid_local' => 1,
            'uid_foreign' => $profileUid,
            'tablenames' => self::TABLE_PROFILE,
            'fieldname' => 'image',
            'sorting_foreign' => 1,
        ]);
        $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_PROFILE)
            ->update(self::TABLE_PROFILE, ['image' => 1], ['uid' => $profileUid]);
    }

    /**
     * Inserts a row with an explicit uid and re-aligns the table's sequence. An
     * explicitly inserted uid does not advance the PostgreSQL sequence, so the next
     * row the DataHandler writes would collide with it - `importCSVDataSet()` does
     * the same reset, raw inserts do not.
     *
     * @param array<string, mixed> $row
     */
    private function insertRecord(string $tableName, array $row): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable($tableName);
        $connection->insert($tableName, $row);
        Testbase::resetTableSequences($connection, $tableName);
    }

    /**
     * @return list<array{uid_foreign: int, title: string, alternative: string}>
     */
    private function fetchReferenceMetadata(): array
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_REFERENCE)
            ->executeQuery('SELECT uid_foreign, title, alternative FROM sys_file_reference WHERE deleted = 0 ORDER BY uid')
            ->fetchAllAssociative();
        return array_map(
            static fn(array $row): array => [
                'uid_foreign' => (int)$row['uid_foreign'],
                'title' => (string)$row['title'],
                'alternative' => (string)$row['alternative'],
            ],
            $rows,
        );
    }
}
