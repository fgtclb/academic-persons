<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\InlineParentPointerAssertionTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The inline children of this extension keep their parent when it is edited in a
 * workspace and published (ACE-907), see {@see InlineParentPointerAssertionTrait}.
 */
final class InlineParentPointerTest extends AbstractAcademicPersonsTestCase
{
    use InlineParentPointerAssertionTrait;

    #[Test]
    public function inlineChildrenKeepTheLiveParent(): void
    {
        $this->assertInlineParentPointersKeepTheLiveParent(['tx_academicpersons_'], [
            'tx_academicpersons_domain_model_contract.email_addresses -> tx_academicpersons_domain_model_email.contract',
            'tx_academicpersons_domain_model_contract.phone_numbers -> tx_academicpersons_domain_model_phone_number.contract',
            'tx_academicpersons_domain_model_contract.physical_addresses -> tx_academicpersons_domain_model_address.contract',
            'tx_academicpersons_domain_model_organisational_unit.contracts -> tx_academicpersons_domain_model_contract.organisational_unit',
            'tx_academicpersons_domain_model_profile.contracts -> tx_academicpersons_domain_model_contract.profile',
            'tx_academicpersons_domain_model_profile.cooperation -> tx_academicpersons_domain_model_profile_information.profile',
            'tx_academicpersons_domain_model_profile.lectures -> tx_academicpersons_domain_model_profile_information.profile',
            'tx_academicpersons_domain_model_profile.memberships -> tx_academicpersons_domain_model_profile_information.profile',
            'tx_academicpersons_domain_model_profile.press_media -> tx_academicpersons_domain_model_profile_information.profile',
            'tx_academicpersons_domain_model_profile.publications -> tx_academicpersons_domain_model_profile_information.profile',
            'tx_academicpersons_domain_model_profile.scientific_research -> tx_academicpersons_domain_model_profile_information.profile',
            'tx_academicpersons_domain_model_profile.vita -> tx_academicpersons_domain_model_profile_information.profile',
        ]);
    }
}
