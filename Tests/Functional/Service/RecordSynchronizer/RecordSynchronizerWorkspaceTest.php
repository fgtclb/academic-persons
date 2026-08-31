<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service\RecordSynchronizer;

use FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext;
use FGTCLB\AcademicPersons\Service\RecordSynchronizerInterface;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Workspace behaviour of {@see \FGTCLB\AcademicPersons\Service\RecordSynchronizer}
 * (ACE-480 / ACE-483).
 *
 * This class started as characterisation pins of the raw-SQL implementation, which
 * wrote live rows from any workspace. Since the synchroniser routes through the
 * DataHandler, workspace correctness falls out of the acting backend user: a live
 * context writes live rows, a backend user acting in a workspace writes versioned
 * rows only (`t3ver_wsid`, `t3ver_state=1`), and a frontend request acting in a
 * non-live workspace is refused entirely.
 *
 * The fixture holds a live profile with a workspace version of it (uid 101), a live
 * contract with a workspace version of it (uid 103), and a contract that exists only
 * in workspace 1 (uid 102, `t3ver_state=1`).
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
        unset($GLOBALS['TYPO3_REQUEST'], $GLOBALS['BE_USER']);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * A synchronisation in the live context (no backend user, workspace aspect 0)
     * writes live rows - and only live rows: the translation of the profile is a
     * plain record without any `t3ver_*` state, created from the LIVE values, not
     * from the workspace version.
     */
    #[Test]
    public function liveRunCreatesProfileTranslationAsALiveRow(): void
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
     * The inline cascade of the live run sees only the live contract: it is
     * translated as a live row wired to the translated profile, while the
     * workspace-only contract (uid 102) and the workspace version of the live one
     * (uid 103) stay untouched - no draft state leaks into the live site (ACE-480).
     */
    #[Test]
    public function liveRunTranslatesOnlyTheLiveContract(): void
    {
        $this->synchronizeProfile(1);

        $contracts = $this->fetchAllRecords(self::TABLE_CONTRACT);
        $this->assertCount(4, $contracts);
        $translatedContract = $this->fetchTranslation(self::TABLE_CONTRACT, 1);
        $this->assertNotNull($translatedContract);
        $this->assertSame(0, (int)$translatedContract['t3ver_wsid']);
        $this->assertSame('Live Professor', $translatedContract['position']);
        $translatedProfile = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translatedProfile);
        $this->assertSame((int)$translatedProfile['uid'], (int)$translatedContract['profile']);
        // The workspace rows are exactly as the fixture created them: untranslated.
        foreach ([102, 103] as $workspaceRowUid) {
            $this->assertSame(0, (int)$contracts[$workspaceRowUid]['sys_language_uid']);
            $this->assertNull($this->fetchTranslation(self::TABLE_CONTRACT, $workspaceRowUid));
        }
    }

    /**
     * Flipped defect pin (ACE-480): a backend user acting in workspace 1 produces
     * versioned rows only. Every row the run creates carries `t3ver_wsid=1` and
     * `t3ver_state=1` (new placeholder), the live state is untouched, and the
     * translation is created from the workspace-overlaid values: the profile version
     * uid 101 supplies "Draft", the contract version uid 103 supplies
     * "Draft Professor", and the workspace-only contract uid 102 is carried along.
     */
    #[Test]
    public function workspaceRunWritesOnlyVersionedRows(): void
    {
        $liveProfileUidsBefore = $this->fetchLiveRowUids(self::TABLE_PROFILE);
        $liveContractUidsBefore = $this->fetchLiveRowUids(self::TABLE_CONTRACT);
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = 1;

        $this->synchronizeProfile(1);

        $this->assertSame($liveProfileUidsBefore, $this->fetchLiveRowUids(self::TABLE_PROFILE), 'The live profile rows changed.');
        $this->assertSame($liveContractUidsBefore, $this->fetchLiveRowUids(self::TABLE_CONTRACT), 'The live contract rows changed.');
        $newProfileRows = $this->fetchRowsCreatedAfter(self::TABLE_PROFILE, 101);
        $this->assertCount(1, $newProfileRows);
        $profileTranslation = $newProfileRows[0];
        $this->assertSame(1, (int)$profileTranslation['sys_language_uid']);
        $this->assertSame(1, (int)$profileTranslation['l10n_parent']);
        $this->assertSame(1, (int)$profileTranslation['t3ver_wsid']);
        $this->assertSame(1, (int)$profileTranslation['t3ver_state']);
        $this->assertSame('Draft', $profileTranslation['first_name']);
        $newContractRows = $this->fetchRowsCreatedAfter(self::TABLE_CONTRACT, 103);
        $this->assertNotSame([], $newContractRows);
        $positions = [];
        foreach ($newContractRows as $contractRow) {
            $this->assertSame(1, (int)$contractRow['sys_language_uid']);
            $this->assertSame(1, (int)$contractRow['t3ver_wsid']);
            $this->assertSame(1, (int)$contractRow['t3ver_state']);
            $positions[] = $contractRow['position'];
        }
        sort($positions);
        $this->assertSame(['Draft Professor', 'Workspace Only Contract'], $positions);
    }

    /**
     * Flipped defect pin (ACE-480): the uid of a workspace VERSION row (101,
     * `t3ver_oid=1`) is refused as a synchronisation entry point. The raw-SQL
     * implementation accepted it and published the draft values as a live
     * translation; the reworked service requires a live record - DataHandler
     * addresses versioned records through their live uid and overlays them itself -
     * so nothing is written at all.
     */
    #[Test]
    public function workspaceVersionUidIsRefusedAsSynchronizationEntryPoint(): void
    {
        $this->synchronizeProfile(101);

        $this->assertCount(2, $this->fetchAllRecords(self::TABLE_PROFILE));
        $this->assertCount(3, $this->fetchAllRecords(self::TABLE_CONTRACT));
    }

    /**
     * The refusal policy (ACE-480): a frontend request acting in a non-live workspace
     * must not synchronise anything - neither live rows (that would leak draft-time
     * decisions into the live site) nor versioned rows (a frontend visitor must not
     * create workspace content). The synchroniser returns without writing.
     */
    #[Test]
    public function frontendRequestInWorkspaceIsRefused(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.acme.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect(1));

        $this->synchronizeProfile(1);

        $this->assertCount(2, $this->fetchAllRecords(self::TABLE_PROFILE));
        $this->assertCount(3, $this->fetchAllRecords(self::TABLE_CONTRACT));
    }

    /**
     * The counterpart proving the policy checks the workspace, not the frontend: the
     * same frontend request in the LIVE workspace synchronises normally and writes
     * live rows.
     */
    #[Test]
    public function frontendRequestInLiveWorkspaceSynchronizes(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.acme.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect(0));

        $this->synchronizeProfile(1);

        $translation = $this->fetchTranslation(self::TABLE_PROFILE, 1);
        $this->assertNotNull($translation);
        $this->assertSame(0, (int)$translation['t3ver_wsid']);
        $this->assertSame('Live', $translation['first_name']);
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
     * @return list<int>
     */
    private function fetchLiveRowUids(string $tableName): array
    {
        $uids = [];
        foreach ($this->fetchAllRecords($tableName) as $record) {
            if ((int)$record['t3ver_wsid'] === 0) {
                $uids[] = (int)$record['uid'];
            }
        }
        return $uids;
    }

    /**
     * @return list<array<string, mixed>> All rows with a uid above the given fixture high-water mark.
     */
    private function fetchRowsCreatedAfter(string $tableName, int $highestFixtureUid): array
    {
        $rows = [];
        foreach ($this->fetchAllRecords($tableName) as $record) {
            if ((int)$record['uid'] > $highestFixtureUid) {
                $rows[] = $record;
            }
        }
        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>> All rows of the table, keyed and ordered by uid.
     */
    private function fetchAllRecords(string $tableName): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $rowsByUid = [];
        foreach ($rows as $row) {
            $rowsByUid[(int)$row['uid']] = $row;
        }
        return $rowsByUid;
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
