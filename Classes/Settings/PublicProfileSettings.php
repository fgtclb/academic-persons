<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * The public detail layout: `structure` lists the element identifiers per
 * layout column, `details` the ordered properties, relation map, label key or
 * special renderer of each element.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class PublicProfileSettings
{
    /**
     * @param array<string, list<string>> $structure
     * @param array<string, string|list<string>|array<string, string>> $details
     */
    public function __construct(
        public readonly array $structure = [],
        public readonly array $details = [],
    ) {}

    /**
     * @param array{
     *     structure?: array<string, list<string>>,
     *     details?: array<string, string|list<string>|array<string, string>>,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            structure: $array['structure'] ?? [],
            details: $array['details'] ?? [],
        );
    }
}
