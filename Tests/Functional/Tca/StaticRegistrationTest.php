<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pins the values an installation stores in "sys_template.include_static_file" and
 * in "pages.tsconfig_includes".
 *
 * They are not implementation detail: they are written into records, so renaming a
 * registered folder silently empties the configuration of every installation that
 * selected it. Whenever an expectation here changes, the extension needs a Breaking
 * changelog entry naming the old and the new value.
 *
 * The two entries "Configuration/TypoScript/Default" and
 * "Configuration/TypoScript/Standalone" are in this list for that reason alone: they
 * are the values installations stored before the configuration was cut per component,
 * and they keep working because the folders kept their names.
 */
final class StaticRegistrationTest extends AbstractAcademicPersonsTestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function staticTemplateIsRegisteredDataProvider(): \Generator
    {
        yield 'profile list' => [
            'EXT:academic_persons/Configuration/TypoScript/List',
            'Academic Persons: Profile list (academic_persons)',
        ];
        yield 'profile list and detail' => [
            'EXT:academic_persons/Configuration/TypoScript/ListAndDetail',
            'Academic Persons: Profile list and detail (academic_persons)',
        ];
        yield 'profile detail' => [
            'EXT:academic_persons/Configuration/TypoScript/Detail',
            'Academic Persons: Profile detail (academic_persons)',
        ];
        yield 'profile card' => [
            'EXT:academic_persons/Configuration/TypoScript/Card',
            'Academic Persons: Profile card (academic_persons)',
        ];
        yield 'selected profiles' => [
            'EXT:academic_persons/Configuration/TypoScript/SelectedProfiles',
            'Academic Persons: Selected profiles (academic_persons)',
        ];
        yield 'selected contracts' => [
            'EXT:academic_persons/Configuration/TypoScript/SelectedContracts',
            'Academic Persons: Selected contracts (academic_persons)',
        ];
        yield 'all components' => [
            'EXT:academic_persons/Configuration/TypoScript/Full',
            'Academic Persons: All components (academic_persons)',
        ];
        yield 'shared plugin settings' => [
            'EXT:academic_persons/Configuration/TypoScript/Default',
            'Academic Persons: Shared plugin settings (academic_persons)',
        ];
        yield 'standalone page' => [
            'EXT:academic_persons/Configuration/TypoScript/Standalone',
            'Academic Persons: Standalone page (academic_persons)',
        ];
    }

    #[Test]
    #[DataProvider('staticTemplateIsRegisteredDataProvider')]
    public function staticTemplateIsRegistered(string $value, string $label): void
    {
        $this->assertContains(
            ['label' => $label, 'value' => $value],
            $GLOBALS['TCA']['sys_template']['columns']['include_static_file']['config']['items'] ?? [],
        );
    }

    /**
     * The registration above is a string, so it stays green when the folder it names
     * is renamed or removed - which is the failure this test class exists for. A
     * static template that points at a folder without any of the three files the core
     * looks for is not an error either, it simply contributes nothing, so the folder
     * and its content have to be asserted separately.
     */
    #[Test]
    #[DataProvider('staticTemplateIsRegisteredDataProvider')]
    public function registeredStaticTemplateFolderExistsAndCarriesTypoScript(string $value, string $label): void
    {
        $path = GeneralUtility::getFileAbsFileName($value);

        $this->assertDirectoryExists(
            $path,
            sprintf('The folder registered as "%s" does not exist.', $label),
        );

        $carriedFiles = array_values(array_filter(
            ['constants.typoscript', 'setup.typoscript', 'include_static_file.txt'],
            static fn(string $fileName): bool => file_exists($path . '/' . $fileName),
        ));

        $this->assertNotSame(
            [],
            $carriedFiles,
            sprintf(
                'The folder registered as "%s" holds none of "constants.typoscript", "setup.typoscript" or'
                    . ' "include_static_file.txt", so the static template delivers nothing.',
                $label,
            ),
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function pageTsConfigFileIsRegisteredDataProvider(): \Generator
    {
        yield 'profile list' => [
            'EXT:academic_persons/Configuration/TSconfig/List/page.tsconfig',
            'Academic Persons: Profile list (academic_persons)',
        ];
        yield 'profile list and detail' => [
            'EXT:academic_persons/Configuration/TSconfig/ListAndDetail/page.tsconfig',
            'Academic Persons: Profile list and detail (academic_persons)',
        ];
        yield 'profile detail' => [
            'EXT:academic_persons/Configuration/TSconfig/Detail/page.tsconfig',
            'Academic Persons: Profile detail (academic_persons)',
        ];
        yield 'profile card' => [
            'EXT:academic_persons/Configuration/TSconfig/Card/page.tsconfig',
            'Academic Persons: Profile card (academic_persons)',
        ];
        yield 'selected profiles' => [
            'EXT:academic_persons/Configuration/TSconfig/SelectedProfiles/page.tsconfig',
            'Academic Persons: Selected profiles (academic_persons)',
        ];
        yield 'selected contracts' => [
            'EXT:academic_persons/Configuration/TSconfig/SelectedContracts/page.tsconfig',
            'Academic Persons: Selected contracts (academic_persons)',
        ];
        yield 'all components' => [
            'EXT:academic_persons/Configuration/TSconfig/Full/page.tsconfig',
            'Academic Persons: All components (academic_persons)',
        ];
    }

    #[Test]
    #[DataProvider('pageTsConfigFileIsRegisteredDataProvider')]
    public function pageTsConfigFileIsRegistered(string $value, string $label): void
    {
        $this->assertContains(
            ['label' => $label, 'value' => $value],
            $GLOBALS['TCA']['pages']['columns']['tsconfig_includes']['config']['items'] ?? [],
        );
    }

    /**
     * As above, and worse: an unresolved page TSconfig include is silent, so a
     * registration that names a file which is not there configures nothing and reports
     * nothing.
     */
    #[Test]
    #[DataProvider('pageTsConfigFileIsRegisteredDataProvider')]
    public function registeredPageTsConfigFileExists(string $value, string $label): void
    {
        $this->assertFileExists(
            GeneralUtility::getFileAbsFileName($value),
            sprintf('The file registered as "%s" does not exist.', $label),
        );
    }
}
