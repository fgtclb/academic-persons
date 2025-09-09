<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

/**
 * @internal not part of public API.
 * @todo Integrate basic interface(s) and traits into `EXT:academic_base` and adopt along with basic/shared factory.
 */
final class AcademicPersonsSettings
{
    /**
     * @param array<string, ProfileInformationType> $profileInformationTypes
     * @param array<string, ValidationSet> $validations
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly array $profileInformationTypes,
        public readonly array $validations,
        public readonly array $raw,
    ) {}

    /**
     * @param array{
     *     profileInformationTypes: array<string, ProfileInformationType>,
     *     validations: array<string, ValidationSet>,
     *     raw: array<string, mixed>,
     * } $array
     * @return self
     */
    public static function __set_state(array $array): self
    {
        return new self(
            profileInformationTypes: $array['profileInformationTypes'],
            validations: $array['validations'],
            raw: $array['raw'],
        );
    }

    public function getProfileInformationType(string $identifier): ?ProfileInformationType
    {
        return $this->profileInformationTypes[$identifier] ?? null;
    }

    public function getValidationSet(string $identifier): ?ValidationSet
    {
        return $this->validations[$identifier] ?? null;
    }

    /**
     * Returns empty validation set in case `$identifier` is not registered,
     * otherwise returns registered and configured validation set.
     */
    public function getValidationSetWithFallback(string $identifier): ValidationSet
    {
        return $this->getValidationSet($identifier)
            ?? new ValidationSet(
                identifier: $identifier,
                validations: [],
            );
    }

    /**
     * @return array<string, mixed>
     * @todo TCA Array should be handed over as argument and changes directly made, returning the array.
     * @todo Should be called in TCA Override files OR as part of an event listener and not in main TCA files.
     */
    public function getValidationTcaTableConfig(string $identifier): array
    {
        $validationSet = $this->validations[$identifier] ?? null;
        if (! $validationSet instanceof ValidationSet) {
            return [];
        }
        $tableTca = [];
        foreach ($validationSet->validations as $validation) {
            if ($validation->tcaConfig !== []) {
                $tableTca['columns'][$validation->fieldName]['config'] = $validation->tcaConfig;
            }
        }
        return $tableTca;
    }
}
