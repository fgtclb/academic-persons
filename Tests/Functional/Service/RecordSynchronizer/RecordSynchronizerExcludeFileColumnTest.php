<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Service\RecordSynchronizer;

use FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext;
use FGTCLB\AcademicPersons\Service\RecordSynchronizerInterface;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The ACE-487 pin for `l10n_mode=exclude` file columns, kept on a fixture column
 * since the profile image stopped being one (ACE-506): a file reference added to the
 * default record AFTER its translation exists IS carried over by the update path -
 * core's `DataMapProcessor` synchronizes all exclude columns of a record the datamap
 * touches from its database row, the relational ones included. This was recorded as
 * a gap by the ACE-483 report; the probe disproved it, and this test keeps it true.
 *
 * Own class rather than a method of {@see RecordSynchronizerTest}: the fixture
 * extension `test_exclude_file_column` adds the column to the profile table for
 * every test of the class that loads it, so it is loaded here only.
 */
final class RecordSynchronizerExcludeFileColumnTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    private const TABLE_PROFILE = 'tx_academicpersons_domain_model_profile';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->addTestExtension('tests/test-exclude-file-column');
        parent::setUp();
        $this->writeSiteConfiguration(
            identifier: 'synchronizer-test',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: '/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    #[Test]
    public function synchronizeCarriesLateExcludeFileReferenceIntoExistingTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithTranslationAndLateExcludeFileReference.csv');

        $synchronizer = $this->get(RecordSynchronizerInterface::class);
        $synchronizer->synchronize(SynchronizerContext::create(
            recordSyncronizer: $synchronizer,
            site: $this->get(SiteFinder::class)->getSiteByIdentifier('synchronizer-test'),
            allowedLanguageIds: [1],
            tableName: self::TABLE_PROFILE,
            uid: 1,
        ));

        $translation = $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_PROFILE)
            ->select(['uid', 'tx_testexcludefilecolumn_document'], self::TABLE_PROFILE, ['l10n_parent' => 1, 'sys_language_uid' => 1])
            ->fetchAssociative();
        $this->assertIsArray($translation);
        $this->assertSame(1, (int)$translation['tx_testexcludefilecolumn_document']);
        $referenceRows = $this->getConnectionPool()
            ->getConnectionForTable('sys_file_reference')
            ->executeQuery('SELECT sys_language_uid, l10n_parent, uid_local, uid_foreign FROM sys_file_reference WHERE deleted = 0 ORDER BY uid')
            ->fetchAllAssociative();
        $this->assertCount(2, $referenceRows);
        $this->assertSame(1, (int)$referenceRows[1]['sys_language_uid']);
        $this->assertSame(1, (int)$referenceRows[1]['l10n_parent']);
        $this->assertSame(1, (int)$referenceRows[1]['uid_local']);
        $this->assertSame((int)$translation['uid'], (int)$referenceRows[1]['uid_foreign']);
    }
}
