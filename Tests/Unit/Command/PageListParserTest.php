<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Command;

use FGTCLB\AcademicPersons\Command\PageListParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The page list of `--include-pids` and `--exclude-pids`, read the same way by the
 * create, update and cleanup command of the profiles (ACE-870).
 */
final class PageListParserTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{mixed, list<int>}>
     */
    public static function validPageLists(): \Generator
    {
        yield 'option not given' => [null, []];
        yield 'empty string' => ['', []];
        yield 'one uid' => ['100', [100]];
        yield 'several uids' => ['100,110', [100, 110]];
        yield 'spaces around the commas' => [' 100 , 110 ', [100, 110]];
        yield 'empty parts' => ['100,,110,', [100, 110]];
        yield 'a uid given twice counts once' => ['100,110,100', [100, 110]];
        yield 'page 0' => ['0', [0]];
    }

    /**
     * @param list<int> $expected
     */
    #[DataProvider('validPageLists')]
    #[Test]
    public function aValidPageListIsReadAsItsUids(mixed $value, array $expected): void
    {
        $this->assertSame($expected, (new PageListParser())->parse($value));
    }

    /**
     * @return \Generator<string, array{mixed}>
     */
    public static function invalidPageLists(): \Generator
    {
        yield 'no number at all' => ['abc'];
        yield 'one part no number' => ['100,abc'];
        yield 'a negative uid' => ['-100'];
        yield 'wrong separator' => ['100;110'];
        yield 'a number with a fraction' => ['100.5'];
        yield 'a number followed by text' => ['100abc'];
        yield 'not a string' => [['100']];
    }

    /**
     * `GeneralUtility::intExplode()`, which the create and update command used before,
     * read every one of these as some list of uids, page 0 for `abc`.
     */
    #[DataProvider('invalidPageLists')]
    #[Test]
    public function aPageListWithAPartThatIsNoUidIsRefused(mixed $value): void
    {
        $this->assertNull((new PageListParser())->parse($value));
    }
}
