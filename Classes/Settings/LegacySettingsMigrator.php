<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Maps the pre-3.0 shape of `Configuration/AcademicPersons/Settings.yaml`
 * onto the section maps of 3.0.
 *
 * The old shape had two top-level maps: `validations`, one flag list per
 * property of six record types, and `profileInformationsTypes`, the seven
 * timeline entry types. Both are strictly less expressive than the section
 * graph, so they overlay the merged array instead of replacing it: a legacy
 * set states the `required`, `readonly`, `disabled`, `email` and `number`
 * flags of every field of its target - a field the set does not list loses
 * those five, exactly as it was unconfigured before - and the flags the old
 * shape could not express (`url`, `date`, `tel`, `textarea`, `html`) stay as
 * the section maps declare them. One thing is not mapped losslessly: an
 * eighth timeline type declared through YAML alone cannot be restored,
 * because it needs a profile relation the settings never created. The `type` and `fieldName` of a timeline type are
 * reported rather than mapped, for the same reason: the profile relations are
 * TCA since 3.0.
 *
 * Transitional: the mapping exists so an installation keeps behaving as
 * configured on the day of the update, and it is removed in 4.0. Every
 * package that still ships a legacy key is logged once per key at warning
 * level - never as a PHP deprecation, which the test suites turn into
 * failures - and named by `academic:persons:settings:migrate` and the
 * status report.
 *
 * @internal not part of public API.
 */
final class LegacySettingsMigrator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const LEGACY_KEYS = ['validations', 'profileInformationsTypes'];

    /**
     * The five flags the old shape knew. A legacy set decides these for every
     * field of its target; every other flag of a field is kept.
     */
    private const LEGACY_FLAGS = ['required', 'disabled', 'readonly', 'email', 'number'];

    /**
     * The path of the field map each legacy validation set addresses.
     */
    private const SET_TARGETS = [
        'profile' => ['profile'],
        'contract' => ['contracts', 'fields'],
        'emailAddress' => ['contracts', 'contactSections', 'emailAddresses', 'fields'],
        'phoneNumber' => ['contracts', 'contactSections', 'phoneNumbers', 'fields'],
        'physicalAddress' => ['contracts', 'contactSections', 'physicalAddresses', 'fields'],
    ];

    /**
     * The `profile` map carries the public layout next to the fields.
     */
    private const PROFILE_LAYOUT_KEYS = ['structure', 'details'];

    /**
     * The `profileInformation` set addressed the properties of the record;
     * the document validators address the fields an editor sees.
     */
    private const PROFILE_INFORMATION_ALIASES = [
        'yearStart' => 'from',
        'yearEnd' => 'to',
        'bodytext' => 'description',
    ];

    /**
     * The two options of a legacy timeline type that used to generate the
     * profile relation. They are reported rather than mapped, see
     * {@see migrateProfileInformationTypes()}.
     */
    private const RELATION_OPTIONS = ['type', 'fieldName'];

    private const PROFILE_INFORMATION_FIELDS = ['title', 'link', 'year', 'from', 'to', 'description'];

    /**
     * @param array<string, mixed> $settings
     * @return list<string>
     */
    public function getLegacyKeys(array $settings): array
    {
        return array_values(array_filter(
            self::LEGACY_KEYS,
            static fn(string $key): bool => is_array($settings[$key] ?? null) && $settings[$key] !== [],
        ));
    }

    /**
     * Maps the legacy keys of the merged settings array onto its section maps
     * and drops them. `$packageSettings` names the packages the legacy keys
     * came from, keyed by package key: one warning is logged per package and
     * legacy key it ships. A caller that has nothing to attribute - the
     * console command reports on its own - passes none and nothing is logged.
     *
     * @param array<string, mixed> $settings
     * @param array<string, array<string, mixed>> $packageSettings
     */
    public function migrate(array $settings, array $packageSettings = []): LegacySettingsMigration
    {
        $legacyKeys = $this->getLegacyKeys($settings);
        if ($legacyKeys === []) {
            return new LegacySettingsMigration(settings: $settings);
        }
        $notes = [];
        if (in_array('validations', $legacyKeys, true)) {
            $settings = $this->migrateValidations($settings, $notes);
        }
        if (in_array('profileInformationsTypes', $legacyKeys, true)) {
            $settings = $this->migrateProfileInformationTypes($settings, $notes);
        }
        foreach (self::LEGACY_KEYS as $legacyKey) {
            unset($settings[$legacyKey]);
        }
        $migration = new LegacySettingsMigration(settings: $settings, migratedKeys: $legacyKeys, notes: $notes);
        foreach ($packageSettings as $packageKey => $packageSettingsArray) {
            foreach ($this->getLegacyKeys($packageSettingsArray) as $legacyKey) {
                $this->logger?->warning(
                    'Package "{package}" ships the legacy key "{key}" in Configuration/AcademicPersons/Settings.yaml.'
                    . ' Its values are mapped onto the section maps at runtime until academic_persons 4.0;'
                    . ' run "academic:persons:settings:migrate" and replace the key with the printed maps.{notes}',
                    [
                        'package' => (string)$packageKey,
                        'key' => $legacyKey,
                        'notes' => $this->formatNotes($migration->getNotesForKey($legacyKey)),
                    ],
                );
            }
        }
        return $migration;
    }

    /**
     * @param array<string, mixed> $settings
     * @param list<string> $notes
     * @return array<string, mixed>
     */
    private function migrateValidations(array $settings, array &$notes): array
    {
        $validations = is_array($settings['validations'] ?? null) ? $settings['validations'] : [];
        foreach ($validations as $setIdentifier => $properties) {
            $setIdentifier = (string)$setIdentifier;
            if (!is_array($properties)) {
                continue;
            }
            $setPath = 'validations.' . $setIdentifier;
            if ($setIdentifier === 'profileInformation') {
                $settings = $this->migrateProfileInformationSet($settings, $properties, $setPath, $notes);
                continue;
            }
            $targetPath = self::SET_TARGETS[$setIdentifier] ?? null;
            if ($targetPath === null) {
                $notes[] = $setPath . ': not a validation set of the previous shape, skipped';
                continue;
            }
            $fields = $this->readPath($settings, $targetPath);
            if ($fields === null) {
                $notes[] = $setPath . ': the section map "' . implode('.', $targetPath) . '" is not configured, skipped';
                continue;
            }
            $skipKeys = $setIdentifier === 'profile' ? self::PROFILE_LAYOUT_KEYS : [];
            $settings = $this->writePath(
                $settings,
                $targetPath,
                $this->overlayFieldMap($fields, $properties, $setPath, $skipKeys, $notes),
            );
        }
        return $settings;
    }

    /**
     * One legacy set for all timeline types becomes the same overlay on the
     * `validators` map of every document section that is not the contracts.
     *
     * @param array<string, mixed> $settings
     * @param array<int|string, mixed> $properties
     * @param list<string> $notes
     * @return array<string, mixed>
     */
    private function migrateProfileInformationSet(
        array $settings,
        array $properties,
        string $setPath,
        array &$notes,
    ): array {
        $legacyByField = [];
        foreach ($properties as $property => $flags) {
            $property = (string)$property;
            $field = self::PROFILE_INFORMATION_ALIASES[$property] ?? $property;
            if (!in_array($field, self::PROFILE_INFORMATION_FIELDS, true)) {
                $notes[] = $setPath . '.' . $property . ': no timeline entry field of this name, skipped';
                continue;
            }
            $flags = $this->normalizeFlags($flags);
            if ($field !== $property) {
                $notes[] = $setPath . '.' . $property . ': mapped onto documentSections.<section>.validators.' . $field;
            }
            $legacyByField[$field] = $flags;
        }
        $sections = $settings['documentSections'] ?? null;
        if (!is_array($sections)) {
            $notes[] = $setPath . ': the section map "documentSections" is not configured, skipped';
            return $settings;
        }
        foreach ($sections as $identifier => $section) {
            if (!is_array($section) || $this->isContractSection((string)$identifier, $section)) {
                continue;
            }
            $validators = is_array($section['validators'] ?? null) ? $section['validators'] : [];
            foreach ($validators as $field => $configured) {
                $validators[$field] = $this->overlayFlags($configured, $legacyByField[(string)$field] ?? []);
            }
            foreach ($legacyByField as $field => $flags) {
                if (!array_key_exists($field, $validators)) {
                    $validators[$field] = $flags;
                }
            }
            $sections[$identifier]['validators'] = $this->withoutEmptyLists($validators);
        }
        $settings['documentSections'] = $sections;
        return $settings;
    }

    /**
     * A legacy type that names a shipped document section refines its label.
     * One that does not is the eighth type: there is no profile relation for
     * it, so it is reported and not created.
     *
     * `type` and `fieldName` are **not** mapped. Until 2.4 they generated the
     * inline column of the profile table, so overriding one moved the backend
     * relation and the frontend selection together. Since 3.0 the seven
     * relations are fixed in
     * :file:`Configuration/TCA/tx_academicpersons_domain_model_profile.php`
     * and nothing regenerates them from the settings - a mapped override would
     * therefore move the frontend half alone, and records created in one
     * context would be invisible in the other. The divergence is reported per
     * key instead, and the section keeps the values that match the TCA.
     *
     * @param array<string, mixed> $settings
     * @param list<string> $notes
     * @return array<string, mixed>
     */
    private function migrateProfileInformationTypes(array $settings, array &$notes): array
    {
        $sections = $settings['documentSections'] ?? null;
        $types = is_array($settings['profileInformationsTypes'] ?? null) ? $settings['profileInformationsTypes'] : [];
        foreach ($types as $identifier => $type) {
            $identifier = (string)$identifier;
            $typePath = 'profileInformationsTypes.' . $identifier;
            if (!is_array($type)) {
                continue;
            }
            if (!is_array($sections) || !is_array($sections[$identifier] ?? null)) {
                $notes[] = $typePath . ': no document section and no profile relation of this name - a timeline'
                    . ' type added through the settings alone cannot be migrated; declare its column in a TCA'
                    . ' override of the profile table and its section below documentSections';
                continue;
            }
            $label = $type['label'] ?? null;
            if (is_string($label) && trim($label) !== '') {
                $sections[$identifier]['label'] = trim($label);
            }
            foreach (self::RELATION_OPTIONS as $option) {
                $value = $type[$option] ?? null;
                if (!is_string($value) || trim($value) === '' || trim($value) === ($sections[$identifier][$option] ?? null)) {
                    continue;
                }
                $notes[] = $typePath . '.' . $option . ': "' . trim($value) . '" is not mapped - the profile relation'
                    . ' of a timeline type is declared in the TCA of the profile table since 3.0 and is no longer'
                    . ' generated from the settings, so the section keeps "'
                    . (string)($sections[$identifier][$option] ?? '') . '"; a type of your own needs its own column'
                    . ' in a TCA override of the profile table';
            }
        }
        if (is_array($sections)) {
            $settings['documentSections'] = $sections;
        }
        return $settings;
    }

    /**
     * Applies one legacy set to a map of fields: every listed property is
     * matched by field key or by `propertyName`, and every field of the map
     * gets its five legacy flags from the set - or none.
     *
     * @param array<int|string, mixed> $fields
     * @param array<int|string, mixed> $properties
     * @param list<string> $skipKeys
     * @param list<string> $notes
     * @return array<int|string, mixed>
     */
    private function overlayFieldMap(
        array $fields,
        array $properties,
        string $setPath,
        array $skipKeys,
        array &$notes,
    ): array {
        $keysByProperty = [];
        foreach ($fields as $key => $options) {
            if (!is_array($options) || in_array((string)$key, $skipKeys, true)) {
                continue;
            }
            $keysByProperty[(string)($options['propertyName'] ?? $key)] ??= (string)$key;
        }
        $legacyByKey = [];
        foreach ($properties as $property => $flags) {
            $property = (string)$property;
            $key = $keysByProperty[$property] ?? null;
            if ($key === null && is_array($fields[$property] ?? null) && !in_array($property, $skipKeys, true)) {
                $key = $property;
            }
            if ($key === null) {
                $notes[] = $setPath . '.' . $property . ': no field of this name in the section maps, skipped';
                continue;
            }
            if ($key !== $property) {
                $notes[] = $setPath . '.' . $property . ': mapped onto the field "' . $key . '"';
            }
            $legacyByKey[$key] = $this->normalizeFlags($flags);
        }
        foreach ($fields as $key => $options) {
            if (!is_array($options) || in_array((string)$key, $skipKeys, true)) {
                continue;
            }
            $validators = $this->overlayFlags($options['validators'] ?? [], $legacyByKey[(string)$key] ?? []);
            unset($fields[$key]['validators']);
            if ($validators !== []) {
                $fields[$key]['validators'] = $validators;
            }
        }
        return $fields;
    }

    /**
     * The flags of one field after a legacy set: the field's flags outside
     * the legacy vocabulary, followed by what the set lists. A document
     * field configured as a map keeps its map, with the legacy flags
     * replaced in its `validators` list and its `<flag>: true` entries.
     *
     * @param list<string> $legacyFlags
     */
    private function overlayFlags(mixed $configured, array $legacyFlags): mixed
    {
        if (is_array($configured) && $configured !== [] && !array_is_list($configured)) {
            foreach (self::LEGACY_FLAGS as $flag) {
                unset($configured[$flag]);
            }
            $validators = $this->overlayFlagList($configured['validators'] ?? [], $legacyFlags);
            unset($configured['validators']);
            if ($validators !== []) {
                $configured['validators'] = $validators;
            }
            return $configured;
        }
        return $this->overlayFlagList($configured, $legacyFlags);
    }

    /**
     * @param list<string> $legacyFlags
     * @return list<string>
     */
    private function overlayFlagList(mixed $configured, array $legacyFlags): array
    {
        $kept = array_values(array_filter(
            $this->normalizeFlags($configured),
            static fn(string $flag): bool => !in_array($flag, self::LEGACY_FLAGS, true),
        ));
        return array_values(array_unique(array_merge($kept, $legacyFlags)));
    }

    /**
     * @return list<string>
     */
    private function normalizeFlags(mixed $flags): array
    {
        if (!is_array($flags)) {
            return [];
        }
        $normalized = [];
        foreach ($flags as $flag) {
            if (!is_string($flag)) {
                continue;
            }
            $flag = strtolower(trim($flag));
            if ($flag !== '' && !in_array($flag, $normalized, true)) {
                $normalized[] = $flag;
            }
        }
        return $normalized;
    }

    /**
     * @param array<int|string, mixed> $validators
     * @return array<int|string, mixed>
     */
    private function withoutEmptyLists(array $validators): array
    {
        return array_filter($validators, static fn(mixed $configured): bool => $configured !== []);
    }

    /**
     * @param array<string, mixed> $section
     */
    private function isContractSection(string $identifier, array $section): bool
    {
        return $identifier === 'contracts'
            || in_array((string)($section['type'] ?? ''), ['contract', 'contracts'], true);
    }

    /**
     * @param array<string, mixed> $settings
     * @param list<string> $path
     * @return array<int|string, mixed>|null
     */
    private function readPath(array $settings, array $path): ?array
    {
        $current = $settings;
        foreach ($path as $segment) {
            $current = $current[$segment] ?? null;
            if (!is_array($current)) {
                return null;
            }
        }
        return $current;
    }

    /**
     * @param array<string, mixed> $settings
     * @param non-empty-list<string> $path
     * @param array<int|string, mixed> $value
     * @return array<string, mixed>
     */
    private function writePath(array $settings, array $path, array $value): array
    {
        $segment = array_shift($path);
        if ($path === []) {
            $settings[$segment] = $value;
            return $settings;
        }
        $settings[$segment] = $this->writePath(
            is_array($settings[$segment] ?? null) ? $settings[$segment] : [],
            $path,
            $value,
        );
        return $settings;
    }

    /**
     * @param list<string> $notes
     */
    private function formatNotes(array $notes): string
    {
        return $notes === [] ? '' : ' ' . implode(' ', $notes) . '.';
    }
}
