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
 * The shipped settings manage no field, so a synchronised record keeps every
 * field editable until a package names one.
 */
final class ManagedFieldsNotDeclaredTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ManagedFields/records.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function aSynchronisedContractStaysEditable(): void
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $columns = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tx_academicpersons_domain_model_contract',
                'vanillaUid' => 1,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        )['processedTca']['columns'];

        $this->assertFalse($columns['position']['config']['readOnly'] ?? false);
        $this->assertArrayNotHasKey('description', $columns['position']);
    }
}
