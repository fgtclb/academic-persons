<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Backend\FormEngine;

use FGTCLB\AcademicPersons\Backend\FormEngine\ContractSelectScopeResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The parsing half of the resolver: what it makes of the page TSconfig it is handed and
 * of the value the edited record carries.
 *
 * Expanding the page list below its depth needs the page tree and is covered by the two
 * functional `ContractSelectStorageScopeTest` classes; every case here therefore leaves
 * `recursive` at its default, where no page is looked up.
 */
final class ContractSelectScopeResolverTest extends UnitTestCase
{
    /**
     * `$parameters['TSconfig']` is `null` when the field has no `itemsProcFunc.` page
     * TSconfig at all - from `AbstractItemProvider` on TYPO3 v12 and v13 alike.
     * Anything but an array has to mean "no restriction", because a restriction that is
     * guessed empties the select.
     *
     * @param array<string, mixed> $parameters
     */
    #[Test]
    #[DataProvider('parametersWithoutARestrictionProvider')]
    public function parametersWithoutARestrictionYieldNoPageIds(array $parameters): void
    {
        $this->assertSame([], (new ContractSelectScopeResolver())->resolve($parameters)->storagePageIds);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function parametersWithoutARestrictionProvider(): array
    {
        return [
            'no TSconfig key at all' => [[]],
            'TSconfig is null' => [['TSconfig' => null]],
            'TSconfig is not an array' => [['TSconfig' => 'storagePids = 20']],
            'TSconfig has no storagePids' => [['TSconfig' => ['recursive' => '2']]],
            'storagePids is empty' => [['TSconfig' => ['storagePids' => '']]],
            'storagePids is a list of separators' => [['TSconfig' => ['storagePids' => ' , , ']]],
            'storagePids is page 0' => [['TSconfig' => ['storagePids' => '0']]],
            'storagePids is negative' => [['TSconfig' => ['storagePids' => '-5']]],
        ];
    }

    #[Test]
    public function aPageListIsParsedWithoutItsDuplicatesAndBlanks(): void
    {
        $scope = (new ContractSelectScopeResolver())->resolve([
            'TSconfig' => ['storagePids' => ' 20 , 30,20, ,0,-1,30 '],
        ]);

        $this->assertSame([20, 30], $scope->storagePageIds);
    }

    /**
     * The value of the edited field is a comma separated list for the FlexForm element
     * and a single uid for the TCA column, and both arrive as the raw database value.
     *
     * @param mixed $value
     * @param int[] $expected
     */
    #[Test]
    #[DataProvider('referencedValueProvider')]
    public function theReferencedUidsAreReadFromTheEditedRow($value, array $expected): void
    {
        $scope = (new ContractSelectScopeResolver())->resolve([
            'TSconfig' => ['storagePids' => '20'],
            'field' => 'contract',
            'row' => ['uid' => 1, 'contract' => $value],
        ]);

        $this->assertSame($expected, $scope->alwaysIncludeUids);
    }

    /**
     * @return array<string, array{0: mixed, 1: int[]}>
     */
    public static function referencedValueProvider(): array
    {
        return [
            'a single uid as a string' => ['7', [7]],
            'a single uid as an integer' => [7, [7]],
            'a comma separated list' => ['7,9', [7, 9]],
            'a list with duplicates and blanks' => ['7, ,9,7', [7, 9]],
            'the TCA default of the single select' => ['0', []],
            'an unset value' => ['', []],
            'a value that is not scalar' => [['7', '9'], []],
        ];
    }

    #[Test]
    public function aRowWithoutTheEditedFieldReferencesNothing(): void
    {
        $scope = (new ContractSelectScopeResolver())->resolve([
            'TSconfig' => ['storagePids' => '20'],
            'field' => 'contract',
            // What a `command: new` compile hands over: the field has no value yet.
            'row' => ['uid' => 1],
        ]);

        $this->assertSame([], $scope->alwaysIncludeUids);
    }

    #[Test]
    public function aDepthOfZeroLeavesTheListedPagesAlone(): void
    {
        $scope = (new ContractSelectScopeResolver())->resolve([
            'TSconfig' => ['storagePids' => '20,30', 'recursive' => '0'],
        ]);

        $this->assertSame([20, 30], $scope->storagePageIds);
    }

    /**
     * A negative depth is clamped to `0` rather than passed on, so no page is looked up
     * and this case stays a unit test. The upper bound of the clamp needs the page tree
     * and is left to the functional coverage.
     */
    #[Test]
    public function aNegativeDepthIsClampedToNone(): void
    {
        $scope = (new ContractSelectScopeResolver())->resolve([
            'TSconfig' => ['storagePids' => '20', 'recursive' => '-1'],
        ]);

        $this->assertSame([20], $scope->storagePageIds);
    }
}
