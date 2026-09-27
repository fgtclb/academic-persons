<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use FGTCLB\AcademicPersons\Settings\Validation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * How the settings factory turns a flag list of the `validations` map into the
 * `Validation` both editing contexts read: `readOnly` and `required` for the
 * frontend editor, `tcaConfig` for the backend form. The fixture file is the only
 * active package's settings, read through an empty cache.
 */
final class AcademicPersonsSettingsFactoryTest extends UnitTestCase
{
    private function settings(): AcademicPersonsSettings
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn(false);
        $package = $this->createMock(PackageInterface::class);
        $package->method('getPackagePath')->willReturn(__DIR__ . '/Fixtures/FrontendReadOnly/');
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn([$package]);

        return (new AcademicPersonsSettingsFactory($cache, $packageManager))->get();
    }

    /**
     * @return \Generator<string, array{property: string, expected: Validation}>
     */
    public static function flagDataSets(): \Generator
    {
        yield 'required: NotEmptyValidator, and required plus minitems in TCA' => [
            'property' => 'required',
            'expected' => new Validation(
                identifier: 'required',
                fieldName: 'required',
                required: true,
                disabled: false,
                readOnly: false,
                validatorClassNames: [NotEmptyValidator::class],
                tcaConfig: ['readOnly' => false, 'required' => true, 'minitems' => 1],
                inputType: 'text',
            ),
        ];
        yield 'frontendreadonly: read-only in the frontend, the TCA fragment stays unlocked' => [
            'property' => 'frontendReadOnly',
            'expected' => new Validation(
                identifier: 'frontendReadOnly',
                fieldName: 'frontend_read_only',
                required: false,
                disabled: false,
                readOnly: true,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => false, 'required' => false],
                inputType: 'text',
            ),
        ];
        yield 'frontendreadonly is matched case-insensitively' => [
            'property' => 'mixedCase',
            'expected' => new Validation(
                identifier: 'mixedCase',
                fieldName: 'mixed_case',
                required: false,
                disabled: false,
                readOnly: true,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => false, 'required' => false],
                inputType: 'text',
            ),
        ];
        yield 'frontendreadonly with readonly: readonly still locks the TCA column' => [
            'property' => 'withReadOnly',
            'expected' => new Validation(
                identifier: 'withReadOnly',
                fieldName: 'with_read_only',
                required: false,
                disabled: false,
                readOnly: true,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => true, 'required' => false],
                inputType: 'text',
            ),
        ];
        yield 'frontendreadonly with disabled: disabled still locks the TCA column' => [
            'property' => 'withDisabled',
            'expected' => new Validation(
                identifier: 'withDisabled',
                fieldName: 'with_disabled',
                required: false,
                disabled: true,
                readOnly: true,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => true, 'required' => false],
                inputType: 'text',
            ),
        ];
        yield 'frontendreadonly with required: no frontend validator, TCA keeps required and minitems' => [
            'property' => 'withRequired',
            'expected' => new Validation(
                identifier: 'withRequired',
                fieldName: 'with_required',
                required: false,
                disabled: false,
                readOnly: true,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => false, 'required' => true, 'minitems' => 1],
                inputType: 'text',
            ),
        ];
        yield 'frontendreadonly with email: the email validator and TCA type stay' => [
            'property' => 'withEmail',
            'expected' => new Validation(
                identifier: 'withEmail',
                fieldName: 'with_email',
                required: false,
                disabled: false,
                readOnly: true,
                validatorClassNames: [EmailAddressValidator::class],
                tcaConfig: ['readOnly' => false, 'required' => false, 'type' => 'email'],
                inputType: 'email',
            ),
        ];
    }

    #[DataProvider('flagDataSets')]
    #[Test]
    public function theFlagsOfAPropertyAreNormalized(string $property, Validation $expected): void
    {
        $this->assertEquals($expected, $this->settings()->getValidationSet('testSet')?->get($property));
    }
}
