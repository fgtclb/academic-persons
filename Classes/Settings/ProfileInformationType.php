<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * @internal and not part of public API.
 */
#[Exclude]
final class ProfileInformationType
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $fieldName,
        public readonly string $type,
        public readonly string $label,
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     fieldName: string,
     *     type: string,
     *     label: string,
     * } $array
     * @return self
     */
    public static function __set_state(array $array): object
    {
        return new self(
            identifier: $array['identifier'],
            fieldName: $array['fieldName'],
            type: $array['type'],
            label: $array['label'],
        );
    }

    public function isValid(): bool
    {
        return $this->identifier !== ''
            && $this->type !== ''
            && $this->label !== '';
    }
}
