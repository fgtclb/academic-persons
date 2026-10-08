<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The form of a new contract, compiled the way the backend compiles it when an
 * editor creates one.
 *
 * The record title of a new record is resolved through the `label_userFunc` of the
 * table with the `NEW…` placeholder as uid. `ContractLabels` handed it to an Extbase
 * `findByUid()`, and PostgreSQL rejects the placeholder as input for the integer
 * column (SQLSTATE 22P02), so the form of every new contract failed there. SQLite,
 * MariaDB and MySQL accept the comparison and find nothing, so run this class on
 * PostgreSQL to see the defect.
 */
final class NewContractFormTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/NewContractForm/page.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG'], $GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    #[Test]
    public function theFormOfANewContractIsCompiled(): void
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tx_academicpersons_domain_model_contract',
                'vanillaUid' => 40,
                'command' => 'new',
            ],
            $this->get(TcaDatabaseRecord::class),
        );

        $this->assertStringStartsWith('NEW', (string)$result['databaseRow']['uid']);
        $this->assertSame(40, (int)$result['databaseRow']['pid']);
    }
}
