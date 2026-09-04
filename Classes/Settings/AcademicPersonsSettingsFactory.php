<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use FGTCLB\AcademicBase\Settings\ValidationSet;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal not part of public API.
 */
class AcademicPersonsSettingsFactory
{
    public function __construct(
        protected readonly SettingsFileLoader $settingsFileLoader,
        protected readonly ValidationNormalizer $validationNormalizer,
    ) {}

    public function get(): AcademicPersonsSettings
    {
        return $this->settingsFileLoader->load(
            'Configuration/AcademicPersons/Settings.yaml',
            'AcademicPersons_Settings_v3',
            AcademicPersonsSettings::class,
            $this->normalize(...),
        );
    }

    /**
     * @param array<string, mixed> $settings
     * @return AcademicPersonsSettings
     */
    private function normalize(array $settings): AcademicPersonsSettings
    {
        return new AcademicPersonsSettings(
            profileInformationTypes: $this->normalizeProfileInformationsTypes($settings),
            validations: $this->normalizeValidations($settings),
            raw: $settings,
        );
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, ValidationSet>
     */
    private function normalizeValidations(array $settings): array
    {
        if (!array_key_exists('validations', $settings)
            || !is_array($settings['validations'])
            || $settings['validations'] === []
        ) {
            return [];
        }
        return $this->validationNormalizer->normalizeValidationSets($settings['validations']);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, ProfileInformationType>
     */
    private function normalizeProfileInformationsTypes(array $settings): array
    {
        $profileInformationTypes = [];
        if (array_key_exists('profileInformationsTypes', $settings)
            && is_array($settings['profileInformationsTypes'])
            && $settings['profileInformationsTypes'] !== []
        ) {
            foreach ($settings['profileInformationsTypes'] as $identifier => $options) {
                $profileInformationType = new ProfileInformationType(
                    identifier: (string)$identifier,
                    fieldName: (string)($options['fieldName'] ?? GeneralUtility::camelCaseToLowerCaseUnderscored($identifier)),
                    type: (string)($options['type']),
                    label: (string)($options['label'] ?? ''),
                );
                if ($profileInformationType->isValid()) {
                    $profileInformationTypes[$profileInformationType->identifier] = $profileInformationType;
                }
            }
        }
        return $profileInformationTypes;
    }
}
