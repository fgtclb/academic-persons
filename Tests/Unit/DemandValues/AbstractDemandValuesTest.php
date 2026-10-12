<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\DemandValues;

use FGTCLB\AcademicPersons\DemandValues\AbstractDemandValues;
use FGTCLB\AcademicPersons\DemandValues\SortByValues;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
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
     * The cases from the empty configuration on are not what an integrator means to
     * configure, but nothing validates the string. An empty value must never become an
     * item: it is also the value a list element on "none" demands, so it would pass the
     * `in_array()` allow list in `ProfileRepository` and order by a property named ''.
     * An entry with an empty side must not raise a warning either, since the list is
     * parsed on every list rendering and every backend form with the select.
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
            'a label may start with an equals sign' => [
                'firstName==First',
                ['firstName' => '=First'],
            ],
            'a repeated value keeps the last label' => [
                'firstName=First,firstName=Second',
                ['firstName' => 'Second'],
            ],
            'an empty configuration produces no item' => [
                '',
                [],
            ],
            'a configuration of separators and whitespace produces no item' => [
                ' , ,',
                [],
            ],
            'a trailing comma adds no item' => [
                'firstName=First name,',
                ['firstName' => 'First name'],
            ],
            'a gap between two entries adds no item' => [
                'firstName=First,,lastName=Last',
                ['firstName' => 'First', 'lastName' => 'Last'],
            ],
            'an entry with an empty label is labelled with its value' => [
                'firstName=,lastName = ',
                ['firstName' => 'firstName', 'lastName' => 'lastName'],
            ],
            'an entry with an empty value is dropped' => [
                '=First,lastName=Last',
                ['lastName' => 'Last'],
            ],
            'an entry of only the equals sign is dropped' => [
                'firstName=First, = ',
                ['firstName' => 'First'],
            ],
        ];
    }

    /**
     * `get()` restores a path that `ext_conf_template.txt` declares and throws one of the
     * two exceptions only for an extension or a path the template does not declare, and
     * it returns an array for a path that is the prefix of deeper options.
     * `ProfileRepository` reads the list on every list rendering, so each of these
     * degrades to no item: the list renders in uid order and the backend select offers
     * only "none".
     */
    #[Test]
    #[DataProvider('unreadableConfigurations')]
    public function anUnreadableConfigurationProducesNoItem(mixed $read): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturnCallback(
            static fn(): mixed => $read instanceof \Throwable ? throw $read : $read
        );

        $this->assertSame([], (new SortByValues($extensionConfiguration))->getAll());
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function unreadableConfigurations(): array
    {
        return [
            'the extension is not configured' => [new ExtensionConfigurationExtensionNotConfiguredException('not configured', 1)],
            'the path does not exist' => [new ExtensionConfigurationPathDoesNotExistException('no path', 2)],
            'the path holds deeper options' => [['allowedSortByValues' => 'firstName']],
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
