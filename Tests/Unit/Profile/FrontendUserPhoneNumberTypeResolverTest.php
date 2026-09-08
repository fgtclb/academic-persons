<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Profile;

use FGTCLB\AcademicPersons\Profile\FrontendUserPhoneNumberTypeResolver;
use FGTCLB\AcademicPersons\Types\PhoneNumberTypes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FrontendUserPhoneNumberTypeResolverTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{array<string, string>, array<string, string>, string, string}>
     */
    public static function resolvedTypesDataProvider(): \Generator
    {
        yield 'missing options use the available business default' => [
            [],
            ['private' => 'Private', 'business' => 'Business', 'mobile' => 'Mobile'],
            'business',
            'business',
        ];
        yield 'telephone and fax can be configured independently' => [
            [
                'profile/feuser/telephoneNumberType' => 'mobile',
                'profile/feuser/faxNumberType' => 'private',
            ],
            ['private' => 'Private', 'business' => 'Business', 'mobile' => 'Mobile'],
            'mobile',
            'private',
        ];
        yield 'custom selectable values are accepted' => [
            [
                'profile/feuser/telephoneNumberType' => 'office',
                'profile/feuser/faxNumberType' => 'office',
            ],
            ['office' => 'Office'],
            'office',
            'office',
        ];
        yield 'unavailable configured values use the undefined type' => [
            [
                'profile/feuser/telephoneNumberType' => 'invalid',
                'profile/feuser/faxNumberType' => 'private',
            ],
            ['private' => 'Private', 'business' => 'Business'],
            '',
            'private',
        ];
        yield 'unavailable business default uses the undefined type' => [
            [],
            ['private' => 'Private'],
            '',
            '',
        ];
    }

    /**
     * ACE-365: the synchronisation used to write the literals `phone` and `fax`, which no
     * default installation offers as types, so every imported record carried a value the
     * backend select could not resolve. The rule that replaces them is expressed here:
     * whatever is configured, what leaves this resolver is either a type the installation
     * offers or the empty one - never the raw configured value.
     *
     * @param array<string, string> $configuration
     * @param array<string, string> $availableTypes
     */
    #[DataProvider('resolvedTypesDataProvider')]
    #[Test]
    public function resolvesConfiguredTypes(
        array $configuration,
        array $availableTypes,
        string $expectedTelephoneType,
        string $expectedFaxType,
    ): void {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration
            ->method('get')
            ->willReturnCallback(
                static function (string $extension, string $path) use ($configuration): string {
                    self::assertSame('academic_persons', $extension);
                    if (!array_key_exists($path, $configuration)) {
                        throw new ExtensionConfigurationPathDoesNotExistException();
                    }
                    return $configuration[$path];
                },
            );
        $phoneNumberTypes = $this->createMock(PhoneNumberTypes::class);
        $phoneNumberTypes->method('getAll')->willReturn($availableTypes);

        $subject = new FrontendUserPhoneNumberTypeResolver($extensionConfiguration, $phoneNumberTypes);

        $this->assertSame($expectedTelephoneType, $subject->getTelephoneNumberType());
        $this->assertSame($expectedFaxType, $subject->getFaxNumberType());
    }

    /**
     * The empty type is not an absence of a value: the TCA ships it as the first item of
     * the select, labelled `undefined`, and the column is `NOT NULL DEFAULT ''`. It is
     * therefore selectable, which is what makes it usable as the fallback and what stops
     * synchronisation from overwriting a record an editor deliberately left untyped.
     */
    #[Test]
    public function detectsSelectableStoredTypesIncludingTheUndefinedType(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $phoneNumberTypes = $this->createMock(PhoneNumberTypes::class);
        $phoneNumberTypes->method('getAll')->willReturn(['business' => 'Business']);
        $subject = new FrontendUserPhoneNumberTypeResolver($extensionConfiguration, $phoneNumberTypes);

        $this->assertTrue($subject->isSelectable(''));
        $this->assertTrue($subject->isSelectable('business'));
        $this->assertFalse($subject->isSelectable('fax'));
    }
}
