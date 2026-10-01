<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Backend\RecordList;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
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
