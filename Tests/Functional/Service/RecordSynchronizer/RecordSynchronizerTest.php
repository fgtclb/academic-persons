<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service\RecordSynchronizer;

use FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext;
use FGTCLB\AcademicPersons\Service\RecordSynchronizerInterface;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Characterisation tests for {@see \FGTCLB\AcademicPersons\Service\RecordSynchronizer} (ACE-105).
 *
 * These tests pin the CURRENT behaviour of the synchroniser, defects included, so the
 * rework of its internals (ACE-483) has a precise before-picture. A test whose PHPDoc
 * names a defect asserts the wrong-but-current behaviour on purpose and is expected to
 * be inverted by the issue it names - it is a tripwire, not an endorsement.
 *
 * The service is exercised directly through its public interface with a
 * {@see SynchronizerContext} built the same way the production listener
 * `SyncChangesToTranslations` builds it. Workspace behaviour (ACE-480) is pinned in
 * {@see RecordSynchronizerWorkspaceTest}.
 */
final class RecordSynchronizerTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    private const TABLE_PROFILE = 'tx_academicpersons_domain_model_profile';
    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';
    private const TABLE_ADDRESS = 'tx_academicpersons_domain_model_address';
    private const TABLE_EMAIL = 'tx_academicpersons_domain_model_email';
    private const TABLE_PHONE = 'tx_academicpersons_domain_model_phone_number';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
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

    /**
     * The default-language profile is copied into a new language-1 row: language field,
     * translation pointer, translation source and pid are set, plain column values are
     * copied verbatim.
     */
    #[Test]
    public function synchronizeCreatesTranslatedProfileForAllowedLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithContractsAndChildren.csv');

        $this->synchronizeProfile(1);

        $this->assertCount(2, $this->fetchAllRecords(self::TABLE_PROFILE));
        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame(1, (int)$translation['sys_language_uid']);
        $this->assertSame(1, (int)$translation['l10n_parent']);
        $this->assertSame(1, (int)$translation['l10n_source']);
        $this->assertSame(100, (int)$translation['pid']);
        $this->assertSame('Erika', $translation['first_name']);
        $this->assertSame('Musterfrau', $translation['last_name']);
        $this->assertSame('erika-musterfrau', $translation['slug']);
    }

    /**
     * Defect pin (ACE-483): the create path does NOT recurse into inline children,
     * although all the machinery for it exists in the class. The column-type guard in
     * `synchronizeRecord()` reads `$columnDefinition['type'] ?? 'unknown'` - but TCA
     * columns carry their type at `['config']['type']`, so the type is always
     * `unknown`, the guard `continue`s on every column and the recursion block below
     * it is dead code. Neither contract is translated, nor any address, email or
     * phone number - the translated profile is created alone.
     *
     * Note this CONTRADICTS the inline-filter reading in the ACE-480 analysis
     * (section 3, item 4), which assumed the guard recurses into every inline column:
     * the misread key sits one line above the misdirected `sys_file_reference`
     * comparison the `@todo` in the class talks about.
     */
    #[Test]
    public function synchronizeDoesNotRecurseIntoInlineChildrenOnCreate(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithContractsAndChildren.csv');

        $this->synchronizeProfile(1);

        $this->assertCount(2, $this->fetchAllRecords(self::TABLE_PROFILE));
        foreach ([self::TABLE_CONTRACT, self::TABLE_ADDRESS, self::TABLE_EMAIL, self::TABLE_PHONE] as $childTable) {
            $records = $this->fetchAllRecords($childTable);
            $this->assertCount(2, $records, 'Unexpected record count in ' . $childTable);
            foreach ($records as $record) {
                $this->assertSame(
                    0,
                    (int)$record['sys_language_uid'],
                    'Unexpected translated record in ' . $childTable,
                );
            }
        }
    }

    /**
     * Defect pin (ACE-483): `createTranslation()` excludes `l10n_diffsource` from the
     * insert, so the translation carries no diff source at all. A translation created
     * through the DataHandler would carry the serialized default-language state, which
     * is what makes the backend diff view work. The rework is expected to flip this.
     */
    #[Test]
    public function synchronizeLeavesDiffsourceOfCreatedTranslationEmpty(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MinimalProfile.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame('', (string)($translation['l10n_diffsource'] ?? ''));
    }

    /**
     * Defect pin (ACE-483): the create path copies EVERY non-inline column, not only
     * the `l10n_mode=exclude` ones - a translatable column such as `title` starts out
     * as a verbatim copy of the default language value. Not wrong per se for a fresh
     * translation, but asymmetric to the update path, which never touches it again.
     */
    #[Test]
    public function synchronizeCopiesTranslatableColumnsIntoCreatedTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithContractsAndChildren.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame('Prof. Dr.', $translation['title']);
    }

    /**
     * {@see SynchronizerContext::create()} drops language id 0, negative ids and ids
     * the site does not define (TYPO3 exception code 1522960188 is swallowed), so only
     * real target languages reach the synchroniser.
     */
    #[Test]
    public function contextFiltersLanguageIdsTheSiteDoesNotDefine(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MinimalProfile.csv');

        $synchronizer = $this->get(RecordSynchronizerInterface::class);
        $context = SynchronizerContext::create(
            recordSyncronizer: $synchronizer,
            site: $this->getTestSite(),
            allowedLanguageIds: [0, '1', -1, 99],
            tableName: self::TABLE_PROFILE,
            uid: 1,
        );
        $this->assertSame([1], $context->getAllowedLanguageIds());

        $synchronizer->synchronize($context);

        $records = $this->fetchAllRecords(self::TABLE_PROFILE);
        $this->assertCount(2, $records);
        $this->assertSame(1, (int)$records[1]['sys_language_uid']);
    }

    /**
     * A uid without a default-language record is a silent no-op.
     */
    #[Test]
    public function synchronizeIsANoOpForAMissingDefaultRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MinimalProfile.csv');

        $this->synchronizeProfile(4711);

        $this->assertCount(1, $this->fetchAllRecords(self::TABLE_PROFILE));
    }

    /**
     * Passing the uid of a translation record is equally a no-op: the default-record
     * lookup constrains on `sys_language_uid = 0`, so a language-1 uid matches nothing.
     */
    #[Test]
    public function synchronizeIsANoOpWhenTheUidOfATranslationIsPassed(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithTranslation.csv');

        $this->synchronizeProfile(2);

        $records = $this->fetchAllRecords(self::TABLE_PROFILE);
        $this->assertCount(2, $records);
        $this->assertSame('Stale-First', $records[1]['first_name']);
        $this->assertSame('Alter Titel', $records[1]['title']);
    }

    /**
     * The update path writes only non-inline `l10n_mode=exclude` columns (here:
     * `first_name`, `last_name`, `website`) into the existing translation. It does not
     * bump the translation's `tstamp` - the row changes without a trace, which the
     * assertion on the fixture value 1000 pins deliberately (ACE-483).
     */
    #[Test]
    public function synchronizeUpdatesExcludeColumnsOfExistingTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithTranslation.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame('Erika', $translation['first_name']);
        $this->assertSame('Musterfrau', $translation['last_name']);
        $this->assertSame('https://new.example.com/', $translation['website']);
        $this->assertSame(1000, (int)$translation['tstamp']);
    }

    /**
     * The counterpart: a translatable column (`title` carries no `l10n_mode`) is left
     * untouched by the update path - the translator's text survives.
     */
    #[Test]
    public function synchronizeLeavesTranslatableColumnsOfExistingTranslationUntouched(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithTranslation.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame('Alter Titel', $translation['title']);
    }

    /**
     * Defect pin (ACE-483): once a translation exists, `synchronizeRecord()` `continue`s
     * after `updateTranslation()` and never recurses. A contract added to the default
     * profile AFTER the translation was created is therefore never translated - the
     * update on the profile row itself still happens, which the first_name assertion
     * proves.
     */
    #[Test]
    public function synchronizeDoesNotRecurseIntoInlineChildrenOnUpdate(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithTranslationAndNewContract.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame('Erika', $translation['first_name']);
        $contracts = $this->fetchAllRecords(self::TABLE_CONTRACT);
        $this->assertCount(1, $contracts);
        $this->assertSame(0, (int)$contracts[0]['sys_language_uid']);
    }

    /**
     * Defect pin (ACE-483): MM relations are not synchronised. The `frontend_users`
     * counter column is copied verbatim into the translation, but no
     * `tx_academicpersons_feuser_mm` row is created for the new record - the
     * translation claims one related frontend user and has none.
     */
    #[Test]
    public function synchronizeDoesNotSynchronizeMmRelations(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithRelations.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame(1, (int)$translation['frontend_users']);
        $mmRows = $this->fetchAllRecords('tx_academicpersons_feuser_mm', 'uid_local');
        $this->assertCount(1, $mmRows);
        $this->assertSame(1, (int)$mmRows[0]['uid_local']);
    }

    /**
     * Defect pin (ACE-483): file references are not synchronised either. The `image`
     * counter column is copied verbatim, but no `sys_file_reference` row pointing at
     * the translation is created - the translated profile claims one image and
     * resolves none.
     */
    #[Test]
    public function synchronizeDoesNotSynchronizeFileReferences(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithRelations.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame(1, (int)$translation['image']);
        $referenceRows = $this->fetchAllRecords('sys_file_reference');
        $this->assertCount(1, $referenceRows);
        $this->assertSame(1, (int)$referenceRows[0]['uid_foreign']);
    }

    /**
     * @param array<int, string|int> $allowedLanguageIds
     */
    private function synchronizeProfile(int $uid, array $allowedLanguageIds = [1]): void
    {
        $synchronizer = $this->get(RecordSynchronizerInterface::class);
        $context = SynchronizerContext::create(
            recordSyncronizer: $synchronizer,
            site: $this->getTestSite(),
            allowedLanguageIds: $allowedLanguageIds,
            tableName: self::TABLE_PROFILE,
            uid: $uid,
        );
        $synchronizer->synchronize($context);
    }

    private function getTestSite(): Site
    {
        return $this->get(SiteFinder::class)->getSiteByIdentifier('synchronizer-test');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAllRecords(string $tableName, string $orderByField = 'uid'): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('*')
            ->from($tableName)
            ->orderBy($orderByField)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchTranslation(string $tableName, int $defaultUid, int $languageId = 1): ?array
    {
        foreach ($this->fetchAllRecords($tableName) as $record) {
            if ((int)$record['l10n_parent'] === $defaultUid && (int)$record['sys_language_uid'] === $languageId) {
                return $record;
            }
        }
        return null;
    }
}
