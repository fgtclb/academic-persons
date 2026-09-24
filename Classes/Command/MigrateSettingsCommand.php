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
use FGTCLB\AcademicPersons\Settings\SettingsOverrideComparator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
 * uses: the packages after it have not been read when its own file is printed.
 * It folds them with {@see SettingsFileLoader::merge()}, the same recursive
 * merge the runtime uses, so the printed maps are what the installation would
 * run on with the packages up to that point.
 *
 * With `--delta` it prints, for every package after the first one, the
 * smallest file with the effect the package's file has, as
 * {@see SettingsOverrideComparator} computes it, and names the entries a
 * copied map of the package leaves out. That mode describes and gates
 * nothing, and exits with 0.
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
        private readonly SettingsOverrideComparator $settingsOverrideComparator,
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
            . ' With --delta, prints instead for every package after the first one the smallest file with'
            . ' the same effect, and the entries its copied maps leave out; exits with 0 then.'
            . ' The file is never written by this command.',
        );
        $this->addOption(
            'delta',
            null,
            InputOption::VALUE_NONE,
            'Print the smallest settings file of every package after the first one',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('delta') === true) {
            return $this->printDeltas($output);
        }
        $legacyPackageFound = false;
        $merged = [];
        $legacyKeyFlip = array_flip(LegacySettingsMigrator::LEGACY_KEYS);
        $packageArrays = $this->settingsFileLoader->loadPackageArrays(AcademicPersonsSettingsFactory::SETTINGS_FILE);
        foreach ($packageArrays as $packageKey => $packageSettings) {
            $legacyKeys = $this->legacySettingsMigrator->getLegacyKeys($packageSettings);
            $merged = $this->settingsFileLoader->merge(
                $merged,
                array_diff_key($packageSettings, $legacyKeyFlip),
            );
            if ($legacyKeys === []) {
                continue;
            }
            $legacyPackageFound = true;
            $migration = $this->legacySettingsMigrator->migrate(
                $this->settingsFileLoader->merge(
                    $merged,
                    array_intersect_key($packageSettings, $legacyKeyFlip),
                ),
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

    private function printDeltas(OutputInterface $output): int
    {
        $packageArrays = $this->settingsFileLoader->loadPackageArrays(AcademicPersonsSettingsFactory::SETTINGS_FILE);
        $overrides = $this->settingsOverrideComparator->compare($packageArrays);
        foreach ($overrides as $override) {
            $output->writeln(sprintf('# %s: %s', $override->packageKey, AcademicPersonsSettingsFactory::SETTINGS_FILE));
            $legacyKeys = $this->legacySettingsMigrator->getLegacyKeys($packageArrays[$override->packageKey] ?? []);
            if ($legacyKeys !== []) {
                $output->writeln(sprintf(
                    '# Legacy keys: %s - run the command without --delta and migrate them first.',
                    implode(', ', $legacyKeys),
                ));
            }
            if ($override->omittedEntries !== []) {
                $output->writeln('# Left out of a copied map, and inherited because the files are merged per entry.');
                $output->writeln('# Add an entry with "~" where leaving it out was meant to remove it:');
                foreach ($override->omittedEntries as $omittedEntry) {
                    $output->writeln(sprintf('#   %s: ~', $omittedEntry));
                }
            }
            if ($override->delta === []) {
                $output->writeln('# The file changes nothing and can be dropped.');
                continue;
            }
            $output->writeln(Yaml::dump(
                $override->delta,
                20,
                2,
                Yaml::DUMP_NULL_AS_TILDE | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE,
            ));
        }
        if ($overrides === []) {
            $output->writeln(sprintf(
                'No active package after the first one ships %s.',
                AcademicPersonsSettingsFactory::SETTINGS_FILE,
            ));
        }
        return Command::SUCCESS;
    }
}
