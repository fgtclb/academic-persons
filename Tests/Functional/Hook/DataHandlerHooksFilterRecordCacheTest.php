<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Hook;

use FGTCLB\AcademicPersons\Service\DataHandlerExecutionContext;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The filter form of a cached list offers the function types and organisational units
 * as they were when the list was cached. A change of one of them flushes the lists, and
 * leaves the detail views alone. A record of another persons table flushes nothing.
 */
final class DataHandlerHooksFilterRecordCacheTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            ['SYS' => ['caching' => ['cacheConfigurations' => ['pages' => ['backend' => Typo3DatabaseBackend::class]]]]],
        );
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FilterRecords.csv');
    }

    private function getPagesCache(): FrontendInterface
    {
        $cache = $this->get(CacheManager::class)->getCache('pages');
        $cache->set('list', 'list', ['profile_list_view']);
        $cache->set('detail-1', 'detail', ['profile_detail_view_1']);
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

    /**
     * @return \Generator<string, array{0: array<string, array<int|string, array<string, int|string>>>, 1: array<string, array<int, array<string, int>>>}>
     */
    public static function filterRecordChangesDataProvider(): \Generator
    {
        $functionType = 'tx_academicpersons_domain_model_function_type';
        $unit = 'tx_academicpersons_domain_model_organisational_unit';
        yield 'function type renamed' => [[$functionType => [1 => ['function_name' => 'Full professor']]], []];
        yield 'function type created' => [[$functionType => ['NEW1' => ['pid' => 100, 'function_name' => 'Dean']]], []];
        yield 'unit hidden' => [[$unit => [1 => ['hidden' => 1]]], []];
        yield 'function type deleted' => [[], [$functionType => [1 => ['delete' => 1]]]];
        yield 'unit deleted' => [[], [$unit => [1 => ['delete' => 1]]]];
    }

    /**
     * @param array<string, array<int|string, array<string, int|string>>> $dataMap
     * @param array<string, array<int, array<string, int>>> $commandMap
     */
    #[DataProvider('filterRecordChangesDataProvider')]
    #[Test]
    public function aChangedFilterRecordFlushesTheCachedLists(array $dataMap, array $commandMap): void
    {
        $cache = $this->getPagesCache();

        $this->process($dataMap, $commandMap);

        $this->assertFalse($cache->has('list'));
        $this->assertTrue($cache->has('detail-1'));
    }

    #[Test]
    public function aRecordTheFiltersDoNotOfferFlushesNothing(): void
    {
        $cache = $this->getPagesCache();

        $this->process(['tx_academicpersons_domain_model_location' => [1 => ['title' => 'Annex']]]);

        $this->assertTrue($cache->has('list'));
        $this->assertTrue($cache->has('detail-1'));
    }
}
