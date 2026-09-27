<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Profile;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ManagedFieldsSettings;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

/**
 * Answers which fields of a person record a synchronisation owns, as the
 * `managedFields` map of `Configuration/AcademicPersons/Settings.yaml` says.
 *
 * A field is managed on a record that carries an import identifier, is a
 * default-language record, and whose profile takes part in the
 * synchronisation. The profile of a contract or a contact is looked up past
 * its visibility, because the synchronisation keeps writing a hidden profile.
 * A record whose profile is excluded with `skip_sync`, or cannot be found at
 * all, has no managed field.
 *
 * The profile and the contract of a record are read as they are live, without
 * a workspace overlay. In a workspace, switching `skip_sync` on unlocks the
 * profile form at once, and its contracts and contact records only once the
 * profile is published.
 *
 * @internal not part of public API.
 */
final readonly class ManagedFieldResolver
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    private const CONTRACT_TABLE = 'tx_academicpersons_domain_model_contract';
    private const CONTACT_TABLES = [
        'tx_academicpersons_domain_model_address',
        'tx_academicpersons_domain_model_email',
        'tx_academicpersons_domain_model_phone_number',
    ];

    public function __construct(
        private AcademicPersonsSettings $settings,
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * The managed database columns of a record, given as a row of its table.
     * The row needs `import_identifier` and `sys_language_uid`, and
     * `skip_sync` on a profile, `profile` on a contract, `contract` on a
     * contact.
     *
     * A table that is not a person table is answered before the map is checked:
     * FormEngine asks for every record it compiles, and a mistake in the map
     * must not take the forms of other tables down.
     *
     * @param array<string, mixed> $row
     * @return list<string>
     * @throws \UnexpectedValueException when the map has a problem
     */
    public function getManagedColumns(string $tableName, array $row): array
    {
        if (!in_array($tableName, ManagedFieldsSettings::RECORD_TYPE_TABLES, true)) {
            return [];
        }
        $managedFields = $this->settings->managedFields;
        $managedFields->assertValid();
        $columns = $managedFields->getColumns($tableName);
        if ($columns === []
            || $this->getImportIdentifier($row) === ''
            || $this->getInt($row, 'sys_language_uid') > 0
            || !$this->isSynchronised($tableName, $row)
        ) {
            return [];
        }
        return $columns;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function getImportIdentifier(array $row): string
    {
        $importIdentifier = $row['import_identifier'] ?? '';
        return is_string($importIdentifier) ? trim($importIdentifier) : '';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function isSynchronised(string $tableName, array $row): bool
    {
        if ($tableName === self::PROFILE_TABLE) {
            return $this->getInt($row, 'skip_sync') === 0;
        }
        if ($tableName === self::CONTRACT_TABLE) {
            return $this->isProfileSynchronised($this->getInt($row, 'profile'));
        }
        if (in_array($tableName, self::CONTACT_TABLES, true)) {
            $contract = $this->findRow(self::CONTRACT_TABLE, $this->getInt($row, 'contract'), 'profile');
            return $contract !== null && $this->isProfileSynchronised((int)$contract['profile']);
        }
        return false;
    }

    private function isProfileSynchronised(int $profileUid): bool
    {
        $profile = $this->findRow(self::PROFILE_TABLE, $profileUid, 'skip_sync');
        return $profile !== null && (int)$profile['skip_sync'] === 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findRow(string $tableName, int $uid, string $column): ?array
    {
        if ($uid <= 0) {
            return null;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());
        $row = $queryBuilder
            ->select($column)
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        return $row === false ? null : $row;
    }

    /**
     * FormEngine hands a row whose relation values may already be lists, the
     * database a plain value.
     *
     * @param array<string, mixed> $row
     */
    private function getInt(array $row, string $column): int
    {
        $value = $row[$column] ?? 0;
        if (is_array($value)) {
            $value = reset($value);
        }
        return is_numeric($value) ? (int)$value : 0;
    }
}
