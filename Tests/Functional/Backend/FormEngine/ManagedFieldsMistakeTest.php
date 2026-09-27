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
 * The fixture extension `test_managed_fields_mistake` misspells a contract
 * field. The instance boots, because the settings, which are built while the
 * TCA loads, only record the mistake. So do the forms of other tables. The
 * form of a person record fails with the name that matches no field.
 */
final class ManagedFieldsMistakeTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-managed-fields-mistake';
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ManagedFields/records.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function theFormOfAPageIsNotAffected(): void
    {
        $this->assertArrayHasKey('title', $this->compile('pages', 20)['processedTca']['columns']);
    }

    #[Test]
    public function theFormOfAPersonRecordNamesTheMistake(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1790536034);
        $this->expectExceptionMessage('`managedFields.contracts` names `positon`');
        $this->compile('tx_academicpersons_domain_model_email', 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(string $tableName, int $uid): array
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => $tableName,
                'vanillaUid' => $uid,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );
    }
}
