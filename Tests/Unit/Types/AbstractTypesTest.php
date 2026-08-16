<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Types;

use FGTCLB\AcademicPersons\Types\AbstractTypes;
use FGTCLB\AcademicPersons\Types\EmailAddressTypes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `AbstractTypes` turns one extension configuration string into the item list a TCA
 * `select` offers for `type` columns of `tx_academicpersons_domain_model_profile_*`
 * records. The parser is the whole class - the three subclasses only name a
 * configuration path - so it is covered here through `EmailAddressTypes`, and the
 * paths are covered in `TypesTest`.
 *
 * What is stored in a record is the array *key*, so a change in how a configured
 * entry is split into key and label silently repoints existing records.
 */
final class AbstractTypesTest extends UnitTestCase
{
    /**
     * @param array<string, string> $expected
     */
    #[Test]
    #[DataProvider('configuredTypeStrings')]
    public function aConfigurationStringBecomesAnItemList(string $configured, array $expected): void
    {
        $this->assertSame($expected, $this->subject($configured)->getAll());
    }

    /**
     * The last four cases are not what an integrator means to configure, but the parser
     * has no validation in front of it, so they document what reaches the item list.
     *
     * @return array<string, array{0: string, 1: array<string, string>}>
     */
    public static function configuredTypeStrings(): array
    {
        return [
            'the shipped default' => [
                'private=Private,business=Business',
                ['private' => 'Private', 'business' => 'Business'],
            ],
            'an entry without a label is its own label' => [
                'private,business',
                ['private' => 'private', 'business' => 'business'],
            ],
            'labelled and unlabelled entries mix' => [
                'private=Private,business',
                ['private' => 'Private', 'business' => 'business'],
            ],
            'whitespace around both separators is dropped' => [
                "  private = Private ,\n business = Business  ",
                ['private' => 'Private', 'business' => 'Business'],
            ],
            'a label may contain an equals sign' => [
                'private=Private=ish',
                ['private' => 'Private=ish'],
            ],
            'an LLL reference is a label like any other' => [
                'private=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:type.private',
                ['private' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:type.private'],
            ],
            'a repeated value keeps the last label' => [
                'private=First,private=Second',
                ['private' => 'Second'],
            ],
            'an empty configuration produces one empty item' => [
                '',
                ['' => ''],
            ],
            'a trailing comma produces an empty item' => [
                'private=Private,',
                ['private' => 'Private', '' => ''],
            ],
            'a gap between two entries produces an empty item' => [
                'private=Private,,business=Business',
                ['private' => 'Private', '' => '', 'business' => 'Business'],
            ],
        ];
    }

    /**
     * The item list is read straight from the extension configuration on every
     * instantiation - there is no runtime cache and no `$GLOBALS` fallback - so an
     * integrator's change takes effect without a cache flush.
     */
    #[Test]
    public function theExtensionConfigurationIsAskedOncePerInstance(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->expects($this->once())
            ->method('get')
            ->with('academic_persons', 'types/emailAddressTypes')
            ->willReturn('private=Private');

        $subject = new EmailAddressTypes($extensionConfiguration);

        $this->assertSame(['private' => 'Private'], $subject->getAll());
        $this->assertSame(['private' => 'Private'], $subject->getAll());
    }

    /**
     * `loadTypesByExtensionConfigurationProperty()` returns early once anything was
     * loaded. None of the three shipped subclasses hits that - each one loads a single
     * property - but a subclass merging two properties would silently get only the
     * first one, with no error to point at it. Pinned here so the trap is visible if
     * such a subclass is ever written.
     */
    #[Test]
    public function aSecondPropertyIsSilentlyIgnored(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturnMap([
            ['academic_persons', 'types/emailAddressTypes', 'private=Private'],
            ['academic_persons', 'types/phoneNumberTypes', 'mobile=Mobile'],
        ]);

        $subject = new class ($extensionConfiguration) extends AbstractTypes {
            protected function initialize(): void
            {
                $this->loadTypesByExtensionConfigurationProperty('types/emailAddressTypes');
                $this->loadTypesByExtensionConfigurationProperty('types/phoneNumberTypes');
            }
        };

        $this->assertSame(['private' => 'Private'], $subject->getAll());
    }

    private function subject(string $configured): EmailAddressTypes
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn($configured);

        return new EmailAddressTypes($extensionConfiguration);
    }
}
