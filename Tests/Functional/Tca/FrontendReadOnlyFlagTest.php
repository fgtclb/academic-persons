<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\Validation;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * A field listing `frontendreadonly` is locked for profile owners and stays
 * editable for backend editors. The fixture extension `test_frontend_readonly`
 * restates the shipped `validations` map and marks the profile website, the
 * contract position and room and the address street, plus the middle name
 * together with `readonly`. The frontend half, the editor ignoring a
 * submitted value, is covered in `academic_persons_edit`.
 */
final class FrontendReadOnlyFlagTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('tests/test-frontend-readonly');
        parent::setUp();
    }

    private function validation(string $set, string $property): Validation
    {
        $validation = $this->get(AcademicPersonsSettings::class)->getValidationSet($set)?->get($property);
        $this->assertInstanceOf(Validation::class, $validation, $set . '.' . $property);
        return $validation;
    }

    #[Test]
    public function theFieldsAreReadOnlyForTheFrontendEditor(): void
    {
        $this->assertTrue($this->validation('profile', 'website')->readOnly);
        $this->assertTrue($this->validation('contract', 'position')->readOnly);
        $this->assertTrue($this->validation('contract', 'room')->readOnly, 'The mixed case spelling is not recognised.');
        $this->assertTrue($this->validation('physicalAddress', 'street')->readOnly);
    }

    /**
     * A read-only value cannot be corrected by its owner, so the frontend does
     * not ask for one. The backend still does.
     */
    #[Test]
    public function theFrontendEditorDoesNotRequireAFieldItShowsReadOnly(): void
    {
        $position = $this->validation('contract', 'position');

        $this->assertFalse($position->required);
        $this->assertSame([], $position->validatorClassNames);
        $this->assertFalse($this->validation('physicalAddress', 'street')->required);
    }

    #[Test]
    public function backendEditorsKeepTheFieldsEditable(): void
    {
        $profile = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'];
        $contract = $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns'];
        $address = $GLOBALS['TCA']['tx_academicpersons_domain_model_address']['columns'];

        $this->assertFalse((bool)($profile['website']['config']['readOnly'] ?? false));
        $this->assertFalse((bool)($contract['position']['config']['readOnly'] ?? false));
        $this->assertFalse((bool)($contract['room']['config']['readOnly'] ?? false));
        $this->assertFalse((bool)($address['street']['config']['readOnly'] ?? false));
    }

    #[Test]
    public function backendEditorsAreStillAskedForARequiredField(): void
    {
        $contract = $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns'];
        $address = $GLOBALS['TCA']['tx_academicpersons_domain_model_address']['columns'];

        $this->assertTrue($contract['position']['config']['required']);
        $this->assertSame(1, $contract['position']['config']['minitems']);
        $this->assertTrue($address['street']['config']['required']);
        $this->assertSame(1, $address['street']['config']['minitems']);
    }

    /**
     * The middle name lists `readonly` beside `frontendreadonly`, which keeps
     * locking the backend form as well.
     */
    #[Test]
    public function aFieldAlsoListingReadonlyStaysLockedInTheBackend(): void
    {
        $profile = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'];

        $this->assertTrue($this->validation('profile', 'middleName')->readOnly);
        $this->assertTrue($profile['middle_name']['config']['readOnly']);
    }
}
