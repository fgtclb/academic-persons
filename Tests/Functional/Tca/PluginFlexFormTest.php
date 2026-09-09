<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\PluginFlexFormDataStructureTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Guards the FlexForm data structure of the plugins against a shape that only
 * works on one of the supported core versions.
 *
 * @see PluginFlexFormDataStructureTrait
 */
final class PluginFlexFormTest extends AbstractAcademicPersonsTestCase
{
    use PluginFlexFormDataStructureTrait;

    /**
     * The plugin content types and how many `valuePicker` configurations their
     * data structure carries. Only `List.xml` and `Detail.xml` have one, on
     * `settings.pageTitleFormat`, and those two are the reason both files exist
     * once per supported core version.
     *
     * @return array<string, array{0: string, 1: int}>
     */
    private static function pluginContentTypes(): array
    {
        return [
            'Profile list' => ['academicpersons_list', 1],
            'Profile list and detail' => ['academicpersons_listanddetail', 1],
            'Profile detail' => ['academicpersons_detail', 1],
            'Profile card' => ['academicpersons_card', 1],
            'Selected profiles' => ['academicpersons_selectedprofiles', 0],
            'Selected contracts' => ['academicpersons_selectedcontracts', 0],
        ];
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function pluginContentTypeDataProvider(): \Generator
    {
        foreach (self::pluginContentTypes() as $label => [$cType]) {
            yield $label => [$cType];
        }
    }

    /**
     * @return \Generator<string, array{0: string, 1: int}>
     */
    public static function pluginContentTypeValuePickerDataProvider(): \Generator
    {
        foreach (self::pluginContentTypes() as $label => [$cType, $valuePickerCount]) {
            yield $label => [$cType, $valuePickerCount];
        }
    }

    #[Test]
    #[DataProvider('pluginContentTypeDataProvider')]
    public function pluginFlexFormIsResolvedForContentType(string $cType): void
    {
        $this->assertPluginFlexFormIsResolved($cType);
    }

    #[Test]
    #[DataProvider('pluginContentTypeValuePickerDataProvider')]
    public function pluginFlexFormValuePickerItemsAreReadableByRunningCore(
        string $cType,
        int $expectedValuePickerCount,
    ): void {
        $this->assertPluginFlexFormValuePickerItemsMatchRunningCore($cType, $expectedValuePickerCount);
    }
}
