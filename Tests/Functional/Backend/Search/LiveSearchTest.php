<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Backend\Search;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Search\LiveSearch\DatabaseRecordProvider;
use TYPO3\CMS\Backend\Search\LiveSearch\ResultItem;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandProperty;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandPropertyName;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * The backend search of the toolbar. It searches the fields the list module
 * searches, in every table the list module shows: profiles, locations,
 * organisational units and function types. Like the list module it leaves out
 * the tables the TCA hides, on both core versions.
 */
final class LiveSearchTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LiveSearch.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest('https://localhost/typo3/ajax/livesearch'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function listedTables(): \Generator
    {
        yield 'profile' => ['tx_academicpersons_domain_model_profile', 'hr:4711'];
        yield 'location' => ['tx_academicpersons_domain_model_location', 'hr:4712'];
        yield 'organisational unit' => ['tx_academicpersons_domain_model_organisational_unit', 'hr:4713'];
        yield 'function type' => ['tx_academicpersons_domain_model_function_type', 'hr:4714'];
    }

    #[DataProvider('listedTables')]
    #[Test]
    public function aRecordIsFoundByItsImportIdentifier(string $tableName, string $importIdentifier): void
    {
        $this->assertSame([$tableName . ':1'], $this->search($importIdentifier));
    }

    #[Test]
    public function aContractIsNotSearched(): void
    {
        $this->assertSame([], $this->search('hr:4715'));
    }

    /**
     * @return list<string> table and uid of each record found
     */
    private function search(string $searchTerm): array
    {
        $results = $this->get(DatabaseRecordProvider::class)->find(new SearchDemand([
            new DemandProperty(DemandPropertyName::query, $searchTerm),
            new DemandProperty(DemandPropertyName::limit, 50),
        ]));

        return array_map(
            static fn(ResultItem $item): string => $item->getExtraData()['table'] . ':' . $item->getExtraData()['uid'],
            $results,
        );
    }
}
