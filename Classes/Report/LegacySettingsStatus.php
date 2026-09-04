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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Names, in the status report of EXT:reports, every active package whose
 * `Configuration/AcademicPersons/Settings.yaml` still ships the pre-3.0
 * keys that {@see LegacySettingsMigrator} maps at runtime.
 *
 * Registered by `Configuration/Services.php` only when EXT:reports is
 * loaded, because the interface belongs to that extension; EXT:reports
 * itself tags every implementation as a status provider.
 *
 * @internal not part of public API.
 */
final class LegacySettingsStatus implements StatusProviderInterface
{
    private const LANGUAGE_FILE = 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_reports.xlf:';

    public function __construct(
        private readonly SettingsFileLoader $settingsFileLoader,
        private readonly LegacySettingsMigrator $legacySettingsMigrator,
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
        return $statuses;
    }

    private function getLanguageService(): LanguageService
    {
        return $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }
}
