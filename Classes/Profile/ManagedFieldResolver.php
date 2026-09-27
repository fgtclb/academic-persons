<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Profile;

use FGTCLB\AcademicPersons\Domain\Model\Address;
use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ManagedFieldsSettings;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Schema\Field\FieldTranslationBehaviour;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;

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
 * The backend form asks with a row and gets database columns, the frontend
 * editor asks with a domain model and gets its property names. Both answers
 * come from the same checks.
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
        private TcaSchemaFactory $tcaSchemaFactory,
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
     * The managed properties of a record, given as its domain model.
     *
     * The model is the record the caller writes. A model overlaid in another
     * language is a translation, which the synchronisation does not write, so
     * only the managed properties whose column all languages share
     * (`l10n_mode` `exclude`) are managed on it, decided on its
     * default-language record: the backend shows those read-only on a
     * translation as well. The contract of a contact and the profile of a
     * contract are read from the database by the record's uid, because a
     * relation of the model may be empty when its parent is hidden.
     *
     * @return list<string>
     * @throws \UnexpectedValueException when the map has a problem
     */
    public function getManagedProperties(Profile|Contract|Address|Email|PhoneNumber $record): array
    {
        $tableName = $this->getTableName($record);
        $managedFields = $this->settings->managedFields;
        $managedFields->assertValid();
        if ((int)$record->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID) > 0) {
            return $this->getSharedProperties($tableName, $this->getManagedPropertiesOfDefaultLanguage($record));
        }
        $properties = $managedFields->getProperties($tableName);
        $uid = (int)$record->getUid();
        if ($properties === []
            || $uid <= 0
            || trim($record->getImportIdentifier()) === ''
        ) {
            return [];
        }
        $synchronised = match (true) {
            $record instanceof Profile => !$record->getSkipSync(),
            $record instanceof Contract => $this->isSynchronised(
                $tableName,
                $this->findRow($tableName, $uid, 'profile') ?? [],
            ),
            default => $this->isSynchronised($tableName, $this->findRow($tableName, $uid, 'contract') ?? []),
        };
        return $synchronised ? $properties : [];
    }

    /**
     * The managed properties of the default-language record behind a domain
     * model, also when the model is overlaid in another language.
     *
     * A model of a translated site language carries the uid of its
     * default-language record, and that is the row Extbase removes on a
     * delete. Whether a row may be deleted is therefore decided here, on the
     * stored default-language row, while its fields follow the record the
     * editor writes, see {@see self::getManagedProperties()}.
     *
     * @return list<string>
     * @throws \UnexpectedValueException when the map has a problem
     */
    public function getManagedPropertiesOfDefaultLanguage(Profile|Contract|Address|Email|PhoneNumber $record): array
    {
        $tableName = $this->getTableName($record);
        $managedFields = $this->settings->managedFields;
        $managedFields->assertValid();
        $properties = $managedFields->getProperties($tableName);
        $uid = (int)$record->getUid();
        if ($properties === [] || $uid <= 0) {
            return [];
        }
        $parentColumn = match ($tableName) {
            self::PROFILE_TABLE => 'skip_sync',
            self::CONTRACT_TABLE => 'profile',
            default => 'contract',
        };
        $row = $this->findRow($tableName, $uid, 'import_identifier', 'sys_language_uid', $parentColumn);
        if ($row === null
            || $this->getImportIdentifier($row) === ''
            || $this->getInt($row, 'sys_language_uid') > 0
            || !$this->isSynchronised($tableName, $row)
        ) {
            return [];
        }
        return $properties;
    }

    /**
     * @param list<string> $properties
     * @return list<string> the properties whose column all languages share
     */
    private function getSharedProperties(string $tableName, array $properties): array
    {
        if ($properties === []) {
            return [];
        }
        $columns = $this->settings->managedFields->getColumnsByProperty($tableName);
        $schema = $this->tcaSchemaFactory->get($tableName);
        return array_values(array_filter(
            $properties,
            static fn(string $property): bool => isset($columns[$property])
                && $schema->hasField($columns[$property])
                && $schema->getField($columns[$property])->getTranslationBehaviour() === FieldTranslationBehaviour::Excluded,
        ));
    }

    private function getTableName(Profile|Contract|Address|Email|PhoneNumber $record): string
    {
        return match (true) {
            $record instanceof Profile => self::PROFILE_TABLE,
            $record instanceof Contract => self::CONTRACT_TABLE,
            $record instanceof Address => ManagedFieldsSettings::RECORD_TYPE_TABLES['physicalAddresses'],
            $record instanceof Email => ManagedFieldsSettings::RECORD_TYPE_TABLES['emailAddresses'],
            $record instanceof PhoneNumber => ManagedFieldsSettings::RECORD_TYPE_TABLES['phoneNumbers'],
        };
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
    private function findRow(string $tableName, int $uid, string ...$columns): ?array
    {
        if ($uid <= 0) {
            return null;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());
        $row = $queryBuilder
            ->select(...$columns)
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
