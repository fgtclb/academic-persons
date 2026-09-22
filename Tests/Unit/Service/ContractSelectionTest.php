<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Service;

use FGTCLB\AcademicPersons\Service\ContractDisplay;
use FGTCLB\AcademicPersons\Service\ContractSelection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Plugin settings arrive as the strings a FlexForm stores; the detail blocks as the scalars
 * YAML produces.
 */
final class ContractSelectionTest extends UnitTestCase
{
    #[Test]
    public function pluginSettingsWithoutTheContractOptionsSelectEveryContract(): void
    {
        $selection = ContractSelection::fromPluginSettings([
            'organisationalUnits' => '1,2',
            'functionTypes' => '3',
        ]);

        $this->assertEquals(new ContractSelection(), $selection);
    }

    #[Test]
    public function pluginSettingsAreReadFromTheFlexFormValues(): void
    {
        $selection = ContractSelection::fromPluginSettings([
            'organisationalUnits' => '1,2',
            'functionTypes' => '3',
            'contracts' => [
                'display' => 'first',
                'matchFilter' => '1',
                'onlyValid' => '1',
            ],
        ]);

        $this->assertSame(ContractDisplay::First, $selection->display);
        $this->assertSame([1, 2], $selection->organisationalUnits);
        $this->assertSame([3], $selection->functionTypes);
        $this->assertTrue($selection->onlyValid);
    }

    /**
     * The units and function types restrict the contracts only while "matching the plugin
     * filter" is on; otherwise they restrict the profiles alone, as before.
     */
    #[Test]
    public function thePluginFilterAppliesOnlyWithMatchFilter(): void
    {
        $selection = ContractSelection::fromPluginSettings([
            'organisationalUnits' => '1,2',
            'functionTypes' => '3',
            'contracts' => [
                'display' => 'all',
                'matchFilter' => '0',
                'onlyValid' => '0',
            ],
        ]);

        $this->assertSame([], $selection->organisationalUnits);
        $this->assertSame([], $selection->functionTypes);
        $this->assertFalse($selection->onlyValid);
    }

    #[Test]
    public function matchFilterWithoutARestrictionOfThePluginKeepsEveryContract(): void
    {
        $selection = ContractSelection::fromPluginSettings([
            'organisationalUnits' => '',
            'contracts' => ['matchFilter' => '1'],
        ]);

        $this->assertSame([], $selection->organisationalUnits);
        $this->assertSame([], $selection->functionTypes);
    }

    /**
     * @return \Generator<string, array{0: mixed, 1: ContractDisplay}>
     */
    public static function displayValueDataProvider(): \Generator
    {
        yield 'all' => ['all', ContractDisplay::All];
        yield 'first' => ['first', ContractDisplay::First];
        yield 'surrounding whitespace' => [' first ', ContractDisplay::First];
        yield 'empty' => ['', ContractDisplay::All];
        yield 'unknown' => ['last', ContractDisplay::All];
        yield 'wrong case' => ['First', ContractDisplay::All];
        yield 'not a string' => [1, ContractDisplay::All];
        yield 'missing' => [null, ContractDisplay::All];
    }

    #[Test]
    #[DataProvider('displayValueDataProvider')]
    public function anUnknownDisplayValueMeansAll(mixed $value, ContractDisplay $expected): void
    {
        $this->assertSame($expected, ContractSelection::fromPluginSettings(['contracts' => ['display' => $value]])->display);
        $this->assertSame($expected, ContractSelection::fromDetailBlock(['contracts' => $value])->display);
    }

    #[Test]
    public function aDetailBlockIsReadFromItsContractsAndOnlyValidKeys(): void
    {
        $selection = ContractSelection::fromDetailBlock([
            'special' => 'datasFromContracts',
            'contracts' => 'first',
            'onlyValid' => true,
        ]);

        $this->assertSame(ContractDisplay::First, $selection->display);
        $this->assertTrue($selection->onlyValid);
        $this->assertSame([], $selection->organisationalUnits);
        $this->assertSame([], $selection->functionTypes);
    }

    #[Test]
    public function aDetailBlockWithoutTheKeysSelectsEveryContract(): void
    {
        $this->assertEquals(new ContractSelection(), ContractSelection::fromDetailBlock(['special' => 'datasFromContracts']));
    }
}
