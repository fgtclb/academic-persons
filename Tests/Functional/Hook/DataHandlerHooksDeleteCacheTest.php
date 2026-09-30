<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Hook;

use FGTCLB\AcademicPersons\Service\DataHandlerExecutionContext;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A profile that is deleted or restored leaves the cached list and detail pages. The
 * command path is covered by the cleanup command test, this one covers a translation
 * deleted on its own and a restored profile.
 *
 * uid 1  default language, translated into language 1 (uid 2)
 * uid 3  default language, deleted
 */
final class DataHandlerHooksDeleteCacheTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE = 'tx_academicpersons_domain_model_profile';

    protected function setUp(): void
    {
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            ['SYS' => ['caching' => ['cacheConfigurations' => ['pages' => ['backend' => Typo3DatabaseBackend::class]]]]],
        );
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DeletedProfiles.csv');
    }

    private function getPagesCache(): FrontendInterface
    {
        $cache = $this->get(CacheManager::class)->getCache('pages');
        $cache->set('list', 'list', ['profile_list_view']);
        $cache->set('detail-1', 'detail', ['profile_detail_view_1']);
        $cache->set('detail-3', 'detail', ['profile_detail_view_3']);
        return $cache;
    }

    /**
     * @param array<string, array<int, array<string, int>>> $commandMap
     */
    private function processCommands(array $commandMap): void
    {
        $this->get(DataHandlerExecutionContext::class)->runAsLiveBackendUser(
            function (BackendUserAuthentication $backendUser) use ($commandMap): void {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start([], $commandMap, $backendUser);
                $dataHandler->process_cmdmap();
                $this->assertSame([], $dataHandler->errorLog);
            },
        );
    }

    /**
     * The detail view of a translation is tagged with the uid of its default-language
     * record, so deleting the translation flushes that tag.
     */
    #[Test]
    public function aDeletedTranslationLeavesTheDetailViewOfItsProfile(): void
    {
        $cache = $this->getPagesCache();

        $this->processCommands([self::TABLE => [2 => ['delete' => 1]]]);

        $this->assertFalse($cache->has('list'));
        $this->assertFalse($cache->has('detail-1'));
        $this->assertTrue($cache->has('detail-3'));
    }

    #[Test]
    public function aRestoredProfileLeavesTheCachedListAndItsDetailView(): void
    {
        $cache = $this->getPagesCache();

        $this->processCommands([self::TABLE => [3 => ['undelete' => 1]]]);

        $this->assertFalse($cache->has('list'));
        $this->assertFalse($cache->has('detail-3'));
        $this->assertTrue($cache->has('detail-1'));
    }
}
