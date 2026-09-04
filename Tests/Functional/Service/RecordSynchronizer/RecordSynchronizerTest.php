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
 * Tests for {@see \FGTCLB\AcademicPersons\Service\RecordSynchronizer} (ACE-105 / ACE-483).
 *
 * Since ACE-483 the synchroniser routes every write through the TYPO3 DataHandler. The
 * tests in this class started as characterisation pins of the previous raw-SQL
 * implementation; the ones that pinned its defects - dead inline recursion, missing MM
 * and file reference synchronisation, empty `l10n_diffsource` - were flipped into
 * positive assertions on the same fixtures when the rework landed. The no-op guards and
 * the language filtering pin the unchanged interface contract.
 *
 * The service is exercised directly through its public interface with a
 * {@see SynchronizerContext} built the same way the production listener
 * `SyncChangesToTranslations` builds it. Workspace behaviour (ACE-480) is covered in
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
     * Flipped defect pin (ACE-483): the DataHandler `localize` command carries the full
     * inline child tree. Both contracts are translated and re-pointed to the translated
     * profile through their `profile` foreign field, and each contract's address, email
     * and phone number children follow the same way, re-pointed to the translated
     * contract. The previous raw-SQL implementation translated the profile row alone -
     * its inline recursion was dead code since the ACE-104 extraction.
     */
    #[Test]
    public function synchronizeRecursesIntoInlineChildrenOnCreate(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithContractsAndChildren.csv');

        $this->synchronizeProfile(1);

        $this->assertCount(2, $this->fetchAllRecords(self::TABLE_PROFILE));
        $translatedProfile = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translatedProfile);
        foreach ([1, 2] as $contractUid) {
            $translatedContract = $this->fetchTranslation(self::TABLE_CONTRACT, $contractUid);
            $this->assertNotNull($translatedContract, 'Missing translation of contract ' . $contractUid);
            $this->assertSame(
                (int)$translatedProfile['uid'],
                (int)$translatedContract['profile'],
                'Translated contract ' . $contractUid . ' is not wired to the translated profile.',
            );
            foreach ([self::TABLE_ADDRESS, self::TABLE_EMAIL, self::TABLE_PHONE] as $childTable) {
                $translatedChild = $this->fetchTranslation($childTable, $contractUid);
                $this->assertNotNull($translatedChild, 'Missing translation in ' . $childTable . ' below contract ' . $contractUid);
                $this->assertSame(
                    (int)$translatedContract['uid'],
                    (int)$translatedChild['contract'],
                    'Translated child in ' . $childTable . ' is not wired to the translated contract ' . $contractUid . '.',
                );
            }
        }
        foreach ([self::TABLE_CONTRACT, self::TABLE_ADDRESS, self::TABLE_EMAIL, self::TABLE_PHONE] as $childTable) {
            $this->assertCount(4, $this->fetchAllRecords($childTable), 'Unexpected record count in ' . $childTable);
        }
    }

    /**
     * Flipped defect pin (ACE-483): a translation created through the DataHandler
     * carries the serialized default-language state in `l10n_diffsource`, which is what
     * makes the backend diff view work. The raw-SQL implementation left it empty.
     */
    #[Test]
    public function synchronizePopulatesDiffsourceOfCreatedTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MinimalProfile.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $diffSource = (string)($translation['l10n_diffsource'] ?? '');
        $this->assertNotSame('', $diffSource);
        $this->assertStringContainsString('"first_name":"Erika"', $diffSource);
    }

    /**
     * A freshly localized record starts out as a copy of the default language record:
     * DataHandler `localize` copies translatable columns such as `title` verbatim
     * (no `prefixLangTitle` field is configured on the table). The update path never
     * touches them again, so the translator's text survives later synchronisations -
     * see {@see self::synchronizeLeavesTranslatableColumnsOfExistingTranslationUntouched()}.
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
     * The update path re-submits the default record's `l10n_mode=exclude` columns
     * (here: `first_name`, `last_name`, `website`) as a datamap, and core's
     * DataMapProcessor propagates them into the existing translation. Because the write
     * goes through the DataHandler it leaves a trace: the translation's `tstamp` is
     * bumped past the fixture value 1000 (flipped ACE-483 pin - the raw-SQL
     * implementation changed the row without touching `tstamp`).
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
        $this->assertGreaterThan(1000, (int)$translation['tstamp']);
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
     * Flipped defect pin (ACE-483): a contract added to the default profile AFTER the
     * translation was created is carried over by the `inlineLocalizeSynchronize`
     * command (action `synchronize`) the update path issues per inline column. The
     * exclude-column update on the profile row itself happens as well, which the
     * first_name assertion proves.
     */
    #[Test]
    public function synchronizeTranslatesContractAddedAfterTranslationExisted(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithTranslationAndNewContract.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame('Erika', $translation['first_name']);
        $this->assertCount(2, $this->fetchAllRecords(self::TABLE_CONTRACT));
        $translatedContract = $this->fetchTranslation(self::TABLE_CONTRACT, 1);
        $this->assertNotNull($translatedContract);
        $this->assertSame(
            (int)$translation['uid'],
            (int)$translatedContract['profile'],
            'The late contract translation is not wired to the translated profile.',
        );
    }

    /**
     * ACE-487: exclude columns of already-translated CHILD records are re-propagated by
     * the update path - the datamap covers the whole default-language inline tree, not
     * just the profile row. The fixture holds stale values on both levels below the
     * profile: the contract translation carries a stale `valid_from` against the
     * default's value, and the address translation (a grandchild) carries type
     * `private` against the default's `business`. Translatable columns of the same
     * children stay untouched - the translated contract position survives.
     *
     * The fixture timestamps are midnight values on purpose: `valid_from` is
     * `type=datetime, format=date`, and since TYPO3 v14 (#105549) the DataHandler
     * normalizes such a value through `DateTimeImmutable` - a non-midnight timestamp
     * would be rewritten, on v14 only, and the assertion would compare apples to
     * normalized oranges.
     */
    #[Test]
    public function synchronizeUpdatesExcludeColumnsOfExistingChildTranslations(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithStaleChildTranslations.csv');

        $this->synchronizeProfile(1);

        $translatedContract = $this->fetchTranslation(self::TABLE_CONTRACT, 1);
        $this->assertNotNull($translatedContract);
        $this->assertSame(1700006400, (int)$translatedContract['valid_from']);
        $this->assertSame('Professorin', $translatedContract['position']);
        $translatedAddress = $this->fetchTranslation(self::TABLE_ADDRESS, 1);
        $this->assertNotNull($translatedAddress);
        $this->assertSame('business', $translatedAddress['type']);
    }

    /**
     * ACE-506: the profile image is an `allowLanguageSynchronization` column. A
     * translation without an `l10n_state` for it is in the `parent` state, so an
     * image added to the default record AFTER the translation exists is carried over
     * by the update path - `DataMapProcessor` synchronizes the parent-state columns of
     * every translation the datamap reaches, from the database row, exactly as it
     * does the exclude columns (the ACE-487 pin of the latter lives in
     * {@see RecordSynchronizerExcludeFileColumnTest} now).
     */
    #[Test]
    public function synchronizeCarriesLateFileReferenceIntoExistingTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithTranslationAndLateRelations.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame(1, (int)$translation['image']);
        $referenceRows = $this->fetchAllRecords('sys_file_reference');
        $this->assertCount(2, $referenceRows);
        $translatedReference = $referenceRows[1];
        $this->assertSame(1, (int)$translatedReference['sys_language_uid']);
        $this->assertSame(1, (int)$translatedReference['l10n_parent']);
        $this->assertSame(1, (int)$translatedReference['uid_local']);
        $this->assertSame((int)$translation['uid'], (int)$translatedReference['uid_foreign']);
    }

    /**
     * The other direction of the same state, and its limit. The default record lost
     * its image *outside the DataHandler* - the reference row is soft-deleted, the
     * counter is 0, the way an Extbase `remove()` leaves it - while the translation
     * still holds the localized reference. The update path resets the translation's
     * counter, but the localized row stays attached: core's `synchronizeReferences()`
     * returns before its `$removeIds` when the default record has no children left.
     * Removal is complete only when the deletion itself goes through the DataHandler,
     * whose delete command cascades into the localizations
     * (`deleteL10nOverlayRecords()`) - which is what `ProfileImageRelationWriter`
     * relies on, pinned in `ProfileImageRelationWriterTest`.
     */
    #[Test]
    public function synchronizeResetsTheImageCounterOfAFollowingTranslationWhenTheDefaultLostIt(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithTranslationAndRemovedImage.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame(0, (int)$translation['image']);
        $referenceRows = $this->fetchAllRecords('sys_file_reference');
        $this->assertCount(2, $referenceRows);
        $this->assertSame(1, (int)$referenceRows[0]['deleted']);
        $this->assertSame(0, (int)$referenceRows[1]['deleted']);
    }

    /**
     * A translation whose image is in the `custom` localization state keeps it: the
     * default record's late image is not carried over, and the translation's own
     * counter and reference rows stay as they are. This is the state the profile
     * editing writes when a translation gets an image of its own (ACE-506).
     */
    #[Test]
    public function synchronizeKeepsCustomProfileImageIndependent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithTranslationAndLateRelations.csv');
        $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_PROFILE)
            ->update(
                self::TABLE_PROFILE,
                ['l10n_state' => json_encode(['image' => 'custom'], JSON_THROW_ON_ERROR)],
                ['uid' => 2],
            );

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame(0, (int)$translation['image']);
        $referenceRows = $this->fetchAllRecords('sys_file_reference');
        $this->assertCount(1, $referenceRows);
        $this->assertSame(0, (int)$referenceRows[0]['sys_language_uid']);
        $this->assertSame(1, (int)$referenceRows[0]['uid_local']);
        $this->assertSame(1, (int)$referenceRows[0]['uid_foreign']);
    }

    /**
     * The MM counterpart of the pin above: an MM relation added to the default record
     * after its translation exists reaches the translation as an own MM row.
     */
    #[Test]
    public function synchronizeCarriesLateMmRelationIntoExistingTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithTranslationAndLateRelations.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame(1, (int)$translation['frontend_users']);
        $mmRows = $this->fetchAllRecords('tx_academicpersons_feuser_mm', 'uid_local');
        $this->assertCount(2, $mmRows);
        $this->assertSame((int)$translation['uid'], (int)$mmRows[1]['uid_local']);
        $this->assertSame(10, (int)$mmRows[1]['uid_foreign']);
    }

    /**
     * Flipped defect pin (ACE-483): MM relations are synchronised by the `localize`
     * command. The translation gets its own `tx_academicpersons_feuser_mm` row pointing
     * at the same frontend user, so its `frontend_users` counter of 1 is backed by a
     * real relation.
     */
    #[Test]
    public function synchronizeSynchronizesMmRelations(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithRelations.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame(1, (int)$translation['frontend_users']);
        $mmRows = $this->fetchAllRecords('tx_academicpersons_feuser_mm', 'uid_local');
        $this->assertCount(2, $mmRows);
        $this->assertSame(1, (int)$mmRows[0]['uid_local']);
        $this->assertSame((int)$translation['uid'], (int)$mmRows[1]['uid_local']);
        $this->assertSame(10, (int)$mmRows[1]['uid_foreign']);
    }

    /**
     * Flipped defect pin (ACE-483): file references are synchronised by the `localize`
     * command. A localized `sys_file_reference` row pointing at the same file and at
     * the translated profile is created, so the translation's `image` counter of 1
     * resolves to a real image.
     */
    #[Test]
    public function synchronizeSynchronizesFileReferences(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileWithRelations.csv');

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame(1, (int)$translation['image']);
        $referenceRows = $this->fetchAllRecords('sys_file_reference');
        $this->assertCount(2, $referenceRows);
        $this->assertSame(1, (int)$referenceRows[0]['uid_foreign']);
        $translatedReference = $referenceRows[1];
        $this->assertSame(1, (int)$translatedReference['sys_language_uid']);
        $this->assertSame(1, (int)$translatedReference['l10n_parent']);
        $this->assertSame(1, (int)$translatedReference['uid_local']);
        $this->assertSame((int)$translation['uid'], (int)$translatedReference['uid_foreign']);
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
