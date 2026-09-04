<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use FGTCLB\AcademicBase\Settings\ValidationSet;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Builds {@see AcademicPersonsSettings} from the merged
 * `Configuration/AcademicPersons/Settings.yaml` of all active packages.
 *
 * The loader of `academic_base` does the package walk and the cache round
 * trip; this class owns the persons shape - which top-level maps exist and
 * how their entries become sections and fields - and hands every flag list
 * to the shared normaliser.
 *
 * @internal not part of public API.
 */
class AcademicPersonsSettingsFactory
{
    /**
     * The document validators of a profile information section address the
     * fields under the names an editor sees. The DTO and domain properties
     * behind them differ for these three.
     */
    private const DOCUMENT_PROPERTY_ALIASES = [
        'from' => 'yearStart',
        'to' => 'yearEnd',
        'description' => 'bodytext',
    ];

    /**
     * The same for a contracts section configured without `contracts.fields`;
     * with them, the contract fields are the validation source.
     */
    private const CONTRACT_PROPERTY_ALIASES = [
        'from' => 'validFrom',
        'to' => 'validTo',
    ];

    private const DOCUMENT_VALIDATION_FLAGS = [
        'required',
        'readonly',
        'disabled',
        'email',
        'url',
        'number',
        'tel',
        'date',
        'textarea',
        'html',
    ];

    public function __construct(
        protected readonly SettingsFileLoader $settingsFileLoader,
        protected readonly ValidationNormalizer $validationNormalizer,
    ) {}

    public function get(): AcademicPersonsSettings
    {
        return $this->settingsFileLoader->load(
            'Configuration/AcademicPersons/Settings.yaml',
            'AcademicPersons_Settings_v3',
            AcademicPersonsSettings::class,
            $this->normalize(...),
        );
    }

    /**
     * Builds the settings graph from an already merged configuration array.
     *
     * @param array<string, mixed> $settings
     */
    public function normalize(array $settings): AcademicPersonsSettings
    {
        $contractFields = $this->normalizeContractFields($settings);
        return new AcademicPersonsSettings(
            profileSections: $this->normalizeProfileSections($settings),
            specialFields: $this->normalizeSpecialFields($settings),
            contractFields: $contractFields,
            contractContactSections: $this->normalizeContractContactSections($settings),
            documentSections: $this->normalizeDocumentSections($settings, $contractFields),
            publicProfile: $this->normalizePublicProfile($settings),
            raw: $settings,
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function normalizePublicProfile(array $settings): PublicProfileSettings
    {
        $configuredPublicProfile = $settings['profile'] ?? null;
        if (!is_array($configuredPublicProfile)) {
            return new PublicProfileSettings();
        }
        $structure = [];
        $configuredStructure = is_array($configuredPublicProfile['structure'] ?? null)
            ? $configuredPublicProfile['structure']
            : [];
        foreach ($configuredStructure as $columnIdentifier => $elementIdentifiers) {
            if (!is_string($columnIdentifier) || $columnIdentifier === '' || !is_array($elementIdentifiers)) {
                continue;
            }
            $structure[$columnIdentifier] = $this->normalizePublicProfileList($elementIdentifiers);
        }
        $details = [];
        $configuredDetails = is_array($configuredPublicProfile['details'] ?? null)
            ? $configuredPublicProfile['details']
            : [];
        foreach ($configuredDetails as $detailIdentifier => $detailConfiguration) {
            if (!is_string($detailIdentifier) || $detailIdentifier === '') {
                continue;
            }
            if (is_string($detailConfiguration) && $detailConfiguration !== '') {
                $details[$detailIdentifier] = $detailConfiguration;
                continue;
            }
            if (!is_array($detailConfiguration)) {
                continue;
            }
            $details[$detailIdentifier] = array_is_list($detailConfiguration)
                ? $this->normalizePublicProfileList($detailConfiguration)
                : $this->normalizePublicProfileMap($detailConfiguration);
        }
        return new PublicProfileSettings(structure: $structure, details: $details);
    }

    /**
     * @param array<int, mixed> $configuredValues
     * @return list<string>
     */
    private function normalizePublicProfileList(array $configuredValues): array
    {
        $values = [];
        foreach ($configuredValues as $configuredValue) {
            if (!is_string($configuredValue)) {
                continue;
            }
            $value = trim($configuredValue);
            if ($value !== '' && !in_array($value, $values, true)) {
                $values[] = $value;
            }
        }
        return $values;
    }

    /**
     * A string-to-string map: non-string keys and values are dropped, values are
     * trimmed, an empty value is dropped. Used for the detail maps of the public
     * profile, the help texts of a document section and the renderer settings
     * of a special field alike.
     *
     * @param array<string|int, mixed> $configuredValues
     * @return array<string, string>
     */
    private function normalizePublicProfileMap(array $configuredValues): array
    {
        $values = [];
        foreach ($configuredValues as $configuredIdentifier => $configuredValue) {
            if (!is_string($configuredIdentifier)
                || $configuredIdentifier === ''
                || !is_string($configuredValue)
            ) {
                continue;
            }
            $value = trim($configuredValue);
            if ($value !== '') {
                $values[$configuredIdentifier] = $value;
            }
        }
        return $values;
    }

    /**
     * Every entry of `profile` apart from the layout keys is a field; the
     * fields are grouped by their `section`, in file order, and the first
     * field of a section decides the section's position.
     *
     * @param array<string, mixed> $settings
     * @return array<string, ProfileSection>
     */
    private function normalizeProfileSections(array $settings): array
    {
        $profile = $settings['profile'] ?? null;
        if (!is_array($profile)) {
            return [];
        }
        $groupedFields = [];
        $sectionPositions = [];
        foreach ($profile as $identifier => $options) {
            if (in_array((string)$identifier, ['structure', 'details'], true)) {
                continue;
            }
            if (!is_array($options)) {
                continue;
            }
            $sectionIdentifier = (string)($options['section'] ?? '');
            $renderType = (string)($options['renderType'] ?? '');
            $propertyName = (string)($options['propertyName'] ?? $identifier);
            $fieldName = (string)($options['fieldName'] ?? GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName));
            $field = new ProfileField(
                identifier: (string)$identifier,
                section: $sectionIdentifier,
                propertyName: $propertyName,
                fieldName: $fieldName,
                fieldType: (string)($options['fieldType'] ?? ''),
                renderType: $renderType,
                validation: $this->validationNormalizer->normalizeValidation(
                    identifier: $propertyName,
                    flags: $this->flagList($options['validators'] ?? null),
                    fieldName: $fieldName,
                    renderType: $renderType,
                    characterLimit: $this->normalizeFieldCharacterLimit($options, $renderType),
                ),
                position: count($groupedFields[$sectionIdentifier] ?? []),
                helptext: trim((string)($options['helptext'] ?? '')),
            );
            if (!$field->isValid()) {
                continue;
            }
            if (!array_key_exists($sectionIdentifier, $sectionPositions)) {
                $sectionPositions[$sectionIdentifier] = count($sectionPositions);
            }
            $groupedFields[$sectionIdentifier][$field->identifier] = $field;
        }
        $sections = [];
        foreach ($groupedFields as $identifier => $fields) {
            $sections[$identifier] = new ProfileSection(
                identifier: $identifier,
                fields: $fields,
                validationSet: $this->validationSetOf($identifier, $fields),
                position: $sectionPositions[$identifier],
            );
        }
        return $sections;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, SpecialField>
     */
    private function normalizeSpecialFields(array $settings): array
    {
        $configuredFields = $settings['special'] ?? null;
        if (!is_array($configuredFields)) {
            return [];
        }
        $fields = [];
        foreach ($configuredFields as $identifier => $options) {
            if (!is_array($options)) {
                continue;
            }
            $renderType = (string)($options['renderType'] ?? '');
            $field = new SpecialField(
                identifier: (string)$identifier,
                type: strtolower((string)($options['type'] ?? '')),
                fieldType: (string)($options['fieldType'] ?? ''),
                renderType: $renderType,
                fieldIdentifiers: $this->normalizePublicProfileList(
                    is_array($options['fields'] ?? null) ? $options['fields'] : [],
                ),
                validation: $this->validationNormalizer->normalizeValidation(
                    identifier: (string)$identifier,
                    flags: $this->flagList($options['validators'] ?? null),
                    renderType: $renderType,
                ),
                position: count($fields),
                settings: $this->normalizePublicProfileMap(
                    is_array($options['settings'] ?? null) ? $options['settings'] : [],
                ),
            );
            if ($field->isValid()) {
                $fields[$field->identifier] = $field;
            }
        }
        return $fields;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, ContractField>
     */
    private function normalizeContractFields(array $settings): array
    {
        $contracts = $settings['contracts'] ?? null;
        $configuredFields = is_array($contracts) ? ($contracts['fields'] ?? null) : null;
        if (!is_array($configuredFields)) {
            return [];
        }
        $fields = [];
        foreach ($configuredFields as $identifier => $options) {
            if (!is_array($options)) {
                continue;
            }
            $propertyName = (string)($options['propertyName'] ?? $identifier);
            $fieldName = (string)($options['fieldName'] ?? GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName));
            $renderType = (string)($options['renderType'] ?? '');
            $field = new ContractField(
                identifier: (string)$identifier,
                propertyName: $propertyName,
                fieldName: $fieldName,
                fieldType: (string)($options['fieldType'] ?? ''),
                renderType: $renderType,
                optionSource: trim((string)($options['options'] ?? '')),
                helptext: trim((string)($options['helptext'] ?? '')),
                validation: $this->validationNormalizer->normalizeValidation(
                    identifier: $propertyName,
                    flags: $this->flagList($options['validators'] ?? null),
                    fieldName: $fieldName,
                    renderType: $renderType,
                    characterLimit: $this->normalizeFieldCharacterLimit($options, $renderType),
                ),
                position: count($fields),
                autocomplete: trim((string)($options['autocomplete'] ?? '')),
            );
            if ($field->isValid()) {
                $fields[$field->identifier] = $field;
            }
        }
        return $fields;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, ContractContactSection>
     */
    private function normalizeContractContactSections(array $settings): array
    {
        $contracts = $settings['contracts'] ?? null;
        $configuredSections = is_array($contracts) ? ($contracts['contactSections'] ?? null) : null;
        if (!is_array($configuredSections)) {
            return [];
        }
        $sections = [];
        foreach ($configuredSections as $sectionIdentifier => $sectionOptions) {
            if (!is_array($sectionOptions)) {
                continue;
            }
            $configuredFields = $sectionOptions['fields'] ?? null;
            if (!is_array($configuredFields)) {
                continue;
            }
            $fields = [];
            foreach ($configuredFields as $identifier => $options) {
                if (!is_array($options)) {
                    continue;
                }
                $propertyName = (string)($options['propertyName'] ?? $identifier);
                $fieldName = (string)($options['fieldName'] ?? GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName));
                $renderType = (string)($options['renderType'] ?? '');
                $field = new ContractContactField(
                    identifier: (string)$identifier,
                    section: (string)$sectionIdentifier,
                    propertyName: $propertyName,
                    fieldName: $fieldName,
                    fieldType: (string)($options['fieldType'] ?? ''),
                    renderType: $renderType,
                    validation: $this->validationNormalizer->normalizeValidation(
                        identifier: $propertyName,
                        flags: $this->flagList($options['validators'] ?? null),
                        fieldName: $fieldName,
                        renderType: $renderType,
                    ),
                    position: count($fields),
                    autocomplete: trim((string)($options['autocomplete'] ?? '')),
                    helptext: trim((string)($options['helptext'] ?? '')),
                );
                if ($field->isValid()) {
                    $fields[$field->identifier] = $field;
                }
            }
            if ($fields === []) {
                continue;
            }
            $sections[(string)$sectionIdentifier] = new ContractContactSection(
                identifier: (string)$sectionIdentifier,
                fields: $fields,
                validationSet: $this->validationSetOf((string)$sectionIdentifier, $fields),
                position: count($sections),
            );
        }
        return $sections;
    }

    /**
     * A document section whose `type` names another top-level map - the
     * shipped `contracts` entry - takes that map as its defaults. The
     * contracts section validates against the contract fields; every other
     * section against its own `validators` map.
     *
     * @param array<string, mixed> $settings
     * @param array<string, ContractField> $contractFields
     * @return array<string, DocumentSection>
     */
    private function normalizeDocumentSections(array $settings, array $contractFields = []): array
    {
        $configuredSections = $settings['documentSections'] ?? null;
        if (!is_array($configuredSections)) {
            return [];
        }
        $sections = [];
        foreach ($configuredSections as $identifier => $options) {
            if (!is_array($options)) {
                continue;
            }
            $sectionIdentifier = (string)$identifier;
            $sectionType = (string)($options['type'] ?? '');
            $referencedConfiguration = $settings[$sectionType] ?? null;
            if (is_array($referencedConfiguration)) {
                $options = array_replace($referencedConfiguration, $options);
            }
            $contractSection = $sectionIdentifier === 'contracts'
                || in_array($sectionType, ['contract', 'contracts'], true);
            $propertyAliases = $contractSection
                ? self::CONTRACT_PROPERTY_ALIASES
                : self::DOCUMENT_PROPERTY_ALIASES;
            $validations = [];
            if ($contractSection && $contractFields !== []) {
                foreach ($contractFields as $field) {
                    $validations[$field->propertyName] = $field->validation;
                }
            } else {
                $configuredValidations = is_array($options['validators'] ?? null) ? $options['validators'] : [];
                foreach ($configuredValidations as $fieldIdentifier => $validationConfiguration) {
                    if (!is_array($validationConfiguration)) {
                        continue;
                    }
                    $propertyName = $propertyAliases[(string)$fieldIdentifier]
                        ?? (string)$fieldIdentifier;
                    $validations[$propertyName] = $this->validationNormalizer->normalizeValidation(
                        identifier: $propertyName,
                        flags: $this->normalizeDocumentValidationFlags($validationConfiguration),
                        characterLimit: $this->normalizeDocumentCharacterLimit($validationConfiguration),
                    );
                }
            }
            $section = new DocumentSection(
                identifier: $sectionIdentifier,
                label: (string)($options['label'] ?? ''),
                type: $sectionType,
                fieldName: (string)($options['fieldName'] ?? ''),
                readOnly: (bool)($options['readonly'] ?? false),
                validationSet: new ValidationSet(identifier: $sectionIdentifier, validations: $validations),
                position: count($sections),
                rowFields: $this->normalizeDocumentOptionList(
                    $options['rowFields'] ?? null,
                    $contractSection
                        ? DocumentSection::SUPPORTED_CONTRACT_ROW_FIELDS
                        : DocumentSection::SUPPORTED_PROFILE_INFORMATION_ROW_FIELDS,
                ),
                actions: $this->normalizeDocumentOptionList(
                    $options['actions'] ?? null,
                    DocumentSection::SUPPORTED_ACTIONS,
                ),
                helptexts: $this->normalizePublicProfileMap(
                    is_array($options['helptext'] ?? null) ? $options['helptext'] : [],
                ),
            );
            if ($section->isValid()) {
                $sections[$section->identifier] = $section;
            }
        }
        return $sections;
    }

    /**
     * @param mixed $configuredValues
     * @param list<string> $allowedValues
     * @return list<string>
     */
    private function normalizeDocumentOptionList(mixed $configuredValues, array $allowedValues): array
    {
        if (!is_array($configuredValues)) {
            return [];
        }
        $values = [];
        foreach ($configuredValues as $configuredValue) {
            if (!is_string($configuredValue)) {
                continue;
            }
            $value = strtolower(trim($configuredValue));
            if ($value !== '' && in_array($value, $allowedValues, true) && !in_array($value, $values, true)) {
                $values[] = $value;
            }
        }
        return $values;
    }

    /**
     * A document field accepts the plain flag list of every other field, or a
     * map: flags below `validators`, flags as `<flag>: true`, and an `editor`
     * map whose `ckeditor` type implies `html` and whose `textarea` type
     * implies `textarea`.
     *
     * @param array<int|string, mixed> $configuration
     * @return list<mixed>
     */
    private function normalizeDocumentValidationFlags(array $configuration): array
    {
        if (array_is_list($configuration)) {
            return array_values($configuration);
        }
        $flags = is_array($configuration['validators'] ?? null)
            ? array_values($configuration['validators'])
            : [];
        foreach (self::DOCUMENT_VALIDATION_FLAGS as $flag) {
            if (($configuration[$flag] ?? false) === true) {
                $flags[] = $flag;
            }
        }
        $editorType = $this->documentEditorType($configuration);
        if ($editorType === 'ckeditor') {
            $flags[] = 'html';
        } elseif ($editorType === 'textarea') {
            $flags[] = 'textarea';
        }
        return $flags;
    }

    /**
     * @param array<int|string, mixed> $configuration
     */
    private function normalizeDocumentCharacterLimit(array $configuration): int
    {
        if ($this->documentEditorType($configuration) !== 'ckeditor') {
            return 0;
        }
        $editor = is_array($configuration['editor'] ?? null) ? $configuration['editor'] : [];
        return $this->normalizeCharacterLimit($editor['limit'] ?? 0);
    }

    /**
     * @param array<int|string, mixed> $configuration
     */
    private function documentEditorType(array $configuration): string
    {
        $editor = is_array($configuration['editor'] ?? null) ? $configuration['editor'] : [];
        return strtolower(trim((string)($editor['type'] ?? '')));
    }

    /**
     * A character limit is only meaningful on a rich text control.
     *
     * @param array<string, mixed> $configuration
     */
    private function normalizeFieldCharacterLimit(array $configuration, string $renderType): int
    {
        if (strtolower(trim($renderType)) !== 'ckeditor') {
            return 0;
        }
        return $this->normalizeCharacterLimit($configuration['characterLimit'] ?? 0);
    }

    private function normalizeCharacterLimit(mixed $limit): int
    {
        if (is_int($limit)) {
            return max(0, $limit);
        }
        if (is_string($limit) && preg_match('/^\d+$/', trim($limit)) === 1) {
            return (int)trim($limit);
        }
        return 0;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function flagList(mixed $configuredFlags): array
    {
        return is_array($configuredFlags) ? $configuredFlags : [];
    }

    /**
     * @param array<string, ProfileField|ContractContactField> $fields
     */
    private function validationSetOf(string $identifier, array $fields): ValidationSet
    {
        /** @var array<string, Validation> $validations */
        $validations = [];
        foreach ($fields as $field) {
            $validations[$field->propertyName] = $field->validation;
        }
        return new ValidationSet(identifier: $identifier, validations: $validations);
    }
}
