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
 * A profile that is created or saved in the backend leaves the cached list and detail
 * pages (ACE-844). The hook reacted to a save of an existing profile only, so a new
 * profile appeared on a cached list only once the page cache expired.
 *
 * uid 1  default language
 * uid 2  default language, never touched
 */
final class DataHandlerHooksSaveCacheTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    private const TABLE = 'tx_academicpersons_domain_model_profile';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            ['SYS' => ['caching' => ['cacheConfigurations' => ['pages' => ['backend' => Typo3DatabaseBackend::class]]]]],
        );
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SavedProfiles.csv');
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
     */
    private function processData(array $dataMap): void
    {
        $this->get(DataHandlerExecutionContext::class)->runAsLiveBackendUser(
            function (BackendUserAuthentication $backendUser) use ($dataMap): void {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start($dataMap, [], $backendUser);
                $dataHandler->process_datamap();
                $this->assertSame([], $dataHandler->errorLog);
            },
        );
    }

    #[Test]
    public function aCreatedProfileLeavesTheCachedList(): void
    {
        $cache = $this->getPagesCache();

        $this->processData([self::TABLE => ['NEW1' => ['pid' => 100, 'first_name' => 'Nina', 'last_name' => 'New']]]);

        $this->assertFalse($cache->has('list'));
        $this->assertTrue($cache->has('detail-1'));
        $this->assertTrue($cache->has('detail-2'));
    }

    /**
     * The detail view of a translation is tagged with the uid of its default-language
     * record, so a created translation flushes that tag.
     */
    #[Test]
    public function aCreatedTranslationLeavesTheDetailViewOfItsProfile(): void
    {
        $cache = $this->getPagesCache();

        $this->processData([
            self::TABLE => [
                'NEW1' => [
                    'pid' => 100,
                    'sys_language_uid' => 1,
                    'l10n_parent' => 1,
                    'first_name' => 'Dora',
                    'last_name' => 'Default (DE)',
                ],
            ],
        ]);

        $this->assertFalse($cache->has('list'));
        $this->assertFalse($cache->has('detail-1'));
        $this->assertTrue($cache->has('detail-2'));
    }

    /**
     * The localize command creates the translation through a datamap of a DataHandler
     * of its own, which reaches the hook with a `NEW…` id as well.
     */
    #[Test]
    public function aLocalizedProfileLeavesTheDetailViewOfItsProfile(): void
    {
        // DataHandler refuses a "localize" into a language the site of the page does
        // not declare, so the site comes first.
        $this->writeSiteConfiguration('acme', $this->buildSiteConfiguration(1, 'https://www.acme.com/'), [
            $this->buildDefaultLanguageConfiguration('EN', '/'),
            $this->buildLanguageConfiguration('DE', '/de/'),
        ]);
        $cache = $this->getPagesCache();

        $this->get(DataHandlerExecutionContext::class)->runAsLiveBackendUser(
            function (BackendUserAuthentication $backendUser): void {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start([], [self::TABLE => [1 => ['localize' => 1]]], $backendUser);
                $dataHandler->process_cmdmap();
                $this->assertSame([], $dataHandler->errorLog);
            },
        );

        $translationCount = $this->getConnectionPool()->getConnectionForTable(self::TABLE)
            ->count('uid', self::TABLE, ['l10n_parent' => 1, 'sys_language_uid' => 1]);
        $this->assertSame(1, $translationCount, 'The profile was not localized.');
        $this->assertFalse($cache->has('list'));
        $this->assertFalse($cache->has('detail-1'));
        $this->assertTrue($cache->has('detail-2'));
    }

    #[Test]
    public function aSavedProfileLeavesTheCachedListAndItsDetailView(): void
    {
        $cache = $this->getPagesCache();

        $this->processData([self::TABLE => [1 => ['last_name' => 'Changed']]]);

        $this->assertFalse($cache->has('list'));
        $this->assertFalse($cache->has('detail-1'));
        $this->assertTrue($cache->has('detail-2'));
    }
}
