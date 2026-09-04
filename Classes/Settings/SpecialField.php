<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Settings\Validation;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * A component of the editing frontend that is not one regular profile field:
 * the composed display name, the image and the synchronisation switch. A
 * special field with a `fieldType` and no composed `fields` addresses one
 * direct profile property - `skipSync` - and takes part in the profile
 * validation; the others are rendered by their `renderType` alone.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class SpecialField
{
    /**
     * @param list<string> $fieldIdentifiers
     * @param array<string, string> $settings Renderer options, e.g. the crop `ratio` of the image
     */
    public function __construct(
        public readonly string $identifier,
        public readonly string $type,
        public readonly string $fieldType,
        public readonly string $renderType,
        public readonly array $fieldIdentifiers,
        public readonly Validation $validation,
        public readonly int $position,
        public readonly array $settings = [],
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     type: string,
     *     fieldType: string,
     *     renderType: string,
     *     fieldIdentifiers: list<string>,
     *     validation: Validation,
     *     position: int,
     *     settings?: array<string, string>,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            type: $array['type'],
            fieldType: $array['fieldType'],
            renderType: $array['renderType'],
            fieldIdentifiers: $array['fieldIdentifiers'],
            validation: $array['validation'],
            position: $array['position'],
            settings: $array['settings'] ?? [],
        );
    }

    public function isValid(): bool
    {
        return $this->identifier !== ''
            && $this->type === 'special'
            && $this->renderType !== '';
    }

    public function hasDirectProfileProperty(): bool
    {
        return $this->fieldType !== '' && $this->fieldIdentifiers === [];
    }
}
