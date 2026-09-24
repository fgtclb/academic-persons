<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Command;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicPersons\Command\MigrateSettingsCommand;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Three override packages next to academic_persons: a copy of the contract
 * fields, a file that names only the layout keys it changes, and a file with
 * the pre-3.0 keys. With `--delta` the command prints the smallest file of each,
 * and those files together give the settings the installation runs on.
 *
 * The package order of an instance does not follow the order the extensions
 * are listed in here; the three packages touch different maps, and the order
 * the fold uses is taken from the loader rather than assumed.
 */
final class MigrateSettingsDeltaTest extends AbstractAcademicPersonsTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'tests/test-settings-copy',
        'tests/test-public-profile-settings',
        'tests/test-legacy-settings',
    ];

    #[Test]
    public function theCopyShrinksToWhatItChangesAndNamesWhatItLeavesOut(): void
    {
        $tester = new CommandTester($this->get(MigrateSettingsCommand::class));

        $exitCode = $tester->execute(['--delta' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode, 'The delta mode gates nothing, a legacy package included');
        $printed = $this->printedFiles($tester->getDisplay());
        $this->assertSame(
            ['test_legacy_settings', 'test_public_profile_settings', 'test_settings_copy'],
            array_keys($this->sortedByKey($printed)),
            'Every package after academic_persons, and academic_persons itself not',
        );
        $copy = $printed['test_settings_copy'];
        $this->assertStringContainsString("#   contracts.fields.room: ~\n", $copy['comments']);
        $this->assertSame(['contracts'], array_keys($copy['yaml']));
        $this->assertSame(['location', 'officeHours'], array_keys($copy['yaml']['contracts']['fields']));
        $this->assertNull($copy['yaml']['contracts']['fields']['officeHours']);
        $this->assertStringContainsString("officeHours: ~\n", $copy['text'], 'A removal is printed as the tilde the manual uses');
        $this->assertSame(
            ['fieldType', 'renderType', 'options', 'validators', 'helptext'],
            array_keys($copy['yaml']['contracts']['fields']['location']),
            'The copy placed the new flag list between two restated keys, which decides their order',
        );
        $this->assertSame(['required'], $copy['yaml']['contracts']['fields']['location']['validators']);
        $this->assertStringContainsString('# Legacy keys: validations', $printed['test_legacy_settings']['comments']);
    }

    /**
     * The one property the delta exists for: each package's file replaced by
     * what the command printed for it, the packages merge into the array the
     * installation runs on now.
     */
    #[Test]
    public function thePrintedFilesGiveTheSettingsTheInstallationRunsOn(): void
    {
        $tester = new CommandTester($this->get(MigrateSettingsCommand::class));
        $tester->execute(['--delta' => true]);
        $printed = $this->printedFiles($tester->getDisplay());

        $cache = $this->get(CacheManager::class)->getCache('core');
        $this->assertInstanceOf(PhpFrontend::class, $cache);
        $loader = new SettingsFileLoader($cache, $this->get(PackageManager::class));
        $merged = [];
        foreach ($loader->loadPackageArrays(AcademicPersonsSettingsFactory::SETTINGS_FILE) as $packageKey => $packageSettings) {
            $merged = $loader->merge($merged, $printed[$packageKey]['yaml'] ?? $packageSettings);
        }

        $this->assertSame($loader->loadMergedArray(AcademicPersonsSettingsFactory::SETTINGS_FILE), $merged);
    }

    #[Test]
    public function withoutTheOptionTheCommandStillMigratesAndFails(): void
    {
        $tester = new CommandTester($this->get(MigrateSettingsCommand::class));

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('# test_legacy_settings: Configuration/AcademicPersons/Settings.yaml', $output);
        $this->assertStringNotContainsString('test_settings_copy', $output);
    }

    /**
     * Splits the output at the header line each package starts with.
     *
     * @return array<string, array{text: string, comments: string, yaml: array<string, mixed>}>
     */
    private function printedFiles(string $output): array
    {
        $files = [];
        $parts = preg_split('/^# ([a-z0-9_]+): Configuration\/AcademicPersons\/Settings\.yaml\n/m', $output, -1, PREG_SPLIT_DELIM_CAPTURE);
        $this->assertIsArray($parts);
        $this->assertSame('', $parts[0], 'Nothing is printed before the first package');
        for ($i = 1; $i < count($parts); $i += 2) {
            $text = $parts[$i + 1];
            preg_match_all('/^#.*\n/m', $text, $comments);
            $yaml = Yaml::parse($text) ?? [];
            $this->assertIsArray($yaml);
            $files[$parts[$i]] = ['text' => $text, 'comments' => implode('', $comments[0]), 'yaml' => $yaml];
        }
        return $files;
    }

    /**
     * @template T
     * @param array<string, T> $array
     * @return array<string, T>
     */
    private function sortedByKey(array $array): array
    {
        ksort($array);
        return $array;
    }
}
