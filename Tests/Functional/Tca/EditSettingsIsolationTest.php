<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The section graph of `Settings.yaml` reaches the backend: every TCA file of
 * this extension merges the set of its own section, so the flags an editor
 * sees in the frontend are the flags the backend record editor enforces. The
 * editing extension is deliberately not loaded here - the backend half applies
 * without it.
 */
final class EditSettingsIsolationTest extends AbstractAcademicPersonsTestCase
{
    /**
     * A document section addresses the records of its type only, through
     * `columnsOverrides`, and the range of the year columns stays untouched
     * by it.
     */
    #[Test]
    public function centralDocumentValidationIsAppliedToBackendTca(): void
    {
        $table = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information'];

        $this->assertArrayHasKey('columnsOverrides', $table['types']['cooperation']);
        $overrides = $table['types']['cooperation']['columnsOverrides'];
        $this->assertTrue($overrides['title']['config']['required']);
        $this->assertTrue($overrides['year']['config']['required']);
        $this->assertFalse($overrides['year_start']['config']['required']);
        $this->assertFalse($overrides['year_end']['config']['required']);
        $this->assertArrayNotHasKey('required', $table['columns']['year']['config']);
        $this->assertSame('number', $table['columns']['year']['config']['type']);
        $this->assertSame('integer', $table['columns']['year']['config']['format']);
        $this->assertSame(['lower' => 0, 'upper' => 9999], $table['columns']['year']['config']['range']);
        $this->assertTrue($table['columns']['year']['config']['nullable']);
    }

    /**
     * The profile table merges every profile section plus the direct special
     * fields; the contact tables merge their own contact section each. The
     * name fields are locked (`readonly`, `disabled`) and therefore not
     * required, whatever else their flag list says.
     */
    #[Test]
    public function centralProfileAndContactValidationIsAppliedToBackendTca(): void
    {
        $profile = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'];
        $this->assertTrue($profile['gender']['config']['required']);
        $this->assertSame(1, $profile['gender']['config']['minitems']);
        $this->assertFalse($profile['first_name']['config']['required']);
        $this->assertTrue($profile['first_name']['config']['readOnly']);
        $this->assertTrue($profile['middle_name']['config']['readOnly']);
        $this->assertTrue($profile['last_name']['config']['readOnly']);
        $this->assertFalse($profile['skip_sync']['config']['readOnly']);
        $this->assertSame('check', $profile['skip_sync']['config']['type']);

        $email = $GLOBALS['TCA']['tx_academicpersons_domain_model_email']['columns']['email']['config'];
        $this->assertSame('email', $email['type']);
        $this->assertTrue($email['required']);
        $this->assertFalse($GLOBALS['TCA']['tx_academicpersons_domain_model_email']['columns']['type']['config']['required']);

        $address = $GLOBALS['TCA']['tx_academicpersons_domain_model_address']['columns'];
        $this->assertTrue($address['street']['config']['required']);
        $this->assertTrue($address['street_number']['config']['required']);
        $this->assertTrue($address['city']['config']['required']);
        $this->assertTrue($address['zip']['config']['required']);
        $this->assertTrue($address['country']['config']['required']);
        $this->assertFalse($address['additional']['config']['required']);

        $phone = $GLOBALS['TCA']['tx_academicpersons_domain_model_phone_number']['columns'];
        $this->assertTrue($phone['phone_number']['config']['required']);
        $this->assertFalse($phone['type']['config']['required']);
    }

    /**
     * The contract table merges the contract fields, which the `contracts`
     * document section validates against.
     */
    #[Test]
    public function contractFieldValidationIsAppliedToTheContractTable(): void
    {
        $contract = $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns'];

        $this->assertTrue($contract['position']['config']['required']);
        $this->assertTrue($contract['valid_from']['config']['required']);
        $this->assertFalse($contract['valid_to']['config']['required']);
        $this->assertFalse($contract['room']['config']['required']);
        $this->assertSame('datetime', $contract['valid_from']['config']['type']);
    }
}
