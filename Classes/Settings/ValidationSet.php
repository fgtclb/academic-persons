<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * @internal and not part of public API.
 * @todo Move this to `EXT:academic_base`.
 */
#[Exclude]
final class ValidationSet
{
    /**
     * @param string $identifier
     * @param array<string, Validation> $validations
     */
    public function __construct(
        public readonly string $identifier,
        public readonly array $validations,
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     validations: array<string, Validation>,
     * } $array
     * @return self
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            validations: $array['validations'],
        );
    }

    public function get(string $identifier): ?Validation
    {
        return $this->validations[$identifier] ?? null;
    }
}
