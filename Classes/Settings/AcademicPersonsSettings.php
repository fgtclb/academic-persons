<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Settings\ValidationSet;

/**
 * @internal not part of public API.
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
}
