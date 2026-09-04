<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Settings\ValidationSet;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Keeps validation for one kind of contract contact isolated from the other
 * contact records and from direct Profile fields.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class ContractContactSection
{
    /**
     * @param array<string, ContractContactField> $fields
     */
    public function __construct(
        public readonly string $identifier,
        public readonly array $fields,
        public readonly ValidationSet $validationSet,
        public readonly int $position,
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     fields: array<string, ContractContactField>,
     *     validationSet: ValidationSet,
     *     position: int,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            fields: $array['fields'],
            validationSet: $array['validationSet'],
            position: $array['position'],
        );
    }

    public function getField(string $identifier): ?ContractContactField
    {
        return $this->fields[$identifier] ?? null;
    }

}
