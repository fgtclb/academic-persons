<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Whether a project field of the persons settings may use the column it names.
 *
 * A project field names a column a site package added to the profile table. The
 * column has to be in the TCA of that table, it must not be a system column or a
 * column of the shipped {@see Profile} model, and its TCA type has to hold a plain
 * value: the frontend editor writes one scalar into it. The shipped model is the
 * class itself, so a project that maps its column in a model XCLASS can still
 * declare it. The renderer of the field has to show a plain value and fit the
 * type: a checkbox for a `check` column and only there, rich text for a `text`
 * column. An `email` or `link` column needs the validator the DataHandler would
 * otherwise apply by itself, after the regular fields of the request are stored:
 * it empties an invalid value and reports an error. A `link` column has to take web
 * addresses, the one link type the editor accepts.
 *
 * The TCA listener asks while the TCA is compiled, the frontend editor whenever it
 * renders or writes a project field. Both hand in the TCA of the profile table, so
 * they cannot disagree about a column.
 *
 * @internal not part of public API.
 */
final readonly class ProjectProfileFieldCheck
{
    public const TABLE = 'tx_academicpersons_domain_model_profile';

    /**
     * The TCA types whose value is one scalar the editor can write as it is.
     */
    public const SUPPORTED_TCA_TYPES = ['input', 'text', 'email', 'link', 'number', 'check'];

    /**
     * The renderers that show a value of a list or of several columns.
     */
    private const UNSUPPORTED_RENDER_TYPES = ['select', 'combinedlink'];

    /**
     * The `ctrl` keys naming a column the core or the workspaces fill.
     */
    private const SYSTEM_CONTROL_KEYS = [
        'tstamp',
        'crdate',
        'delete',
        'sortby',
        'languageField',
        'transOrigPointerField',
        'transOrigDiffSourceField',
        'translationSource',
        'origUid',
        'editlock',
        'descriptionColumn',
        'type',
    ];

    /**
     * What is wrong with the column of a project field, or null when the field may
     * use it. The message names the field, the column and the reason.
     *
     * @param array<string, mixed> $profileTca the TCA of the profile table, `ctrl` and `columns`
     */
    public function findProblem(ProfileField $field, array $profileTca): ?string
    {
        if ($field->fieldName === '') {
            return sprintf(
                'The project field "profile.%s" of the persons settings names no column. Set its "fieldName".',
                $field->identifier,
            );
        }
        $reason = $this->findReason($field, $profileTca);
        if ($reason === null) {
            return null;
        }
        return sprintf(
            'The project field "profile.%s" of the persons settings cannot use the column "%s" of the table "%s": %s.',
            $field->identifier,
            $field->fieldName,
            self::TABLE,
            $reason,
        );
    }

    /**
     * @param array<string, mixed> $profileTca
     */
    private function findReason(ProfileField $field, array $profileTca): ?string
    {
        $modelProperties = $this->getModelProperties();
        if (in_array($field->propertyName, $modelProperties, true)) {
            return sprintf(
                'its identifier "%s" is a property of the profile model, name the project field differently',
                $field->propertyName,
            );
        }
        $columns = is_array($profileTca['columns'] ?? null) ? $profileTca['columns'] : [];
        if (!is_array($columns[$field->fieldName] ?? null)) {
            return 'the TCA of the table has no such column';
        }
        if (in_array($field->fieldName, $this->getSystemColumns($profileTca), true)) {
            return 'it is a system column of the table';
        }
        foreach ($modelProperties as $propertyName) {
            if (GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName) === $field->fieldName) {
                return sprintf('it belongs to the property "%s" of the profile model', $propertyName);
            }
        }
        $type = $columns[$field->fieldName]['config']['type'] ?? null;
        if (!in_array($type, self::SUPPORTED_TCA_TYPES, true)) {
            return sprintf(
                'its TCA type "%s" holds no plain value, use one of: %s',
                is_string($type) ? $type : '',
                implode(', ', self::SUPPORTED_TCA_TYPES),
            );
        }
        $renderType = strtolower($field->renderType);
        if (in_array($renderType, self::UNSUPPORTED_RENDER_TYPES, true)) {
            return sprintf('the renderer "%s" is not available for a project field', $field->renderType);
        }
        if (($type === 'check') !== ($renderType === 'checkbox')) {
            return $type === 'check'
                ? sprintf('a column of the TCA type "check" needs the renderer "checkbox", not "%s"', $field->renderType)
                : sprintf('the renderer "checkbox" needs a column of the TCA type "check", not "%s"', $type);
        }
        if ($renderType === 'ckeditor' && $type !== 'text') {
            return sprintf('the renderer "ckeditor" needs a column of the TCA type "text", not "%s"', $type);
        }
        $requiredFlag = ['email' => 'email', 'link' => 'url'][$type] ?? null;
        if ($requiredFlag !== null && !in_array($requiredFlag, $field->validation->flags, true)) {
            return sprintf(
                'a column of the TCA type "%s" needs the validator "%s", which checks the value before anything is stored',
                $type,
                $requiredFlag,
            );
        }
        $allowedTypes = $columns[$field->fieldName]['config']['allowedTypes'] ?? null;
        if ($type === 'link' && is_array($allowedTypes) && ($allowedTypes[0] ?? '') !== '*' && !in_array('url', $allowedTypes, true)) {
            return 'its "allowedTypes" do not include "url", the one link type the editor accepts';
        }
        return null;
    }

    /**
     * @param array<string, mixed> $profileTca
     * @return list<string>
     */
    private function getSystemColumns(array $profileTca): array
    {
        $control = is_array($profileTca['ctrl'] ?? null) ? $profileTca['ctrl'] : [];
        $columns = ['uid', 'pid'];
        foreach (self::SYSTEM_CONTROL_KEYS as $key) {
            if (is_string($control[$key] ?? null) && $control[$key] !== '') {
                $columns[] = $control[$key];
            }
        }
        foreach (is_array($control['enablecolumns'] ?? null) ? $control['enablecolumns'] : [] as $column) {
            if (is_string($column) && $column !== '') {
                $columns[] = $column;
            }
        }
        foreach (array_keys(is_array($profileTca['columns'] ?? null) ? $profileTca['columns'] : []) as $column) {
            if (str_starts_with((string)$column, 't3ver_')) {
                $columns[] = (string)$column;
            }
        }
        return $columns;
    }

    /**
     * The properties of the shipped model, without the internal state of Extbase.
     *
     * @return list<string>
     */
    private function getModelProperties(): array
    {
        $properties = [];
        foreach ((new \ReflectionClass(Profile::class))->getProperties() as $property) {
            if (!$property->isStatic() && !str_starts_with($property->getName(), '_')) {
                $properties[] = $property->getName();
            }
        }
        return $properties;
    }
}
