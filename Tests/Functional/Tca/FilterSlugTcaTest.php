<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Service\DataHandlerExecutionContext;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The slug of a function type and of an organisational unit, as a backend save generates
 * it: from the name, without a slash, and unique in the whole table rather than in one
 * folder or site.
 */
final class FilterSlugTcaTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FilterSlug/pages.csv');
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function filterTablesDataProvider(): \Generator
    {
        yield 'function type' => ['tx_academicpersons_domain_model_function_type', 'function_name'];
        yield 'organisational unit' => ['tx_academicpersons_domain_model_organisational_unit', 'unit_name'];
    }

    /**
     * @param array<string, array<string, array<string, int|string>>> $dataMap
     * @return array<string, int> the uids of the new records, by their NEW id
     */
    private function create(array $dataMap): array
    {
        $uids = [];
        $this->get(DataHandlerExecutionContext::class)->runAsLiveBackendUser(
            function (BackendUserAuthentication $backendUser) use ($dataMap, &$uids): void {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start($dataMap, [], $backendUser);
                $dataHandler->process_datamap();
                $this->assertSame([], $dataHandler->errorLog);
                $uids = array_map(intval(...), $dataHandler->substNEWwithIDs);
            },
        );
        return $uids;
    }

    private function slug(string $table, int $uid): string
    {
        $slug = $this->getConnectionPool()->getConnectionForTable($table)->select(['slug'], $table, ['uid' => $uid])->fetchOne();
        $this->assertIsString($slug);
        return $slug;
    }

    #[DataProvider('filterTablesDataProvider')]
    #[Test]
    public function aSaveGeneratesTheSlugFromTheName(string $table, string $nameField): void
    {
        $uids = $this->create([$table => [
            'NEW1' => ['pid' => 100, $nameField => 'Research / Teaching Staff'],
            'NEW2' => ['pid' => 101, $nameField => 'Research / Teaching Staff'],
        ]]);

        $this->assertSame('research-teaching-staff', $this->slug($table, $uids['NEW1']));
        // The same name in another folder: unique in the table, not in the folder.
        $this->assertSame('research-teaching-staff-1', $this->slug($table, $uids['NEW2']));
    }
}
