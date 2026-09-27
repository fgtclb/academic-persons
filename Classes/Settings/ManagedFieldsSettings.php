<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * The `managedFields` map: per record type, the fields a synchronisation or an
 * import owns on the records it wrote. Each entry is resolved to its property
 * and database column through the field of the settings it names.
 *
 * A mistake in the map - an unknown record type, a field the settings do not
 * have, a value that is not a list - is recorded in `problems` rather than
 * thrown, for the reason {@see FrontendUserSyncSettings} gives: the graph is
 * built while the TCA is loaded. {@see self::assertValid()} throws it where
 * the map is used.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class ManagedFieldsSettings
{
    /**
     * The record types a field can be managed on, with their tables.
     */
    public const RECORD_TYPE_TABLES = [
        'profile' => 'tx_academicpersons_domain_model_profile',
        'contracts' => 'tx_academicpersons_domain_model_contract',
        'emailAddresses' => 'tx_academicpersons_domain_model_email',
        'phoneNumbers' => 'tx_academicpersons_domain_model_phone_number',
        'physicalAddresses' => 'tx_academicpersons_domain_model_address',
    ];

    /**
     * @param array<string, array<string, string>> $fields record type => property => column
     * @param list<string> $problems what the map got wrong, one sentence each
     */
    public function __construct(
        public readonly array $fields = [],
        public readonly array $problems = [],
    ) {}

    /**
     * @param array{
     *     fields?: array<string, array<string, string>>,
     *     problems?: list<string>,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            fields: $array['fields'] ?? [],
            problems: $array['problems'] ?? [],
        );
    }

    /**
     * @throws \UnexpectedValueException when the map has a problem
     */
    public function assertValid(): void
    {
        if ($this->problems === []) {
            return;
        }
        throw new \UnexpectedValueException(
            'The managedFields map of Configuration/AcademicPersons/Settings.yaml is invalid: '
            . implode(' ', $this->problems),
            1790536034,
        );
    }

    /**
     * The managed database columns of a table, empty for a table that is no
     * record type of the map.
     *
     * @return list<string>
     */
    public function getColumns(string $tableName): array
    {
        $recordType = array_search($tableName, self::RECORD_TYPE_TABLES, true);
        if ($recordType === false) {
            return [];
        }
        return array_values($this->fields[$recordType] ?? []);
    }
}
