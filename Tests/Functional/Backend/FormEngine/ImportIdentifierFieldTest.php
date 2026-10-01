<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compiles the backend form of the eight person tables that carry an import
 * identifier, the way FormEngine does when an editor opens a record. Record 1
 * of each table carries an identifier, record 2 does not.
 *
 * A column FormEngine keeps in `processedTca` is one the form renders: the
 * data providers remove every column the record type does not show and every
 * column whose display condition fails.
 */
final class ImportIdentifierFieldTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportIdentifierField.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function tablesWithImportIdentifier(): \Generator
    {
        yield 'profile' => ['tx_academicpersons_domain_model_profile', 'fe_users:12'];
        yield 'contract' => ['tx_academicpersons_domain_model_contract', 'fe_users:12'];
        yield 'e-mail address' => ['tx_academicpersons_domain_model_email', 'fe_users:12'];
        yield 'phone number' => ['tx_academicpersons_domain_model_phone_number', 'telephone:fe_users:12'];
        yield 'physical address' => ['tx_academicpersons_domain_model_address', 'fe_users:12'];
        yield 'location' => ['tx_academicpersons_domain_model_location', 'campus:12'];
        yield 'organisational unit' => ['tx_academicpersons_domain_model_organisational_unit', 'units:12'];
        yield 'function type' => ['tx_academicpersons_domain_model_function_type', 'functions:12'];
    }

    #[DataProvider('tablesWithImportIdentifier')]
    #[Test]
    public function aRecordWithAnImportIdentifierShowsItReadOnly(string $tableName, string $importIdentifier): void
    {
        $result = $this->compile($tableName, 1);

        $field = $result['processedTca']['columns']['import_identifier'] ?? [];
        $this->assertSame('input', $field['config']['type'] ?? null);
        $this->assertTrue($field['config']['readOnly'] ?? false);
        $this->assertSame($importIdentifier, $result['databaseRow']['import_identifier'] ?? null);
    }

    #[DataProvider('tablesWithImportIdentifier')]
    #[Test]
    public function aRecordWithoutAnImportIdentifierShowsNoField(string $tableName): void
    {
        $result = $this->compile($tableName, 2);

        $this->assertArrayNotHasKey('import_identifier', $result['processedTca']['columns']);
    }

    #[DataProvider('tablesWithImportIdentifier')]
    #[Test]
    public function theImportIdentifierHasALabel(string $tableName): void
    {
        $result = $this->compile($tableName, 1);

        $this->assertSame(
            'Import identifier',
            $GLOBALS['LANG']->sL($result['processedTca']['columns']['import_identifier']['label'] ?? ''),
        );
    }

    /**
     * The profile shows the switch that stops its synchronisation next to the
     * identifier, and only when it has one.
     */
    #[Test]
    public function theSynchronisationSwitchOfAProfileSitsNextToItsImportIdentifier(): void
    {
        $result = $this->compile('tx_academicpersons_domain_model_profile', 1);

        $this->assertSame(
            ['import_identifier', 'skip_sync'],
            $this->getPaletteFields($result, 'import'),
        );
        $this->assertNotContains('skip_sync', $this->getPaletteFields($result, 'hidden'));
        $this->assertArrayHasKey('skip_sync', $result['processedTca']['columns']);
    }

    #[Test]
    public function aProfileWithoutAnImportIdentifierShowsNoSynchronisationSwitch(): void
    {
        $result = $this->compile('tx_academicpersons_domain_model_profile', 2);

        $this->assertArrayNotHasKey('skip_sync', $result['processedTca']['columns']);
    }

    /**
     * @param array<string, mixed> $result
     * @return list<string>
     */
    private function getPaletteFields(array $result, string $palette): array
    {
        $fields = [];
        foreach (GeneralUtility::trimExplode(',', (string)($result['processedTca']['palettes'][$palette]['showitem'] ?? ''), true) as $item) {
            $fields[] = GeneralUtility::trimExplode(';', $item)[0];
        }
        return $fields;
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
