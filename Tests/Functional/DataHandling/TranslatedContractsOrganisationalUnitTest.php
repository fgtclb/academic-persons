<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\DataHandling;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A contract belongs to its profile and has the language of the profile. Its
 * organisational unit is a select with `l10n_mode` `exclude`, so the
 * translation of a contract points at the default language unit, and a
 * contract of a profile that is valid in all languages does as well.
 *
 * TYPO3 14.3.10 gives every inline child the language of its parent when the
 * parent is created, changes its language or gets a new child (core #103970).
 * While the contracts list of the unit held every language, a contract added
 * to the default language unit turned the translated contract into a default
 * language contract without a translation parent, and the contract valid in
 * all languages into a default language one (ACE-943). The list holds the
 * default language contracts only now.
 *
 * Contract 1 is in the default language, contract 2 translates it, and
 * contract 3 belongs to a profile that is valid in all languages. All three
 * name unit 1.
 */
final class TranslatedContractsOrganisationalUnitTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE_UNIT = 'tx_academicpersons_domain_model_organisational_unit';
    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';

    private const CONTRACTS_OF_OTHER_LANGUAGES = [
        ['uid' => 2, 'sys_language_uid' => 1, 'l10n_parent' => 1, 'l10n_source' => 1, 'organisational_unit' => 1],
        ['uid' => 3, 'sys_language_uid' => -1, 'l10n_parent' => 0, 'l10n_source' => 0, 'organisational_unit' => 1],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedContractsOrganisationalUnit/unitWithContractsOfEveryLanguage.csv');
        $this->setUpBackendUser(1);
        // The DataHandler and FormEngine read the language service from $GLOBALS['LANG'].
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG'], $GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    #[Test]
    public function addingAContractToTheUnitKeepsTheContractsOfOtherLanguages(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            self::TABLE_CONTRACT => ['NEW1' => ['pid' => 100, 'position' => 'Lecturer']],
            self::TABLE_UNIT => [1 => ['contracts' => '1,NEW1']],
        ], []);
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');

        $contracts = $this->fetchContracts();
        $this->assertSame(
            [0, 0],
            array_column(array_filter($contracts, static fn(array $row): bool => $row['uid'] === 1 || $row['uid'] > 3), 'sys_language_uid'),
            'The default language contract and the new one are in the default language.',
        );
        $this->assertSame(
            self::CONTRACTS_OF_OTHER_LANGUAGES,
            array_values(array_filter($contracts, static fn(array $row): bool => $row['uid'] === 2 || $row['uid'] === 3)),
        );
    }

    /**
     * The `foreign_match_fields` of the list: the form of the unit offers, and
     * submits, the default language contracts only.
     */
    #[Test]
    public function theContractsListOfTheUnitHoldsTheDefaultLanguageContractsOnly(): void
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => self::TABLE_UNIT,
                'vanillaUid' => 1,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );

        $this->assertSame([1], array_column($result['processedTca']['columns']['contracts']['children'], 'vanillaUid'));
    }

    /**
     * @return list<array<string, int>>
     */
    private function fetchContracts(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTRACT);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'sys_language_uid', 'l10n_parent', 'l10n_source', 'organisational_unit')
            ->from(self::TABLE_CONTRACT)
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        return array_map(
            static fn(array $row): array => array_map(intval(...), $row),
            $rows,
        );
    }
}
