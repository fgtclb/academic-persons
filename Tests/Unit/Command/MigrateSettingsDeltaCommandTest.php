<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Command;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicPersons\Command\MigrateSettingsCommand;
use FGTCLB\AcademicPersons\Settings\LegacySettingsMigrator;
use FGTCLB\AcademicPersons\Settings\SettingsOverrideComparator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The two outcomes of `--delta` that print no settings: a file without any
 * effect, and an installation where nothing is compared at all. The package
 * list is fixed here, which a functional test cannot do.
 */
final class MigrateSettingsDeltaCommandTest extends UnitTestCase
{
    #[Test]
    public function aFileWithoutEffectIsNamedAsDroppable(): void
    {
        $tester = $this->commandTester([
            'academic_persons' => __DIR__ . '/../../../',
            'test_unchanged_settings' => __DIR__ . '/../Fixtures/Packages/unchanged_settings/',
        ]);

        $exitCode = $tester->execute(['--delta' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(
            "# test_unchanged_settings: Configuration/AcademicPersons/Settings.yaml\n"
            . "# The file changes nothing and can be dropped.\n",
            $tester->getDisplay(),
            'No omission either: one field restated among many is not a copy of the map',
        );
    }

    #[Test]
    public function withoutASecondPackageThereIsNothingToCompare(): void
    {
        $tester = $this->commandTester(['academic_persons' => __DIR__ . '/../../../']);

        $exitCode = $tester->execute(['--delta' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(
            "No active package after the first one ships Configuration/AcademicPersons/Settings.yaml.\n",
            $tester->getDisplay(),
        );
    }

    /**
     * @param array<string, string> $packagePaths Package key => path, in loading order
     */
    private function commandTester(array $packagePaths): CommandTester
    {
        $packages = [];
        foreach ($packagePaths as $packageKey => $packagePath) {
            $package = $this->createMock(PackageInterface::class);
            $package->method('getPackageKey')->willReturn($packageKey);
            $package->method('getPackagePath')->willReturn($packagePath);
            $packages[] = $package;
        }
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn($packages);
        $loader = new SettingsFileLoader($this->createMock(PhpFrontend::class), $packageManager);
        return new CommandTester(
            new MigrateSettingsCommand($loader, new LegacySettingsMigrator(), new SettingsOverrideComparator($loader)),
        );
    }
}
