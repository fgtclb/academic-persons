<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * @internal not part of public API.
 * @todo Integrate a basic configuration builder factory in `EXT:academic_base` and adopt this implementation.
 */
class AcademicPersonsSettingsFactory
{
    public function __construct(
        #[Autowire(service: 'cache.core')]
        protected readonly PhpFrontend $cache,
        protected readonly PackageManager $packageManager,
    ) {}

    public function get(): AcademicPersonsSettings
    {
        return $this->getFromCache() ?? $this->loadUncached();
    }

    private function loadUncached(): AcademicPersonsSettings
    {
        $loadedSettings = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $settingsFile = $package->getPackagePath() . 'Configuration/AcademicPersons/Settings.yaml';
            if (file_exists($settingsFile)) {
                $settingsArray = Yaml::parseFile($settingsFile);
                if ($settingsArray === null) {
                    continue;
                }
                $loadedSettings = array_merge($loadedSettings, $settingsArray);
            }
        }
        $settings = $this->normalize($loadedSettings);
        $this->setCache($settings);
        return $settings;
    }

    private function getFromCache(): ?AcademicPersonsSettings
    {
        $settings = $this->cache->require($this->academicPersonsSettingsIdentifier());
        return $settings instanceof AcademicPersonsSettings ? $settings : null;
    }

    private function setCache(AcademicPersonsSettings $settings): void
    {
        $this->cache->set($this->academicPersonsSettingsIdentifier(), 'return ' . var_export($settings, true) . ';');
    }

    /**
     * @return non-empty-string string
     */
    private function academicPersonsSettingsIdentifier(): string
    {
        return 'AcademicPersons_Settings';
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
        $validations = [];
        if (array_key_exists('validations', $settings)
            && is_array($settings['validations'])
            && $settings['validations'] !== []
        ) {
            foreach ($settings['validations'] as $identifier => $options) {
                $itemValidations = [];
                foreach ($options as $fieldIdentifier => $validators) {
                    $tcaConfig = [];
                    $validators = array_map('strtolower', $validators);
                    $readOnly = in_array('readonly', $validators, true);
                    $disabled = in_array('disabled', $validators, true);
                    $required = !$disabled && !$readOnly && in_array('required', $validators, true);
                    $inputType = 'text';
                    /** @var class-string<ValidatorInterface>[] $validatorClassNames */
                    $validatorClassNames = [];
                    if ($disabled) {
                        // @todo Investigate how to handle that for the backend / TCA FormEngine, therefore switch to
                        //       readOnly for now
                        $readOnly = true;
                    }
                    $tcaConfig['readOnly'] = $readOnly;
                    $tcaConfig['required'] = false;
                    if ($required) {
                        $validatorClassNames[] = NotEmptyValidator::class;
                        $tcaConfig['required'] = true;
                        $tcaConfig['minitems'] = 1;
                    }
                    if (in_array('email', $validators, true)) {
                        $validatorClassNames[] = EmailAddressValidator::class;
                        $tcaConfig['type'] = 'email';
                        $inputType = 'email';
                    }
                    if (in_array('number', $validators, true)) {
                        // @todo Investigate if we want to use NumberValidator for the frontend
                        $tcaConfig['type'] = 'number';
                        $inputType = 'number';
                    }
                    // @todo url validation ?
                    $itemValidations[$fieldIdentifier] = new Validation(
                        identifier: $fieldIdentifier,
                        fieldName: GeneralUtility::camelCaseToLowerCaseUnderscored($fieldIdentifier),
                        required: $required,
                        disabled: $disabled,
                        readOnly: $readOnly,
                        validatorClassNames: $validatorClassNames,
                        tcaConfig: $tcaConfig,
                        inputType: $inputType,
                    );
                }
                $validations[$identifier] = new ValidationSet(
                    identifier: $identifier,
                    validations: $itemValidations,
                );
            }
        }
        return $validations;
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
