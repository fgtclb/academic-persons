<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationSet;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ProfileInformationType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The settings object is what `AcademicPersonsSettingsFactory` hands to the TCA files
 * and to the Extbase validation, and it is what `SettingsFileLoader` writes into the
 * core cache as `return <var_export>;`. Two things therefore have to hold: the
 * lookups must fail softly, because TCA files ask for identifiers that need not be
 * configured, and the object must survive `var_export()`/`require`, because that is
 * how it is restored on every request after the first.
 */
final class AcademicPersonsSettingsTest extends UnitTestCase
{
    #[Test]
    public function aRegisteredProfileInformationTypeIsReturned(): void
    {
        $profileInformationType = $this->profileInformationType('email');

        $subject = new AcademicPersonsSettings(
            profileInformationTypes: ['email' => $profileInformationType],
            validations: [],
            raw: [],
        );

        $this->assertSame($profileInformationType, $subject->getProfileInformationType('email'));
    }

    /**
     * TCA files ask by identifier for types an integrator may never have configured.
     * Returning null rather than raising is what lets them skip the column instead of
     * taking the TCA build down.
     */
    #[Test]
    public function anUnknownProfileInformationTypeIsNull(): void
    {
        $subject = new AcademicPersonsSettings(
            profileInformationTypes: ['email' => $this->profileInformationType('email')],
            validations: [],
            raw: [],
        );

        $this->assertNull($subject->getProfileInformationType('phone'));
    }

    #[Test]
    public function aRegisteredValidationSetIsReturned(): void
    {
        $validationSet = new ValidationSet(identifier: 'profile', validations: []);

        $subject = new AcademicPersonsSettings(
            profileInformationTypes: [],
            validations: ['profile' => $validationSet],
            raw: [],
        );

        $this->assertSame($validationSet, $subject->getValidationSet('profile'));
    }

    #[Test]
    public function anUnknownValidationSetIsNull(): void
    {
        $subject = new AcademicPersonsSettings(
            profileInformationTypes: [],
            validations: [],
            raw: [],
        );

        $this->assertNull($subject->getValidationSet('profile'));
    }

    /**
     * The fallback is the variant callers use when they need to ask a set for a field
     * unconditionally. It has to carry the *requested* identifier, not an empty one -
     * anything logging or comparing the returned set would otherwise attribute it to
     * the wrong table.
     */
    #[Test]
    public function anUnknownValidationSetFallsBackToAnEmptySetOfTheSameIdentifier(): void
    {
        $subject = new AcademicPersonsSettings(
            profileInformationTypes: [],
            validations: [],
            raw: [],
        );

        $fallback = $subject->getValidationSetWithFallback('profile');

        $this->assertSame('profile', $fallback->identifier);
        $this->assertSame([], $fallback->validations);
        $this->assertNull($fallback->get('first_name'));
    }

    #[Test]
    public function aRegisteredValidationSetIsNotReplacedByTheFallback(): void
    {
        $validationSet = new ValidationSet(identifier: 'profile', validations: []);

        $subject = new AcademicPersonsSettings(
            profileInformationTypes: [],
            validations: ['profile' => $validationSet],
            raw: [],
        );

        $this->assertSame($validationSet, $subject->getValidationSetWithFallback('profile'));
    }

    /**
     * `AcademicPersonsSettingsFactory` caches the settings as `return <var_export>;` in
     * a `PhpFrontend` and restores them with `require`, which is the only reason the
     * four `__set_state()` implementations exist. This exercises the whole nesting in
     * one go - a property added to any of them without being added to its
     * `__set_state()` breaks the cached request but not the uncached one, so it is a
     * defect that only shows on the second hit.
     */
    #[Test]
    public function theWholeObjectGraphSurvivesTheVarExportRoundTrip(): void
    {
        $subject = new AcademicPersonsSettings(
            profileInformationTypes: ['email' => $this->profileInformationType('email')],
            validations: [
                'profile' => new ValidationSet(
                    identifier: 'profile',
                    validations: ['firstName' => $this->validation('first_name', ['type' => 'input'])],
                ),
            ],
            raw: ['profileInformationTypes' => ['email' => ['fieldName' => 'email']]],
        );

        $restored = eval('return ' . var_export($subject, true) . ';');

        $this->assertInstanceOf(AcademicPersonsSettings::class, $restored);
        $this->assertEquals($subject, $restored);
        $this->assertNotSame($subject, $restored);
    }

    /**
     * @param array<string, mixed> $tcaConfig
     */
    private function validation(string $fieldName, array $tcaConfig): Validation
    {
        return new Validation(
            identifier: 'validation of ' . $fieldName,
            fieldName: $fieldName,
            required: false,
            disabled: false,
            readOnly: false,
            validatorClassNames: [],
            tcaConfig: $tcaConfig,
        );
    }

    private function profileInformationType(string $identifier): ProfileInformationType
    {
        return new ProfileInformationType(
            identifier: $identifier,
            fieldName: $identifier,
            type: 'string',
            label: 'Label of ' . $identifier,
        );
    }
}
