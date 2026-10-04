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
 * rename has to be made twice instead of silently agreeing with itself. Five of the nine are
 * drawn by a shared file of EXT:academic_base, the identifier is still this extension's own.
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
            'tx-academicpersons-record-address',
            'tx-academicpersons-record-contract',
            'tx-academicpersons-record-email',
            'tx-academicpersons-record-function-type',
            'tx-academicpersons-record-location',
            'tx-academicpersons-record-organisational-unit',
            'tx-academicpersons-record-phone-number',
            'tx-academicpersons-record-profile',
            'tx-academicpersons-record-profile-information',
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
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function tableIconIdentifiers(): \Generator
    {
        foreach (self::recordIconIdentifiers() as [$identifier]) {
            $table = 'tx_academicpersons_domain_model_' . str_replace(
                '-',
                '_',
                substr($identifier, strlen('tx-academicpersons-record-')),
            );
            yield $table => [$table, $identifier];
        }
    }

    /**
     * The identifiers are no longer the table names, so the one place that binds a table to
     * its icon is `ctrl.typeicon_classes`. A misspelt identifier there is no error: the
     * backend renders `default-not-found`, and the TCA walk below skips an identifier that is
     * not registered, as it skips one whose file lives in EXT:academic_base.
     */
    #[Test]
    #[DataProvider('tableIconIdentifiers')]
    public function recordTypeNamesItsIconIdentifier(string $table, string $identifier): void
    {
        $this->assertSame($identifier, $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['default'] ?? null);
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
