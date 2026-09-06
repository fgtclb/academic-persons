<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Imaging;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Every identifier below is what a TCA record type resolves to, so it reaches the record
 * list, the page tree and FormEngine through the *default* markup. That markup has to be
 * the inlined file rather than an <img>, because an <img> is opaque to CSS and keeps the
 * ink of its file on the dark cards of a dark backend colour scheme (ACE-523).
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class RecordIconsTest extends AbstractAcademicPersonsTestCase
{
    use ColourSchemeAwareIconsTrait;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordIconIdentifiers(): \Generator
    {
        $identifiers = [
            'tx_academicpersons_domain_model_address',
            'tx_academicpersons_domain_model_contract',
            'tx_academicpersons_domain_model_email',
            'tx_academicpersons_domain_model_function_type',
            'tx_academicpersons_domain_model_location',
            'tx_academicpersons_domain_model_organisational_unit',
            'tx_academicpersons_domain_model_phone_number',
            'tx_academicpersons_domain_model_profile',
            'tx_academicpersons_domain_model_profile_information',
        ];
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsRegisteredWithTheColourSchemeAwareProvider(string $identifier): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsInlinedInBothMarkups(string $identifier): void
    {
        $this->assertIconIsInlinedInBothMarkups($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconMarkupFollowsTheTextColour(string $identifier): void
    {
        $this->assertIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function renderedRecordIconCarriesItsIdentifier(string $identifier): void
    {
        $this->assertRenderedIconCarriesItsIdentifier($identifier);
    }

    /**
     * The identifiers above are hand maintained, so they cannot catch a record icon that is
     * added later and never converted. This one is derived from the TCA and does.
     */
    #[Test]
    public function everyRecordTypeIconOfThisExtensionIsColourSchemeAware(): void
    {
        $this->assertEveryRecordTypeIconIsColourSchemeAware('academic_persons');
    }
}
