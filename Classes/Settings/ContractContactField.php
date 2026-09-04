<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Settings\Validation;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * One field of an Address, Email or PhoneNumber record owned by a Contract.
 *
 * `identifier` is the key of the settings file, `propertyName` the DTO and
 * domain property it addresses and `fieldName` the database column - the
 * three differ for the `type` and `email` columns, whose settings keys carry
 * the section name to stay unique across the contact sections.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class ContractContactField
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $section,
        public readonly string $propertyName,
        public readonly string $fieldName,
        public readonly string $fieldType,
        public readonly string $renderType,
        public readonly Validation $validation,
        public readonly int $position,
        public readonly string $autocomplete = '',
        public readonly string $helptext = '',
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     section: string,
     *     propertyName: string,
     *     fieldName: string,
     *     fieldType: string,
     *     renderType: string,
     *     validation: Validation,
     *     position: int,
     *     autocomplete?: string,
     *     helptext?: string,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            section: $array['section'],
            propertyName: $array['propertyName'],
            fieldName: $array['fieldName'],
            fieldType: $array['fieldType'],
            renderType: $array['renderType'],
            validation: $array['validation'],
            position: $array['position'],
            autocomplete: $array['autocomplete'] ?? '',
            helptext: $array['helptext'] ?? '',
        );
    }

    public function isValid(): bool
    {
        return $this->identifier !== ''
            && $this->section !== ''
            && $this->propertyName !== ''
            && $this->fieldName !== ''
            && $this->fieldType !== ''
            && $this->renderType !== '';
    }
}
