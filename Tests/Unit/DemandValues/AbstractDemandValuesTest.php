<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\DemandValues;

use FGTCLB\AcademicPersons\DemandValues\AbstractDemandValues;
use FGTCLB\AcademicPersons\DemandValues\SortByValues;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `AbstractDemandValues` parses one extension configuration string into the FlexForm
 * item list of the `groupBy` and `sortBy` fields. The keys of that list are more than
 * labels: `Domain\Repository\ProfileRepository::getOrderingsFromDemand()` uses them as
 * the allow list a submitted demand is checked against, and then as the Extbase
 * property name it orders by. A key that is not a property of `Domain\Model\Profile`
 * therefore reaches the persistence layer.
 *
 * The parser is the whole class - the two subclasses only name a configuration path -
 * so it is covered here through `SortByValues`, and the paths in `DemandValuesTest`.
 */
final class AbstractDemandValuesTest extends UnitTestCase
{
    /**
     * @param array<string, string> $expected
     */
    #[Test]
    #[DataProvider('configuredValueStrings')]
    public function aConfigurationStringBecomesAnItemList(string $configured, array $expected): void
    {
        $this->assertSame($expected, $this->subject($configured)->getAll());
    }

    /**
     * The last three cases are not what an integrator means to configure. They are
     * listed because nothing validates the string, and the empty key they produce is
     * also the value an unset `sortBy`/`groupBy` demand carries - so it passes the
     * `in_array()` allow list in `ProfileRepository` and orders by a property named ''.
     *
     * @return array<string, array{0: string, 1: array<string, string>}>
     */
    public static function configuredValueStrings(): array
    {
        return [
            'the shipped default shape - property name and LLL label' => [
                'firstName=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.first_name'
                    . ',lastName=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.last_name',
                [
                    'firstName' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.first_name',
                    'lastName' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.last_name',
                ],
            ],
            'an entry without a label is its own label' => [
                'firstName,lastName',
                ['firstName' => 'firstName', 'lastName' => 'lastName'],
            ],
            'labelled and unlabelled entries mix' => [
                'firstName=First name,lastName',
                ['firstName' => 'First name', 'lastName' => 'lastName'],
            ],
            'whitespace around both separators is dropped' => [
                "  firstName = First name ,\n lastName = Last name  ",
                ['firstName' => 'First name', 'lastName' => 'Last name'],
            ],
            'a label may contain an equals sign' => [
                'firstName=a=b',
                ['firstName' => 'a=b'],
            ],
            'a repeated value keeps the last label' => [
                'firstName=First,firstName=Second',
                ['firstName' => 'Second'],
            ],
            'an empty configuration produces one empty item' => [
                '',
                ['' => ''],
            ],
            'a trailing comma produces an empty item' => [
                'firstName=First name,',
                ['firstName' => 'First name', '' => ''],
            ],
            'a gap between two entries produces an empty item' => [
                'firstName=First,,lastName=Last',
                ['firstName' => 'First', '' => '', 'lastName' => 'Last'],
            ],
        ];
    }

    /**
     * `ProfileRepository` builds a fresh instance per query through
     * `GeneralUtility::makeInstance()`, so the extension configuration is read on every
     * list rendering. Nothing caches the parsed list on the instance beyond the
     * constructor either.
     */
    #[Test]
    public function theExtensionConfigurationIsAskedOncePerInstance(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->expects($this->once())
            ->method('get')
            ->with('academic_persons', 'demand/allowedSortByValues')
            ->willReturn('firstName=First name');

        $subject = new SortByValues($extensionConfiguration);

        $this->assertSame(['firstName' => 'First name'], $subject->getAll());
        $this->assertSame(['firstName' => 'First name'], $subject->getAll());
    }

    /**
     * `loadValuesByExtensionConfigurationProperty()` returns early once anything was
     * loaded. Neither shipped subclass hits that - each one loads a single property -
     * but a subclass merging two properties would silently get only the first one, with
     * no error to point at it. Pinned here so the trap is visible if such a subclass is
     * ever written.
     */
    #[Test]
    public function aSecondPropertyIsSilentlyIgnored(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturnMap([
            ['academic_persons', 'demand/allowedSortByValues', 'firstName=First name'],
            ['academic_persons', 'demand/allowedGroupByValues', 'lastNameAlpha=Last name'],
        ]);

        $subject = new class ($extensionConfiguration) extends AbstractDemandValues {
            protected function initialize(): void
            {
                $this->loadValuesByExtensionConfigurationProperty('demand/allowedSortByValues');
                $this->loadValuesByExtensionConfigurationProperty('demand/allowedGroupByValues');
            }
        };

        $this->assertSame(['firstName' => 'First name'], $subject->getAll());
    }

    private function subject(string $configured): SortByValues
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn($configured);

        return new SortByValues($extensionConfiguration);
    }
}
