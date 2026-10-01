<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Backend\RecordList;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Runs the query the list module builds for a search term on the folder of
 * the person records. On TYPO3 v13 the fields searched are the `searchFields`
 * of the table, on v14 every field the TCA schema calls searchable.
 */
final class RecordListSearchTest extends AbstractAcademicPersonsTestCase
{
    private const FOLDER = 20;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordListSearch.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function anOrganisationalUnitIsFoundByItsName(): void
    {
        $this->assertSame(
            [1],
            $this->search('tx_academicpersons_domain_model_organisational_unit', 'Engineering'),
        );
    }

    #[Test]
    public function anOrganisationalUnitIsFoundByItsUniqueName(): void
    {
        $this->assertSame(
            [2],
            $this->search('tx_academicpersons_domain_model_organisational_unit', 'information-services'),
        );
    }

    /**
     * The table is hidden from the list module until page TSconfig shows it.
     * The query is built for one table here, which the TCA option does not
     * affect.
     */
    #[Test]
    public function aProfileInformationIsFoundByItsText(): void
    {
        $this->assertSame(
            [1],
            $this->search('tx_academicpersons_domain_model_profile_information', 'bridge'),
        );
    }

    /**
     * The list module shows the profile, location, organisational unit and
     * function type tables. The other four are hidden from it until page
     * TSconfig shows them, `mod.web_list.table.<table>.hideTable = 0`, and are
     * searched with the same query then. The query is built for one table
     * here, which the TCA option does not affect.
     *
     * The search escapes `_` for LIKE but names no escape character, which
     * SQLite has no default for, so on SQLite a term with `_` finds nothing on
     * either core version. The terms leave out the `fe_` of the identifier.
     *
     * @return \Generator<string, array{string, string, list<int>}>
     */
    public static function importIdentifierSearches(): \Generator
    {
        yield 'profile' => ['tx_academicpersons_domain_model_profile', 'users:12', [1]];
        yield 'contract' => ['tx_academicpersons_domain_model_contract', 'users:12', [1]];
        yield 'e-mail address' => ['tx_academicpersons_domain_model_email', 'users:12', [1]];
        yield 'phone number' => ['tx_academicpersons_domain_model_phone_number', 'users:12', [1]];
        yield 'physical address' => ['tx_academicpersons_domain_model_address', 'users:12', [1]];
        yield 'location' => ['tx_academicpersons_domain_model_location', 'campus:12', [1]];
        yield 'organisational unit' => ['tx_academicpersons_domain_model_organisational_unit', 'units:12', [3]];
        yield 'function type' => ['tx_academicpersons_domain_model_function_type', 'functions:12', [1]];
    }

    /**
     * @param list<int> $expectedUids
     */
    #[DataProvider('importIdentifierSearches')]
    #[Test]
    public function aRecordIsFoundByItsImportIdentifier(string $tableName, string $importIdentifier, array $expectedUids): void
    {
        $this->assertSame($expectedUids, $this->search($tableName, $importIdentifier));
    }

    /**
     * @return list<int>
     */
    private function search(string $tableName, string $searchTerm): array
    {
        $request = (new ServerRequest('https://localhost/typo3/module/web/list'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest($request);
        $recordList->start(self::FOLDER, $tableName, 0, $searchTerm);
        $uids = $recordList->getQueryBuilder($tableName, ['uid'])
            ->executeQuery()
            ->fetchFirstColumn();
        $uids = array_map(intval(...), $uids);
        sort($uids);
        return $uids;
    }
}
