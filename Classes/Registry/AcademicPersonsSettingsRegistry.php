<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Registry;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;

/**
 * Note class is used in early bootstrap phase (TCA compilation) and **must** not use database services directly
 * or indirectly and needs to stay clean as as possible.
 *
 * @internal for internal use only and not part of public extension API. May change at any given time.
 */
class AcademicPersonsSettingsRegistry
{
    /**
     * @var array<string, mixed>
     */
    protected array $registry = [];

    /**
     * @param array<string, mixed> $settings
     */
    public function attach(array $settings): void
    {
        if ($settings === []) {
            return;
        }

        // @todo Recheck this merge. Is it really good that way ? Not recursive or eventually using ArrayUtility for it ?
        $this->registry = array_merge($this->registry, $settings);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->registry;
    }

    public function getProfileInformationTypeMapping(string $type): string
    {
        if (!isset($this->registry['profileInformationsTypes'][$type]['type'])) {
            return '';
        }
        return $this->registry['profileInformationsTypes'][$type]['type'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidationsForFrontend(string $object): array
    {
        $validations = [];
        if (isset($this->registry['validations'][$object])) {
            $validations = $this->registry['validations'][$object];
        }
        return $validations;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidationsForValidator(string $object): array
    {
        $validations = [];
        if (isset($this->registry['validations'][$object])) {
            foreach ($this->registry['validations'][$object] as $property => $validators) {
                foreach ($validators as $validator) {
                    $validatorClass = null;
                    switch ($validator) {
                        case 'email':
                            $validatorClass = EmailAddressValidator::class;
                            break;
                        case 'required':
                            $validatorClass = NotEmptyValidator::class;
                            break;
                    }
                    if ($validatorClass !== null) {
                        $validations[$property][] = $validatorClass;
                    }
                }
            }
        }
        return $validations;
    }

    /**
     * @return array<string, mixed>
     * @todo TCA Array should be handed over as argument and changes directly made, returning the array.
     * @todo Should be called in TCA Override files OR as part of an event listener and not in main TCA files.
     */
    public function getValidationsForTca(string $object): array
    {
        $validations = [];
        if (isset($this->registry['validations'][$object])) {
            foreach ($this->registry['validations'][$object] as $property => $validators) {
                $property = GeneralUtility::camelCaseToLowerCaseUnderscored($property);
                $tcaConfig = [];
                foreach ($validators as $validator) {
                    switch ($validator) {
                        case 'email':
                            $tcaConfig['type'] = 'email';
                            break;
                        case 'number':
                            $tcaConfig['type'] = 'number';
                            break;
                        case 'required':
                            $tcaConfig['required'] = true;
                            $tcaConfig['minitems'] = 1;
                            break;
                    }
                }
                if ($tcaConfig !== []) {
                    $validations['columns'][$property]['config'] = $tcaConfig;
                }
            }
        }
        return $validations;
    }
}
