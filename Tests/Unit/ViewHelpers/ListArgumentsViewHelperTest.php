<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\ViewHelpers;

use FGTCLB\AcademicPersons\ViewHelpers\ListArgumentsViewHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The arguments of one navigation link of the profile list: the active list arguments,
 * changed only by what the link is responsible for. How the links use it is covered by
 * `AcademicPersonsListNavigationStateTest`.
 */
final class ListArgumentsViewHelperTest extends UnitTestCase
{
    /**
     * @param array<string, mixed> $arguments
     * @return array<array-key, mixed>
     */
    private function render(array $arguments): array
    {
        $subject = new ListArgumentsViewHelper();
        $subject->setArguments(array_replace(['arguments' => [], 'overrides' => [], 'remove' => ''], $arguments));

        return $subject->render();
    }

    /**
     * @return \Generator<string, array{0: array<string, mixed>, 1: array<string, mixed>}>
     */
    public static function argumentsDataProvider(): \Generator
    {
        yield 'no active arguments: the overrides alone' => [
            ['overrides' => ['currentPage' => 2]],
            ['currentPage' => 2],
        ];
        yield 'an override replaces the active value and keeps the others' => [
            ['arguments' => ['viewMode' => 'table', 'currentPage' => 2], 'overrides' => ['currentPage' => 3]],
            ['viewMode' => 'table', 'currentPage' => 3],
        ];
        yield 'an override adds a value that is not active' => [
            ['arguments' => ['viewMode' => 'table'], 'overrides' => ['currentPage' => 2]],
            ['viewMode' => 'table', 'currentPage' => 2],
        ];
        yield 'an empty override is kept, not dropped' => [
            ['arguments' => ['alphabetFilter' => 'b'], 'overrides' => ['alphabetFilter' => '']],
            ['alphabetFilter' => ''],
        ];
        yield 'a removed key is dropped, the others are kept' => [
            ['arguments' => ['viewMode' => 'table', 'currentPage' => 2], 'overrides' => ['alphabetFilter' => 'b'], 'remove' => 'currentPage'],
            ['viewMode' => 'table', 'alphabetFilter' => 'b'],
        ];
        yield 'several removed keys, blanks around them ignored' => [
            ['arguments' => ['viewMode' => 'table', 'currentPage' => 2, 'alphabetFilter' => 'b'], 'remove' => ' currentPage , alphabetFilter '],
            ['viewMode' => 'table'],
        ];
        yield 'a key both overridden and removed is removed' => [
            ['arguments' => ['currentPage' => 2], 'overrides' => ['currentPage' => 3], 'remove' => 'currentPage'],
            [],
        ];
        yield 'removing a key that is not there changes nothing' => [
            ['arguments' => ['viewMode' => 'table'], 'remove' => 'currentPage'],
            ['viewMode' => 'table'],
        ];
        yield 'nothing active, nothing to change: an empty set' => [
            [],
            [],
        ];
        yield 'active arguments a template did not pass: an empty set' => [
            ['arguments' => null, 'overrides' => null, 'remove' => null],
            [],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $expected
     */
    #[DataProvider('argumentsDataProvider')]
    #[Test]
    public function linkArgumentsAreTheActiveArgumentsWithTheLinksOwnChange(array $arguments, array $expected): void
    {
        $this->assertSame($expected, $this->render($arguments));
    }
}
