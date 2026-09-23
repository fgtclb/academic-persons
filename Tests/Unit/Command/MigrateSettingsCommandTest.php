<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Command;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use FGTCLB\AcademicPersons\Command\MigrateSettingsCommand;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use FGTCLB\AcademicPersons\Settings\LegacySettingsMigrator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The command walks the packages itself, because it reports the state of each
 * package as the packages up to it produce it - the merged array of the whole
 * installation would attribute nothing. It has to fold them the way the loader
 * does all the same, and the package order is what makes the difference
 * visible, so it is fixed here rather than left to an installation.
 */
final class MigrateSettingsCommandTest extends UnitTestCase
{
    private const LEGACY_PACKAGE = __DIR__ . '/../../Functional/Fixtures/Extensions/test_legacy_settings/';
    private const PARTIAL_PACKAGE = __DIR__ . '/../Fixtures/Packages/partial_settings/';

    /**
     * A package before the legacy one names three profile fields and nothing
     * else. A top-level fold turns that into a profile of three fields, so the
     * document the command tells the integrator to ship loses every other field,
     * the public layout included - and it would no longer be what the
     * installation runs on.
     */
    #[Test]
    public function aPartialOverrideBeforeTheLegacyPackageKeepsTheFieldsItDoesNotName(): void
    {
        $loader = new SettingsFileLoader($this->cacheWithoutEntry(), $this->packageManager());
        $tester = new CommandTester(new MigrateSettingsCommand($loader, new LegacySettingsMigrator()));

        $tester->execute([]);
        $printed = Yaml::parse($tester->getDisplay());

        $this->assertIsArray($printed);
        $this->assertArrayHasKey('structure', $printed['profile'], 'The public layout of the shipped file survives');
        $this->assertSame('information', $printed['profile']['gender']['section'] ?? null);
        $this->assertArrayHasKey('physicalAddresses', $printed['contracts']['contactSections']);
    }

    /**
     * And what it prints is what that installation runs on: the legacy package
     * is the last one here, the realistic layout, so the per-package view and
     * the merged array of the runtime have to agree key for key. It prints the
     * four section maps the legacy keys are mapped onto; `frontendUserSync` is
     * none of them, and a package that does not name it keeps the shipped one.
     */
    #[Test]
    public function thePrintedMapsAreTheSettingsTheInstallationRunsOn(): void
    {
        $loader = new SettingsFileLoader($this->cacheWithoutEntry(), $this->packageManager());
        $tester = new CommandTester(new MigrateSettingsCommand($loader, new LegacySettingsMigrator()));

        $tester->execute([]);
        $printed = Yaml::parse($tester->getDisplay());

        $factory = new AcademicPersonsSettingsFactory($loader, new ValidationNormalizer(), new LegacySettingsMigrator());
        $this->assertSame(array_diff_key($factory->get()->raw, ['frontendUserSync' => true]), $printed);
    }

    private function cacheWithoutEntry(): PhpFrontend
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn(false);
        return $cache;
    }

    /**
     * academic_persons, then a package that names three profile fields, then the
     * package still shipping the pre-3.0 keys.
     */
    private function packageManager(): PackageManager
    {
        $packages = [];
        foreach ([
            'academic_persons' => __DIR__ . '/../../../',
            'test_partial_settings' => self::PARTIAL_PACKAGE,
            'test_legacy_settings' => self::LEGACY_PACKAGE,
        ] as $packageKey => $packagePath) {
            $package = $this->createMock(PackageInterface::class);
            $package->method('getPackageKey')->willReturn($packageKey);
            $package->method('getPackagePath')->willReturn($packagePath);
            $packages[] = $package;
        }
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn($packages);
        return $packageManager;
    }
}
