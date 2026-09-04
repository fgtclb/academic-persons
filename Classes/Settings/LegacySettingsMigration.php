<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * What {@see LegacySettingsMigrator::migrate()} produced: the settings array
 * with the legacy keys mapped onto the section maps and removed, the legacy
 * keys it found, and one note per entry it could not map or had to change.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class LegacySettingsMigration
{
    /**
     * @param array<string, mixed> $settings
     * @param list<string> $migratedKeys
     * @param list<string> $notes
     */
    public function __construct(
        public readonly array $settings,
        public readonly array $migratedKeys = [],
        public readonly array $notes = [],
    ) {}

    public function hasMigratedKeys(): bool
    {
        return $this->migratedKeys !== [];
    }

    /**
     * The notes about one legacy key, `validations` or
     * `profileInformationsTypes`.
     *
     * @return list<string>
     */
    public function getNotesForKey(string $legacyKey): array
    {
        return array_values(array_filter(
            $this->notes,
            static fn(string $note): bool => str_starts_with($note, $legacyKey . '.'),
        ));
    }
}
