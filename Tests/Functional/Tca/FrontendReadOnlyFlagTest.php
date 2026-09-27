<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * A field listing `frontendreadonly` is locked for profile owners and stays
 * editable for backend editors. The fixture extension `test_frontend_readonly`
 * marks the profile title, the contract position and room and the address
 * street, plus the middle name together with `readonly`. The frontend half,
 * the editor refusing a submitted value, is covered in `academic_persons_edit`.
 */
final class FrontendReadOnlyFlagTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-frontend-readonly';
        parent::setUp();
    }

    #[Test]
    public function theFieldsAreReadOnlyForTheFrontendEditor(): void
    {
        $settings = $this->get(AcademicPersonsSettings::class);

        $this->assertTrue($settings->getProfileField('title')?->validation->readOnly);
        $this->assertTrue($settings->getContractField('position')?->validation->readOnly);
        $this->assertTrue($settings->getContractField('room')?->validation->readOnly, 'The mixed case spelling is not recognised.');
        $this->assertTrue($settings->getContractContactField('street')?->validation->readOnly);
    }

    /**
     * A read-only value cannot be corrected by its owner, so the frontend does
     * not ask for one. The backend still does.
     */
    #[Test]
    public function theFrontendEditorDoesNotRequireAFieldItShowsReadOnly(): void
    {
        $settings = $this->get(AcademicPersonsSettings::class);
        $position = $settings->getContractField('position');
        $street = $settings->getContractContactField('street');
        $this->assertNotNull($position);
        $this->assertNotNull($street);

        $this->assertFalse($position->validation->required);
        $this->assertSame([], $position->validation->validatorClassNames);
        $this->assertFalse($street->validation->required);
    }

    #[Test]
    public function backendEditorsKeepTheFieldsEditable(): void
    {
        $profile = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'];
        $contract = $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns'];
        $address = $GLOBALS['TCA']['tx_academicpersons_domain_model_address']['columns'];

        $this->assertFalse((bool)($profile['title']['config']['readOnly'] ?? false));
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

        $this->assertTrue($this->get(AcademicPersonsSettings::class)->getProfileField('middleName')?->validation->readOnly);
        $this->assertTrue($profile['middle_name']['config']['readOnly']);
    }
}
