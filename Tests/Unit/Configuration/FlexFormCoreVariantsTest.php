<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Keeps the two core-version variants of the split FlexForms in sync.
 *
 * `List.xml` and `Detail.xml` exist once per supported TYPO3 major version
 * because their `settings.pageTitleFormat` value picker cannot be written in a
 * way both versions accept (ACE-560). That is a value picker *shape* difference
 * and nothing else: every other field, and the picker's own labels and values,
 * have to stay identical, or the two copies quietly grow apart - which is the
 * standing objection to any folder split.
 *
 * This is also the only guard that sees the shipped `Core14` file as it is
 * written. In a functional test TYPO3 v14 migrates a positional list to
 * `label`/`value` before any assertion can look at it, so a wrong `Core14` file
 * is invisible there.
 *
 * The comparison needs no core, no TCA and no container, so it fails in the
 * cheapest suite the repository has.
 */
final class FlexFormCoreVariantsTest extends UnitTestCase
{
    private const FLEX_FORM_PATH = __DIR__ . '/../../../Configuration/FlexForms';

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function splitFlexFormDataProvider(): \Generator
    {
        yield 'List' => ['List.xml'];
        yield 'Detail' => ['Detail.xml'];
    }

    #[Test]
    #[DataProvider('splitFlexFormDataProvider')]
    public function coreVariantsDifferOnlyInTheirValuePickers(string $fileName): void
    {
        $core13 = $this->readFlexForm('Core13/' . $fileName);
        $core14 = $this->readFlexForm('Core14/' . $fileName);

        $this->assertSame(
            $this->withoutValuePickers($core14),
            $this->withoutValuePickers($core13),
            sprintf(
                'The Core13 and Core14 variants of "%s" differ outside their value pickers.'
                . ' Everything but "config.valuePicker.items" has to stay identical.',
                $fileName,
            ),
        );
    }

    #[Test]
    #[DataProvider('splitFlexFormDataProvider')]
    public function coreVariantsOfferTheSameValuePickerItems(string $fileName): void
    {
        $core13 = $this->positionalValuePickerItems($this->readFlexForm('Core13/' . $fileName));
        $core14 = $this->associativeValuePickerItems($this->readFlexForm('Core14/' . $fileName));

        $this->assertNotSame([], $core13, sprintf('"Core13/%s" has no value picker items.', $fileName));
        $this->assertSame(
            $core14,
            $core13,
            sprintf(
                'The Core13 and Core14 variants of "%s" offer different value picker items.'
                . ' Only the key shape may differ, never the labels or the values.',
                $fileName,
            ),
        );
    }

    #[Test]
    #[DataProvider('splitFlexFormDataProvider')]
    public function core13VariantUsesPositionalValuePickerItems(string $fileName): void
    {
        $valuePickers = $this->valuePickers($this->readFlexForm('Core13/' . $fileName));

        $this->assertNotSame([], $valuePickers, sprintf('"Core13/%s" has no value picker.', $fileName));
        foreach ($valuePickers as $valuePicker) {
            // TYPO3 v13 reads $item[0] and $item[1], so each item is a pair of
            // positional <numIndex> children and carries no <label>/<value>.
            $this->assertStringNotContainsString('<label>', $valuePicker);
            $this->assertStringNotContainsString('<value>', $valuePicker);
            $this->assertStringContainsString('<numIndex index="0">', $valuePicker);
            $this->assertStringContainsString('<numIndex index="1">', $valuePicker);
        }
    }

    #[Test]
    #[DataProvider('splitFlexFormDataProvider')]
    public function core14VariantUsesAssociativeValuePickerItems(string $fileName): void
    {
        $valuePickers = $this->valuePickers($this->readFlexForm('Core14/' . $fileName));

        $this->assertNotSame([], $valuePickers, sprintf('"Core14/%s" has no value picker.', $fileName));
        foreach ($valuePickers as $valuePicker) {
            // TYPO3 v14 reads $item['label'] and $item['value']. Positional
            // children would still work, but only by way of an on-the-fly
            // TcaMigration that raises E_USER_DEPRECATED.
            $this->assertStringContainsString('<label>', $valuePicker);
            $this->assertStringContainsString('<value>', $valuePicker);
            $this->assertStringNotContainsString('<numIndex index="0">', $valuePicker);
            $this->assertStringNotContainsString('<numIndex index="1">', $valuePicker);
        }
    }

    /**
     * The split costs a duplicated file, so it may only cover the structures
     * that really differ. The other two carry no value picker and stay shared.
     */
    #[Test]
    public function onlyTheStructuresCarryingAValuePickerAreSplit(): void
    {
        $this->assertSame(
            ['SelectedContracts.xml', 'SelectedProfiles.xml'],
            $this->flexFormNames(''),
            'The shared FlexForm folder holds structures other than the two without a value picker.',
        );
        foreach ($this->flexFormNames('') as $fileName) {
            $this->assertSame(
                [],
                $this->valuePickers($this->readFlexForm($fileName)),
                sprintf('Shared FlexForm "%s" carries a value picker and would have to be split.', $fileName),
            );
        }

        foreach (['Core13', 'Core14'] as $variant) {
            $this->assertSame(
                ['Detail.xml', 'List.xml'],
                $this->flexFormNames($variant),
                sprintf('The "%s" folder holds structures other than the two that are split.', $variant),
            );
        }
    }

    /**
     * @return list<string> The `*.xml` file names directly below the folder, sorted
     */
    private function flexFormNames(string $folder): array
    {
        $names = [];
        $paths = glob(rtrim(self::FLEX_FORM_PATH . '/' . $folder, '/') . '/*.xml') ?: [];
        foreach ($paths as $path) {
            $names[] = basename($path);
        }
        sort($names);

        return $names;
    }

    private function readFlexForm(string $relativePath): string
    {
        $path = self::FLEX_FORM_PATH . '/' . $relativePath;
        $contents = file_get_contents($path);
        $this->assertIsString($contents, sprintf('FlexForm "%s" could not be read.', $relativePath));

        return $contents;
    }

    /**
     * @return list<string>
     */
    private function valuePickers(string $flexForm): array
    {
        preg_match_all('#<valuePicker>.*?</valuePicker>#s', $flexForm, $matches);

        return $matches[0];
    }

    /**
     * @return list<array{0: string, 1: string}> label and value of every item, across all pickers
     */
    private function positionalValuePickerItems(string $flexForm): array
    {
        return $this->valuePickerItems(
            $flexForm,
            '#<numIndex index="0">(.*?)</numIndex>\s*<numIndex index="1">(.*?)</numIndex>#s',
        );
    }

    /**
     * @return list<array{0: string, 1: string}> label and value of every item, across all pickers
     */
    private function associativeValuePickerItems(string $flexForm): array
    {
        return $this->valuePickerItems($flexForm, '#<label>(.*?)</label>\s*<value>(.*?)</value>#s');
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function valuePickerItems(string $flexForm, string $itemPattern): array
    {
        $items = [];
        foreach ($this->valuePickers($flexForm) as $valuePicker) {
            preg_match_all($itemPattern, $valuePicker, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $items[] = [$match[1], $match[2]];
            }
        }

        return $items;
    }

    private function withoutValuePickers(string $flexForm): string
    {
        return (string)preg_replace('#<valuePicker>.*?</valuePicker>#s', '<valuePicker/>', $flexForm);
    }
}
