<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\Validation;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `Validation` carries no behaviour beyond `__set_state()` - it is a readonly data
 * object whose properties are read directly. That one method is not decoration though:
 * `AcademicPersonsSettingsFactory` caches the settings as `return <var_export>;` and
 * restores them with `require`, so a constructor property that `__set_state()` does not
 * pass on is lost on every request but the first, where nothing points at it.
 */
final class ValidationTest extends UnitTestCase
{
    #[Test]
    public function everyPropertySurvivesTheVarExportRoundTrip(): void
    {
        $subject = new Validation(
            identifier: 'firstName',
            fieldName: 'first_name',
            required: true,
            disabled: false,
            readOnly: true,
            validatorClassNames: [NotEmptyValidator::class, StringLengthValidator::class],
            tcaConfig: ['type' => 'input', 'max' => 60, 'eval' => 'trim'],
            inputType: 'text',
        );

        $restored = eval('return ' . var_export($subject, true) . ';');

        $this->assertInstanceOf(Validation::class, $restored);
        $this->assertEquals($subject, $restored);
        $this->assertSame('firstName', $restored->identifier);
        $this->assertSame('first_name', $restored->fieldName);
        $this->assertTrue($restored->required);
        $this->assertFalse($restored->disabled);
        $this->assertTrue($restored->readOnly);
        $this->assertSame(
            [NotEmptyValidator::class, StringLengthValidator::class],
            $restored->validatorClassNames,
        );
        $this->assertSame(['type' => 'input', 'max' => 60, 'eval' => 'trim'], $restored->tcaConfig);
        $this->assertSame('text', $restored->inputType);
    }

    /**
     * `inputType` is the only constructor argument with a default, and `__set_state()`
     * reads it as a required array key. That holds as long as the array comes from
     * `var_export()` of a real instance - this pins that it does, because a validation
     * built without an `inputType` is the common case.
     */
    #[Test]
    public function aDefaultedInputTypeIsStillExportedAndRestored(): void
    {
        $subject = new Validation(
            identifier: 'firstName',
            fieldName: 'first_name',
            required: false,
            disabled: false,
            readOnly: false,
            validatorClassNames: [],
            tcaConfig: [],
        );

        $restored = eval('return ' . var_export($subject, true) . ';');

        $this->assertInstanceOf(Validation::class, $restored);
        $this->assertSame('', $restored->inputType);
    }
}
