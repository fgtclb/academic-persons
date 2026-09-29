<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * A relation of the imported contract in `frontendUserSync.contract`: the
 * `fe_users` column that names the related record, the field of that record
 * it is matched against, and whether a missing record is created, and on
 * which page. A relation without a column is not part of the map.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class FrontendUserSyncRelation
{
    /**
     * @param non-empty-string $column
     * @param non-empty-string $matchBy a property of the related record, see {@see FrontendUserSyncSettings::RELATION_MATCH_FIELDS}
     * @param int<0, max> $storagePid
     */
    public function __construct(
        public readonly string $column,
        public readonly string $matchBy,
        public readonly bool $create = false,
        public readonly int $storagePid = 0,
    ) {}

    /**
     * @param array{
     *     column: non-empty-string,
     *     matchBy: non-empty-string,
     *     create?: bool,
     *     storagePid?: int<0, max>,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            column: $array['column'],
            matchBy: $array['matchBy'],
            create: $array['create'] ?? false,
            storagePid: $array['storagePid'] ?? 0,
        );
    }
}
