<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Hook;

use FGTCLB\AcademicPersons\Service\DataHandlerExecutionContext;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A profile that is created or saved through the DataHandler leaves the cached list
 * and detail pages. The detail view of a translation is tagged with the uid of its
 * default-language record, and the hook flushed the tag of the translation itself,
 * which no page carries, until ACE-858.
 *
 * uid 1  default language
 * uid 2  default language, never touched
 * uid 3  translation of uid 1
 */
final class DataHandlerHooksSaveCacheTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    private const TABLE = 'tx_academicpersons_domain_model_profile';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
        'FR' => ['id' => 2, 'title' => 'Français', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            ['SYS' => ['caching' => ['cacheConfigurations' => ['pages' => ['backend' => Typo3DatabaseBackend::class]]]]],
        );
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SavedProfiles.csv');
        // DataHandler refuses a translation into a language the site of the page does
        // not declare, so the site comes first.
        $this->writeSiteConfiguration('acme', $this->buildSiteConfiguration(1, 'https://www.acme.com/'), [
            $this->buildDefaultLanguageConfiguration('EN', '/'),
            $this->buildLanguageConfiguration('DE', '/de/'),
            $this->buildLanguageConfiguration('FR', '/fr/'),
        ]);
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    private function getPagesCache(): FrontendInterface
    {
        $cache = $this->get(CacheManager::class)->getCache('pages');
        $cache->set('list', 'list', ['profile_list_view']);
        $cache->set('detail-1', 'detail', ['profile_detail_view_1']);
        $cache->set('detail-2', 'detail', ['profile_detail_view_2']);
        return $cache;
    }

    /**
     * @param array<string, array<int|string, array<string, int|string>>> $dataMap
     * @param array<string, array<int, array<string, int>>> $commandMap
     */
    private function process(array $dataMap, array $commandMap = []): void
    {
        $this->get(DataHandlerExecutionContext::class)->runAsLiveBackendUser(
            function (BackendUserAuthentication $backendUser) use ($dataMap, $commandMap): void {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start($dataMap, $commandMap, $backendUser);
                $dataHandler->process_datamap();
                $dataHandler->process_cmdmap();
                $this->assertSame([], $dataHandler->errorLog);
            },
        );
    }

    private function assertProfileOneWasFlushed(FrontendInterface $cache): void
    {
        $this->assertFalse($cache->has('list'), 'The cached list was not flushed.');
        $this->assertFalse($cache->has('detail-1'), 'The cached detail view of the profile was not flushed.');
        $this->assertTrue($cache->has('detail-2'), 'The cached detail view of another profile was flushed.');
    }

    #[Test]
    public function aSavedProfileLeavesTheCachedListAndItsDetailView(): void
    {
        $cache = $this->getPagesCache();

        $this->process([self::TABLE => [1 => ['last_name' => 'Changed']]]);

        $this->assertProfileOneWasFlushed($cache);
    }

    #[Test]
    public function aSavedTranslationLeavesTheDetailViewOfItsProfile(): void
    {
        $cache = $this->getPagesCache();

        $this->process([self::TABLE => [3 => ['last_name' => 'Geändert']]]);

        $this->assertProfileOneWasFlushed($cache);
    }

    #[Test]
    public function aCreatedProfileLeavesTheCachedList(): void
    {
        $cache = $this->getPagesCache();

        $this->process([self::TABLE => ['NEW1' => ['pid' => 100, 'first_name' => 'Nina', 'last_name' => 'New']]]);

        $this->assertFalse($cache->has('list'));
        $this->assertTrue($cache->has('detail-1'));
        $this->assertTrue($cache->has('detail-2'));
    }

    /**
     * The localize command creates the translation through a datamap of a DataHandler
     * of its own, which reaches the hook with a `NEW…` id that is substituted by then.
     */
    #[Test]
    public function aLocalizedProfileLeavesTheDetailViewOfItsProfile(): void
    {
        $cache = $this->getPagesCache();

        $this->process([], [self::TABLE => [1 => ['localize' => 2]]]);

        $translationCount = $this->getConnectionPool()->getConnectionForTable(self::TABLE)
            ->count('uid', self::TABLE, ['l10n_parent' => 1, 'sys_language_uid' => 2]);
        $this->assertSame(1, $translationCount, 'The profile was not localized.');
        $this->assertProfileOneWasFlushed($cache);
    }
}
