<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\ProfileInformationType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `isValid()` is the gate a configured profile information type has to pass before the
 * factory keeps it, so what it accepts is what ends up in the cached settings - and
 * what it rejects is dropped silently, without a log line.
 */
final class ProfileInformationTypeTest extends UnitTestCase
{
    #[Test]
    #[DataProvider('profileInformationTypes')]
    public function onlyAFullyNamedTypeIsValid(
        string $identifier,
        string $fieldName,
        string $type,
        string $label,
        bool $expected,
    ): void {
        $subject = new ProfileInformationType(
            identifier: $identifier,
            fieldName: $fieldName,
            type: $type,
            label: $label,
        );

        $this->assertSame($expected, $subject->isValid());
    }

    /**
     * Three of the four properties are checked. `fieldName` is not - an entry without
     * one passes, which is the case worth pinning: it is the property that names the
     * database column, so an incomplete configuration is kept rather than dropped.
     *
     * Emptiness is also literal, not semantic: a blank is a value.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: bool}>
     */
    public static function profileInformationTypes(): array
    {
        return [
            'fully configured' => ['email', 'email', 'string', 'E-Mail', true],
            'without an identifier' => ['', 'email', 'string', 'E-Mail', false],
            'without a type' => ['email', 'email', '', 'E-Mail', false],
            'without a label' => ['email', 'email', 'string', '', false],
            'without anything' => ['', '', '', '', false],
            'without a field name' => ['email', '', 'string', 'E-Mail', true],
            'whitespace counts as configured' => ['email', 'email', ' ', ' ', true],
        ];
    }

    /**
     * Restored from the core cache the factory writes as `return <var_export>;`, so
     * every property has to make the round trip - a value lost here is a type that
     * silently differs between the first request and every later one.
     */
    #[Test]
    public function everyPropertySurvivesTheVarExportRoundTrip(): void
    {
        $subject = new ProfileInformationType(
            identifier: 'email',
            fieldName: 'email',
            type: 'string',
            label: 'E-Mail',
        );

        $restored = eval('return ' . var_export($subject, true) . ';');

        $this->assertInstanceOf(ProfileInformationType::class, $restored);
        $this->assertEquals($subject, $restored);
    }
}
