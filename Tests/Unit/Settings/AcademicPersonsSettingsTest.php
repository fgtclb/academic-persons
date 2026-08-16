<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ProfileInformationType;
use FGTCLB\AcademicPersons\Settings\Validation;
use FGTCLB\AcademicPersons\Settings\ValidationSet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The settings object is what `AcademicPersonsSettingsFactory` hands to TCA overrides
 * and to the Extbase validation, and it is what the factory writes into the core cache
 * as `return <var_export>;`. Two things therefore have to hold: the lookups must fail
 * softly, because TCA files ask for identifiers that need not be configured, and the
 * object must survive `var_export()`/`require`, because that is how it is restored on
 * every request after the first.
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
     * The result is merged into `$GLOBALS['TCA']`, so the array key is the column name
     * - the `fieldName` of the validation, not the key it is registered under. Those
     * two are deliberately different here, because a set is keyed by validation
     * identifier and nothing enforces that it equals the column.
     */
    #[Test]
    public function theTcaConfigIsKeyedByTheFieldNameOfEachValidation(): void
    {
        $subject = new AcademicPersonsSettings(
            profileInformationTypes: [],
            validations: [
                'profile' => new ValidationSet(
                    identifier: 'profile',
                    validations: [
                        'firstName' => $this->validation('first_name', ['type' => 'input', 'required' => true]),
                        'lastName' => $this->validation('last_name', ['type' => 'input', 'max' => 60]),
                    ],
                ),
            ],
            raw: [],
        );

        $this->assertSame(
            [
                'columns' => [
                    'first_name' => ['config' => ['type' => 'input', 'required' => true]],
                    'last_name' => ['config' => ['type' => 'input', 'max' => 60]],
                ],
            ],
            $subject->getValidationTcaTableConfig('profile'),
        );
    }

    /**
     * A validation may only exist to attach an Extbase validator, in which case it has
     * nothing to say about TCA. Skipping it matters beyond tidiness: writing an empty
     * `config` into `$GLOBALS['TCA']` would drop the column's own configuration.
     */
    #[Test]
    public function aValidationWithoutTcaConfigContributesNothing(): void
    {
        $subject = new AcademicPersonsSettings(
            profileInformationTypes: [],
            validations: [
                'profile' => new ValidationSet(
                    identifier: 'profile',
                    validations: [
                        'firstName' => $this->validation('first_name', []),
                        'lastName' => $this->validation('last_name', ['type' => 'input']),
                    ],
                ),
            ],
            raw: [],
        );

        $this->assertSame(
            ['columns' => ['last_name' => ['config' => ['type' => 'input']]]],
            $subject->getValidationTcaTableConfig('profile'),
        );
    }

    /**
     * Not even an empty `columns` key: the caller merges the result into the table TCA,
     * and `['columns' => []]` is not the same neutral element as `[]` for every merge
     * strategy.
     */
    #[Test]
    public function aSetWithoutAnyTcaConfigProducesAnEmptyArray(): void
    {
        $subject = new AcademicPersonsSettings(
            profileInformationTypes: [],
            validations: [
                'profile' => new ValidationSet(
                    identifier: 'profile',
                    validations: ['firstName' => $this->validation('first_name', [])],
                ),
            ],
            raw: [],
        );

        $this->assertSame([], $subject->getValidationTcaTableConfig('profile'));
    }

    /**
     * TCA override files call this for every table the extension knows, whether or not
     * an integrator configured a validation set for it.
     */
    #[Test]
    public function anUnknownIdentifierProducesAnEmptyArray(): void
    {
        $subject = new AcademicPersonsSettings(
            profileInformationTypes: [],
            validations: [],
            raw: [],
        );

        $this->assertSame([], $subject->getValidationTcaTableConfig('profile'));
    }

    /**
     * Two validations of one set naming the same column silently collapse into the last
     * one - the earlier `config` is replaced, not merged. Pinned because the set is
     * keyed by validation identifier, so nothing stops a configuration from doing it.
     */
    #[Test]
    public function twoValidationsOnOneColumnKeepTheLastTcaConfig(): void
    {
        $subject = new AcademicPersonsSettings(
            profileInformationTypes: [],
            validations: [
                'profile' => new ValidationSet(
                    identifier: 'profile',
                    validations: [
                        'firstName' => $this->validation('first_name', ['type' => 'input']),
                        'firstNameAgain' => $this->validation('first_name', ['type' => 'text']),
                    ],
                ),
            ],
            raw: [],
        );

        $this->assertSame(
            ['columns' => ['first_name' => ['config' => ['type' => 'text']]]],
            $subject->getValidationTcaTableConfig('profile'),
        );
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
