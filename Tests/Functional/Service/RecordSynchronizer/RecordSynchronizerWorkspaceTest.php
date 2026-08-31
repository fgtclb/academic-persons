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
 * Characterisation tests pinning the workspace behaviour of
 * {@see \FGTCLB\AcademicPersons\Service\RecordSynchronizer} (ACE-105 / ACE-480).
 *
 * EVERY assertion in this class describes a DEFECT: the synchroniser is not workspace
 * aware. Its interface offers no way to pass a workspace, its reads carry only a
 * `DeletedRestriction` - so workspace version rows and workspace-only rows are treated
 * as ordinary default-language rows - and `createTranslation()` strips the `t3ver_*`
 * columns from the insert, so every row it writes is a LIVE row. ACE-480 is expected
 * to invert these tests; they exist so that flip is visible and deliberate.
 *
 * The fixture holds a live profile with a workspace version of it, a live contract
 * with a workspace version of it, and a contract that exists only in workspace 1.
 */
final class RecordSynchronizerWorkspaceTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    private const TABLE_PROFILE = 'tx_academicpersons_domain_model_profile';
    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->addCoreExtension('typo3/cms-workspaces');
        parent::setUp();
        $this->writeSiteConfiguration(
            identifier: 'synchronizer-test',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: '/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceProfile.csv');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * Defect pin (ACE-480): although a workspace version of the profile exists, the
     * translation the synchroniser creates is a LIVE row (`t3ver_wsid = 0`,
     * `t3ver_state = 0`, no `t3ver_oid`) - there is no way to run the sync inside a
     * workspace, and an unpublished draft state leaks into the live site as soon as
     * anything triggers a sync.
     */
    #[Test]
    public function createdProfileTranslationIsALiveRow(): void
    {
        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame(0, (int)$translation['t3ver_wsid']);
        $this->assertSame(0, (int)$translation['t3ver_oid']);
        $this->assertSame(0, (int)$translation['t3ver_state']);
        $this->assertSame('Live', $translation['first_name']);
    }

    /**
     * No contract is translated at all - not the live one, not the workspace-only one
     * (uid 102, `t3ver_state = 1`), not the workspace version of the live one
     * (uid 103, `t3ver_oid = 1`). This is the dead inline recursion pinned in
     * {@see RecordSynchronizerTest::synchronizeDoesNotRecurseIntoInlineChildrenOnCreate()}
     * (ACE-483): the misread TCA type key stops the recursion before the
     * workspace-unaware child read (no `t3ver_wsid` constraint, ACE-480) could even
     * run. Once ACE-483 revives child synchronisation, the workspace rows in this
     * fixture become reachable and this pin has to be replaced by workspace-aware
     * expectations.
     */
    #[Test]
    public function contractRowsAreNotTranslatedRegardlessOfWorkspaceState(): void
    {
        $this->synchronizeProfile(1);

        $contracts = $this->fetchAllRecords(self::TABLE_CONTRACT);
        $this->assertCount(3, $contracts);
        foreach ($contracts as $contract) {
            $this->assertSame(0, (int)$contract['sys_language_uid']);
        }
    }

    /**
     * Defect pin (ACE-480): the default-record read constrains on uid and language
     * only, so the uid of a workspace VERSION row (101, `t3ver_oid = 1`) is accepted
     * as an ordinary default record - and its draft values are published as a live
     * translation whose `l10n_parent` points at the version row instead of a live
     * record.
     */
    #[Test]
    public function workspaceVersionUidIsAcceptedAsDefaultRecord(): void
    {
        $this->synchronizeProfile(101);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 101);
        $this->assertNotNull($translation);
        $this->assertSame('Draft', $translation['first_name']);
        $this->assertSame(0, (int)$translation['t3ver_wsid']);
        $this->assertSame(101, (int)$translation['l10n_parent']);
        $this->assertSame(101, (int)$translation['l10n_source']);
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
    private function fetchAllRecords(string $tableName): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('*')
            ->from($tableName)
            ->orderBy('uid')
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
