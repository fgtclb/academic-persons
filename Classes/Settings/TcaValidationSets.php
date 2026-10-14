<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Settings\TcaValidationMerger;
use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationSet;
use FGTCLB\AcademicPersons\EventListener\ApplySettingsToTca;

/**
 * The validation sets the persons settings merge into the TCA of the person
 * tables, and the columns two fields of one of them name.
 *
 * {@see ApplySettingsToTca} merges the sets this class returns. The upgrade
 * check and the persons settings entry of the status report ask it for the
 * shared columns of the same sets.
 *
 * {@see TcaValidationMerger} assigns the TCA fragment of each validation to its
 * column, and the profile information sections are assigned per record type the
 * same way. Of two fields naming one column, the one applied later therefore
 * decides the backend flags of the column, and the flags of the earlier one are
 * lost. The profile set applies the profile fields in section order, then the
 * special fields. That is the documented behaviour, and a shared column is
 * reported so that the loss is not silent.
 *
 * Two cases are not reported:
 *
 * - Fields that collapse while the settings are built never reach a set. Two
 *   fields of one set with the same property name keep only the later one, so
 *   do two profile fields of one property name in different sections, which
 *   are combined with `array_replace()`, and a special field whose identifier
 *   is the property name of a profile field replaces that field.
 * - The listener refuses project fields against the TCA before the merge, the
 *   reports against the merged `$GLOBALS['TCA']`. The merge can turn a column
 *   into an `email` or `number` column, so the two can judge a project field
 *   differently, which changes what is reported only where the field shares
 *   its column with another field.
 *
 * @internal not part of public API.
 */
final readonly class TcaValidationSets
{
    public const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    public const PROFILE_INFORMATION_TABLE = 'tx_academicpersons_domain_model_profile_information';

    public function __construct(
        private AcademicPersonsSettings $academicPersonsSettings,
        private ProjectProfileFieldCheck $projectProfileFieldCheck,
    ) {}

    /**
     * The set of each person table the TCA has, keyed by table, with the
     * validations of the columns that table has. A fragment for a column the TCA
     * does not have would be a column without a type.
     *
     * The profile set is every profile section plus the special fields addressing
     * a profile column, except the owner's visibility switch of the profile
     * editor: it writes the `disabled` enable column, and taking it away from
     * owners must not take the checkbox away from backend editors. A project
     * field whose column is refused is left out as well.
     *
     * @param array<string, mixed> $tca
     * @param array<string, string> $refusedProjectFields what {@see findRefusedProjectFields()} returns
     *        for the same TCA, which the listener announces before it merges
     * @return array<string, ValidationSet>
     */
    public function forTables(array $tca, array $refusedProjectFields): array
    {
        $validationSets = [
            self::PROFILE_TABLE => $this->getProfileValidationSet($tca, array_keys($refusedProjectFields)),
            'tx_academicpersons_domain_model_contract' => $this->academicPersonsSettings->getDocumentValidationSet('contracts'),
            'tx_academicpersons_domain_model_email' => $this->academicPersonsSettings->getContractContactValidationSet('emailAddresses'),
            'tx_academicpersons_domain_model_phone_number' => $this->academicPersonsSettings->getContractContactValidationSet('phoneNumbers'),
            'tx_academicpersons_domain_model_address' => $this->academicPersonsSettings->getContractContactValidationSet('physicalAddresses'),
        ];
        $tableSets = [];
        foreach ($validationSets as $table => $validationSet) {
            $columns = $tca[$table]['columns'] ?? null;
            if (!is_array($columns)) {
                continue;
            }
            $tableSets[$table] = new ValidationSet(
                identifier: $validationSet->identifier,
                validations: array_filter(
                    $validationSet->validations,
                    static fn(Validation $validation): bool => isset($columns[$validation->fieldName]),
                ),
            );
        }
        return $tableSets;
    }

    /**
     * What is wrong with the column of each project field the profile table
     * cannot give its settings, keyed by the property name of the field.
     *
     * @param array<string, mixed> $tca
     * @return array<string, string>
     */
    public function findRefusedProjectFields(array $tca): array
    {
        $profileTca = $tca[self::PROFILE_TABLE] ?? null;
        if (!is_array($profileTca) || !is_array($profileTca['columns'] ?? null)) {
            return [];
        }
        $problems = [];
        foreach ($this->academicPersonsSettings->getCustomProfileFields() as $propertyName => $field) {
            $problem = $this->projectProfileFieldCheck->findProblem($field, $profileTca);
            if ($problem !== null) {
                $problems[$propertyName] = $problem;
            }
        }
        return $problems;
    }

    /**
     * Every column that two or more fields of one set name, among the fields
     * whose TCA fragment the merge applies: the sets of {@see forTables()}, and
     * the profile information sections per record type. A validation without a
     * TCA fragment applies nothing and loses nothing, so it is not counted.
     *
     * @param array<string, mixed> $tca
     * @return list<SharedColumn>
     */
    public function findSharedColumns(array $tca): array
    {
        $sharedColumns = [];
        foreach ($this->forTables($tca, $this->findRefusedProjectFields($tca)) as $table => $validationSet) {
            $fieldsByColumn = [];
            foreach ($validationSet->validations as $fieldIdentifier => $validation) {
                if ($validation->tcaConfig !== []) {
                    $fieldsByColumn[$validation->fieldName][] = (string)$fieldIdentifier;
                }
            }
            array_push($sharedColumns, ...$this->collectSharedColumns($table, '', $fieldsByColumn));
        }
        if (!is_array($tca[self::PROFILE_INFORMATION_TABLE]['types'] ?? null)) {
            return $sharedColumns;
        }
        // The order of AcademicPersonsSettings::getDocumentValidationTcaTypesConfig(),
        // which assigns the fragment of a later section of a record type over an
        // earlier one of the same type.
        $fieldsByTypeAndColumn = [];
        foreach ($this->academicPersonsSettings->documentSections as $section) {
            if ($section->isContractSection()) {
                continue;
            }
            foreach ($section->validationSet->validations as $fieldIdentifier => $validation) {
                if ($validation->tcaConfig !== []) {
                    $fieldsByTypeAndColumn[$section->type][$validation->fieldName][]
                        = $section->identifier . '.' . $fieldIdentifier;
                }
            }
        }
        foreach ($fieldsByTypeAndColumn as $recordType => $fieldsByColumn) {
            array_push(
                $sharedColumns,
                ...$this->collectSharedColumns(self::PROFILE_INFORMATION_TABLE, (string)$recordType, $fieldsByColumn),
            );
        }
        return $sharedColumns;
    }

    /**
     * @param array<string, mixed> $tca
     * @param list<string> $refusedProperties
     */
    private function getProfileValidationSet(array $tca, array $refusedProperties): ValidationSet
    {
        $validationSet = $this->academicPersonsSettings->getProfileUpdateValidationSet();
        $disabledColumn = $tca[self::PROFILE_TABLE]['ctrl']['enablecolumns']['disabled'] ?? null;
        return new ValidationSet(
            identifier: $validationSet->identifier,
            validations: array_filter(
                $validationSet->validations,
                static fn(Validation $validation, string $propertyName): bool => $validation->fieldName !== $disabledColumn
                    && !in_array($propertyName, $refusedProperties, true),
                ARRAY_FILTER_USE_BOTH,
            ),
        );
    }

    /**
     * @param array<array-key, list<string>> $fieldsByColumn
     * @return list<SharedColumn>
     */
    private function collectSharedColumns(string $table, string $recordType, array $fieldsByColumn): array
    {
        $sharedColumns = [];
        foreach ($fieldsByColumn as $column => $fields) {
            if (count($fields) > 1) {
                $sharedColumns[] = new SharedColumn(
                    table: $table,
                    column: (string)$column,
                    fields: $fields,
                    recordType: $recordType,
                );
            }
        }
        return $sharedColumns;
    }
}
