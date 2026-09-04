<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * A site package still shipping the pre-3.0 `validations` map reaches the
 * backend through the runtime overlay: the fixture is the override example
 * of the 2.x manual, which unlocks the profile names by not listing them, and
 * the TCA the six table files build reflects that - not the shipped defaults
 * EditSettingsIsolationTest pins for an installation without the override.
 */
final class LegacySettingsOverlayTest extends AbstractAcademicPersonsTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'tests/test-legacy-settings',
    ];

    /**
     * The legacy `profile` set does not list the three name fields, so their
     * shipped `readonly` and `disabled` flags are gone and the columns are
     * writable; `website` is listed as `required` and is.
     */
    #[Test]
    public function aLegacyProfileSetUnlocksTheNameFieldsInTheBackendTca(): void
    {
        $profile = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'];

        $this->assertFalse($profile['first_name']['config']['readOnly']);
        $this->assertFalse($profile['middle_name']['config']['readOnly']);
        $this->assertFalse($profile['last_name']['config']['readOnly']);
        $this->assertFalse($profile['first_name']['config']['required']);
        $this->assertTrue($profile['website']['config']['required']);
        $this->assertSame('input', $profile['website']['config']['type']);
        $this->assertFalse($profile['gender']['config']['required'], 'Not listed in the legacy set');
    }

    /**
     * The other five legacy sets reach their tables the same way: a listed
     * property carries its listed flags, an unlisted one is unconfigured,
     * and the column types the TCA files declare are untouched.
     */
    #[Test]
    public function theLegacyContactContractAndTimelineSetsReachTheirTables(): void
    {
        $email = $GLOBALS['TCA']['tx_academicpersons_domain_model_email']['columns'];
        $this->assertSame('email', $email['email']['config']['type']);
        $this->assertTrue($email['email']['config']['required']);
        $this->assertFalse($email['type']['config']['required']);

        $phone = $GLOBALS['TCA']['tx_academicpersons_domain_model_phone_number']['columns'];
        $this->assertTrue($phone['phone_number']['config']['required']);
        $this->assertSame('input', $phone['phone_number']['config']['type']);

        $address = $GLOBALS['TCA']['tx_academicpersons_domain_model_address']['columns'];
        $this->assertTrue($address['street']['config']['required']);
        $this->assertFalse($address['street_number']['config']['required']);
        $this->assertFalse($address['zip']['config']['required']);
        $this->assertFalse($address['city']['config']['required']);
        $this->assertFalse($address['country']['config']['required']);

        $contract = $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns'];
        $this->assertTrue($contract['position']['config']['required']);
        $this->assertFalse($contract['valid_from']['config']['required']);
        $this->assertSame('datetime', $contract['valid_from']['config']['type']);

        $information = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information'];
        foreach (['cooperation', 'lecture', 'membership', 'press_media', 'publication', 'scientific_research', 'curriculum_vitae'] as $type) {
            $overrides = $information['types'][$type]['columnsOverrides'];
            $this->assertTrue($overrides['title']['config']['required'], $type);
            // The legacy set does not list the year, so it loses the shipped
            // `required` and `number` flags and overrides nothing at all.
            $this->assertArrayNotHasKey('year', $overrides, $type . ': the year was not listed');
        }
        $this->assertSame('number', $information['columns']['year']['config']['type']);
        $this->assertSame(['lower' => 0, 'upper' => 9999], $information['columns']['year']['config']['range']);
    }
}
