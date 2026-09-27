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
 * Compiles the backend form of person records the way FormEngine does when an
 * editor opens them, with the managed fields of the fixture extension
 * `test_managed_fields`: the profile title and website, the contract
 * position, the e-mail address and the phone number type.
 *
 * Which records count as synchronised is covered in `ManagedFieldResolverTest`.
 * This test covers what the form makes of it.
 */
final class ManagedFieldsReadOnlyTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-managed-fields';
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ManagedFields/records.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function aManagedFieldOfASynchronisedContractIsReadOnly(): void
    {
        $columns = $this->compile('tx_academicpersons_domain_model_contract', 1);

        $this->assertTrue($columns['position']['config']['readOnly'] ?? false);
    }

    #[Test]
    public function aManagedFieldNamesTheImportIdentifierOfItsRecord(): void
    {
        $columns = $this->compile('tx_academicpersons_domain_model_contract', 1);

        $this->assertSame(
            'Maintained by the synchronisation (fe_users:1), so it is read-only here.',
            $columns['position']['description'] ?? null,
        );
    }

    #[Test]
    public function aFieldThatIsNotManagedStaysEditable(): void
    {
        $columns = $this->compile('tx_academicpersons_domain_model_contract', 1);

        $this->assertFalse($columns['room']['config']['readOnly'] ?? false);
        $this->assertArrayNotHasKey('description', $columns['room']);
    }

    #[Test]
    public function aContractAnEditorAddedStaysEditable(): void
    {
        $columns = $this->compile('tx_academicpersons_domain_model_contract', 2);

        $this->assertFalse($columns['position']['config']['readOnly'] ?? false);
        $this->assertArrayNotHasKey('description', $columns['position']);
    }

    #[Test]
    public function aSynchronisedContractOfAnExcludedProfileStaysEditable(): void
    {
        $columns = $this->compile('tx_academicpersons_domain_model_contract', 3);

        $this->assertFalse($columns['position']['config']['readOnly'] ?? false);
    }

    /**
     * The synchronisation keeps writing a hidden profile, so its records stay
     * locked.
     */
    #[Test]
    public function aSynchronisedContractOfAHiddenProfileIsLocked(): void
    {
        $columns = $this->compile('tx_academicpersons_domain_model_contract', 4);

        $this->assertTrue($columns['position']['config']['readOnly'] ?? false);
    }

    /**
     * The fixture names the phone number type by its property. It is a select,
     * which FormEngine renders with an element of its own.
     */
    #[Test]
    public function aManagedSelectOfASynchronisedPhoneNumberIsReadOnly(): void
    {
        $columns = $this->compile('tx_academicpersons_domain_model_phone_number', 1);

        $this->assertTrue($columns['type']['config']['readOnly'] ?? false);
        $this->assertFalse($columns['phone_number']['config']['readOnly'] ?? false);
    }

    #[Test]
    public function aManagedFieldOfASynchronisedEmailAddressIsReadOnly(): void
    {
        $columns = $this->compile('tx_academicpersons_domain_model_email', 1);

        $this->assertTrue($columns['email']['config']['readOnly'] ?? false);
    }

    #[Test]
    public function anEmailAddressAnEditorAddedStaysEditable(): void
    {
        $columns = $this->compile('tx_academicpersons_domain_model_email', 2);

        $this->assertFalse($columns['email']['config']['readOnly'] ?? false);
    }

    /**
     * The contracts of a profile form and their contact records are collapsed.
     * Expanding one compiles it on its own, as the inline AJAX controller of
     * core does, and with the same data group. Contract 1 of profile 1 carries
     * a synchronised e-mail address and one an editor added.
     */
    #[Test]
    public function anExpandedEmailAddressInsideTheProfileFormIsLockedPerRecord(): void
    {
        $this->assertTrue($this->compileExpandedEmailAddress(1)['email']['config']['readOnly'] ?? false);
        $this->assertFalse($this->compileExpandedEmailAddress(2)['email']['config']['readOnly'] ?? false);
    }

    #[Test]
    public function theManagedFieldsOfASynchronisedProfileAreReadOnly(): void
    {
        $columns = $this->compile('tx_academicpersons_domain_model_profile', 1);

        $this->assertTrue($columns['title']['config']['readOnly'] ?? false);
        $this->assertTrue($columns['website']['config']['readOnly'] ?? false);
        $this->assertFalse($columns['publications_link']['config']['readOnly'] ?? false);
    }

    /**
     * The storage page of the fixture gives the profile title a description
     * through page TSconfig. The note is added on a line of its own below it.
     */
    #[Test]
    public function theNoteFollowsTheDescriptionAFieldAlreadyHas(): void
    {
        $columns = $this->compile('tx_academicpersons_domain_model_profile', 1);

        $this->assertSame(
            "Academic degrees only\nMaintained by the synchronisation (fe_users:1), so it is read-only here.",
            $columns['title']['description'] ?? null,
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function compile(string $tableName, int $uid): array
    {
        return $this->compileRecord($tableName, $uid)['processedTca']['columns'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function compileExpandedEmailAddress(int $uid): array
    {
        return $this->compileRecord('tx_academicpersons_domain_model_email', $uid, [
            'isInlineChild' => true,
            'isInlineAjaxOpeningContext' => true,
            'inlineParentConfig' => $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns']['email_addresses']['config'],
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'tx_academicpersons_domain_model_contract',
            'inlineParentFieldName' => 'email_addresses',
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'tx_academicpersons_domain_model_profile',
            'inlineTopMostParentFieldName' => 'contracts',
        ])['processedTca']['columns'];
    }

    /**
     * @param array<string, mixed> $inlineContext
     * @return array<string, mixed>
     */
    private function compileRecord(string $tableName, int $uid, array $inlineContext = []): array
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
                ...$inlineContext,
            ],
            $this->get(TcaDatabaseRecord::class),
        );
    }
}
