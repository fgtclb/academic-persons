<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * One entry of a contact list of `frontendUserSync`: the `fe_users` column of
 * each record property it feeds, and - for a phone number - the configured
 * type. A property mapped to `''` is not part of `columns`.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class FrontendUserSyncEntry
{
    /**
     * @param non-empty-array<string, non-empty-string> $columns record property => `fe_users` column
     */
    public function __construct(
        public readonly array $columns,
        public readonly string $type = '',
    ) {}

    /**
     * @param array{
     *     columns: non-empty-array<string, non-empty-string>,
     *     type?: string,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            columns: $array['columns'],
            type: $array['type'] ?? '',
        );
    }

    /**
     * The column that names the entry in an import identifier: the first one.
     *
     * @return non-empty-string
     */
    public function getIdentifyingColumn(): string
    {
        return $this->columns[array_key_first($this->columns)];
    }

    /**
     * Whether every column of the entry is empty in the frontend user data. A
     * column is empty as PHP's `empty()` sees it, which is the rule the
     * synchronisation followed before the mapping existed - `'0'` included.
     *
     * @param array<string, mixed> $frontendUserData
     */
    public function isEmptyIn(array $frontendUserData): bool
    {
        foreach ($this->columns as $column) {
            if (!empty($frontendUserData[$column])) {
                return false;
            }
        }
        return true;
    }
}
