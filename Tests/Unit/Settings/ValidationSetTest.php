<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\Validation;
use FGTCLB\AcademicPersons\Settings\ValidationSet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * A validation set is the per-table bundle the Extbase validation and the TCA overrides
 * ask a single field of. `get()` has to answer for a field nobody configured, because
 * the caller iterates over the model's fields, not over the configured ones.
 */
final class ValidationSetTest extends UnitTestCase
{
    #[Test]
    public function aRegisteredValidationIsReturned(): void
    {
        $validation = $this->validation('first_name');

        $subject = new ValidationSet(identifier: 'profile', validations: ['firstName' => $validation]);

        $this->assertSame($validation, $subject->get('firstName'));
    }

    /**
     * The lookup key is the key the validation is registered under, not its `fieldName`
     * or its own `identifier`. Those need not be equal, and this is the one place where
     * mixing them up produces a silent null instead of an error.
     */
    #[Test]
    public function theLookupUsesTheRegistrationKeyRatherThanTheFieldName(): void
    {
        $subject = new ValidationSet(
            identifier: 'profile',
            validations: ['firstName' => $this->validation('first_name')],
        );

        $this->assertNull($subject->get('first_name'));
    }

    #[Test]
    public function anUnknownValidationIsNull(): void
    {
        $subject = new ValidationSet(identifier: 'profile', validations: []);

        $this->assertNull($subject->get('firstName'));
    }

    /**
     * Restored from the core cache the factory writes as `return <var_export>;`, which
     * means the nested `Validation` objects have to make the round trip together with
     * the keys they are registered under - a re-indexed array would make every `get()`
     * miss.
     */
    #[Test]
    public function theRegistrationKeysSurviveTheVarExportRoundTrip(): void
    {
        $subject = new ValidationSet(
            identifier: 'profile',
            validations: [
                'firstName' => $this->validation('first_name'),
                'lastName' => $this->validation('last_name'),
            ],
        );

        $restored = eval('return ' . var_export($subject, true) . ';');

        $this->assertInstanceOf(ValidationSet::class, $restored);
        $this->assertEquals($subject, $restored);
        $this->assertSame(['firstName', 'lastName'], array_keys($restored->validations));
    }

    private function validation(string $fieldName): Validation
    {
        return new Validation(
            identifier: 'validation of ' . $fieldName,
            fieldName: $fieldName,
            required: true,
            disabled: false,
            readOnly: false,
            validatorClassNames: [],
            tcaConfig: [],
        );
    }
}
