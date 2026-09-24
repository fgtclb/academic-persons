<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Report;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use FGTCLB\AcademicPersons\Settings\LegacySettingsMigrator;
use FGTCLB\AcademicPersons\Settings\SettingsOverride;
use FGTCLB\AcademicPersons\Settings\SettingsOverrideComparator;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Names, in the status report of EXT:reports, every active package whose
 * `Configuration/AcademicPersons/Settings.yaml` still ships the pre-3.0
 * keys that {@see LegacySettingsMigrator} maps at runtime, and every package
 * whose file removes entries with `~` or leaves entries out of a copied map,
 * as {@see SettingsOverrideComparator} finds them. An omission is a notice:
 * the package inherits the entry since the files are merged per entry, and
 * only the integrator knows whether the copy meant to drop it.
 *
 * Registered by `Configuration/Services.php` only when EXT:reports is
 * loaded, because the interface belongs to that extension; EXT:reports
 * itself tags every implementation as a status provider.
 *
 * @todo Rename the class and its `status.legacySettings.*` title label when
 *       the legacy half is removed in 4.0; the override entries stay.
 *
 * @internal not part of public API.
 */
final class LegacySettingsStatus implements StatusProviderInterface
{
    private const LANGUAGE_FILE = 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_reports.xlf:';

    public function __construct(
        private readonly SettingsFileLoader $settingsFileLoader,
        private readonly LegacySettingsMigrator $legacySettingsMigrator,
        private readonly SettingsOverrideComparator $settingsOverrideComparator,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    /**
     * The status report resolves a label that is a language reference itself.
     */
    public function getLabel(): string
    {
        return self::LANGUAGE_FILE . 'status.label';
    }

    /**
     * @return Status[]
     */
    public function getStatus(): array
    {
        $languageService = $this->getLanguageService();
        $statuses = [];
        $packageArrays = $this->settingsFileLoader->loadPackageArrays(AcademicPersonsSettingsFactory::SETTINGS_FILE);
        foreach ($packageArrays as $packageKey => $packageSettings) {
            $legacyKeys = $this->legacySettingsMigrator->getLegacyKeys($packageSettings);
            if ($legacyKeys === []) {
                continue;
            }
            $statuses[] = new Status(
                $languageService->sL(self::LANGUAGE_FILE . 'status.legacySettings.title'),
                $packageKey,
                sprintf(
                    $languageService->sL(self::LANGUAGE_FILE . 'status.legacySettings.message'),
                    implode('", "', $legacyKeys),
                    AcademicPersonsSettingsFactory::SETTINGS_FILE,
                ),
                ContextualFeedbackSeverity::WARNING,
            );
        }
        if ($statuses === []) {
            $statuses[] = new Status(
                $languageService->sL(self::LANGUAGE_FILE . 'status.legacySettings.title'),
                $languageService->sL(self::LANGUAGE_FILE . 'status.legacySettings.none.value'),
                $languageService->sL(self::LANGUAGE_FILE . 'status.legacySettings.none.message'),
            );
        }
        foreach ($this->settingsOverrideComparator->compare($packageArrays) as $override) {
            if ($override->removedEntries !== [] || $override->omittedEntries !== []) {
                $statuses[] = $this->overrideStatus($override, $languageService);
            }
        }
        return $statuses;
    }

    private function overrideStatus(SettingsOverride $override, LanguageService $languageService): Status
    {
        $sentences = [];
        if ($override->removedEntries !== []) {
            $sentences[] = sprintf(
                $languageService->sL(self::LANGUAGE_FILE . 'status.overrides.removed'),
                implode(', ', $override->removedEntries),
            );
        }
        if ($override->omittedEntries !== []) {
            $sentences[] = sprintf(
                $languageService->sL(self::LANGUAGE_FILE . 'status.overrides.omitted'),
                implode(', ', $override->omittedEntries),
            );
        }
        $sentences[] = $languageService->sL(self::LANGUAGE_FILE . 'status.overrides.delta');
        return new Status(
            $languageService->sL(self::LANGUAGE_FILE . 'status.legacySettings.title'),
            $override->packageKey,
            implode(' ', $sentences),
            $override->omittedEntries !== [] ? ContextualFeedbackSeverity::NOTICE : ContextualFeedbackSeverity::INFO,
        );
    }

    private function getLanguageService(): LanguageService
    {
        return $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }
}
