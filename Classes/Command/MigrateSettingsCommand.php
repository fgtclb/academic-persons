<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Command;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use FGTCLB\AcademicPersons\Settings\LegacySettingsMigrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Prints, for every active package whose `Configuration/AcademicPersons/Settings.yaml`
 * still ships the pre-3.0 `validations` or `profileInformationsTypes` keys,
 * the four section maps those keys are mapped onto at runtime - the document
 * the package should ship instead. Exits with 1 when such a package exists,
 * so a deployment pipeline can gate on it.
 *
 * Each package is migrated against the maps of the packages loaded up to and
 * including it, rather than against the fully merged array the runtime overlay
 * uses. The two agree for the realistic layout - the shipping package first,
 * the site package last - and differ only when a package loaded after the
 * legacy one replaces a whole top-level map.
 *
 * The command deliberately does not write the file: the override lives in a
 * site package that is under version control and deployed read-only, so a
 * write would be lost on the next deployment or leave a dirty working tree,
 * and the printed maps replace the legacy keys of that package only after
 * the integrator has reviewed them.
 *
 * @internal This command is for internal use and may change without notice.
 */
final class MigrateSettingsCommand extends Command
{
    private const SECTION_MAPS = ['profile', 'special', 'contracts', 'documentSections'];

    public function __construct(
        private readonly SettingsFileLoader $settingsFileLoader,
        private readonly LegacySettingsMigrator $legacySettingsMigrator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Lists every active package whose Configuration/AcademicPersons/Settings.yaml still ships the'
            . ' pre-3.0 "validations" or "profileInformationsTypes" keys, and prints for each the "profile",'
            . ' "special", "contracts" and "documentSections" maps those keys are mapped onto at runtime.'
            . ' Replace the legacy keys of the package with the printed maps and flush the caches.'
            . ' Exits with 1 when at least one such package exists, with 0 otherwise.'
            . ' The file is never written by this command.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $legacyPackageFound = false;
        $merged = [];
        $legacyKeyFlip = array_flip(LegacySettingsMigrator::LEGACY_KEYS);
        $packageArrays = $this->settingsFileLoader->loadPackageArrays(AcademicPersonsSettingsFactory::SETTINGS_FILE);
        foreach ($packageArrays as $packageKey => $packageSettings) {
            $legacyKeys = $this->legacySettingsMigrator->getLegacyKeys($packageSettings);
            $merged = array_merge($merged, array_diff_key($packageSettings, $legacyKeyFlip));
            if ($legacyKeys === []) {
                continue;
            }
            $legacyPackageFound = true;
            $migration = $this->legacySettingsMigrator->migrate(
                array_merge($merged, array_intersect_key($packageSettings, $legacyKeyFlip)),
            );
            $output->writeln(sprintf('# %s: %s', $packageKey, AcademicPersonsSettingsFactory::SETTINGS_FILE));
            $output->writeln(sprintf('# Legacy keys: %s', implode(', ', $legacyKeys)));
            foreach ($migration->notes as $note) {
                $output->writeln('# ' . $note);
            }
            $output->writeln(Yaml::dump(
                array_intersect_key($migration->settings, array_flip(self::SECTION_MAPS)),
                20,
                2,
            ));
        }
        if (!$legacyPackageFound) {
            $output->writeln(sprintf(
                'No active package ships the legacy keys "%s" in %s.',
                implode('" or "', LegacySettingsMigrator::LEGACY_KEYS),
                AcademicPersonsSettingsFactory::SETTINGS_FILE,
            ));
            return Command::SUCCESS;
        }
        return Command::FAILURE;
    }
}
