<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationSet;

/**
 * The typed graph built from `Configuration/AcademicPersons/Settings.yaml`.
 *
 * Its four top-level maps become: the profile sections with their fields
 * (`profile`), the special components (`special`), the contract fields and
 * the contact sections a contract owns (`contracts`), and the document
 * sections (`documentSections`). The public detail layout is read from the
 * same `profile` map into {@see PublicProfileSettings}. `raw` keeps the
 * merged array the graph was built from.
 *
 * The lookups fail softly: a TCA file or a form asks for identifiers an
 * installation need not configure, and an unknown one yields null or an
 * empty validation set carrying the requested identifier. Validation never
 * falls back from one section to another - a contact section, a document
 * section and the profile are validated against their own set only.
 *
 * @internal not part of public API.
 */
final class AcademicPersonsSettings
{
    /** @var array<string, ProfileSection> */
    public readonly array $profileSections;
    /** @var array<string, SpecialField> */
    public readonly array $specialFields;
    /** @var array<string, ContractField> */
    public readonly array $contractFields;
    /** @var array<string, ContractContactSection> */
    public readonly array $contractContactSections;
    /** @var array<string, DocumentSection> */
    public readonly array $documentSections;
    public readonly PublicProfileSettings $publicProfile;
    /** @var array<string, mixed> */
    public readonly array $raw;

    /**
     * @param array<string, ProfileSection> $profileSections
     * @param array<string, SpecialField> $specialFields
     * @param array<string, ContractField> $contractFields
     * @param array<string, ContractContactSection> $contractContactSections
     * @param array<string, DocumentSection> $documentSections
     * @param array<string, mixed> $raw
     */
    public function __construct(
        array $profileSections = [],
        array $specialFields = [],
        array $contractFields = [],
        array $contractContactSections = [],
        array $documentSections = [],
        ?PublicProfileSettings $publicProfile = null,
        array $raw = [],
    ) {
        $this->profileSections = $profileSections;
        $this->specialFields = $specialFields;
        $this->contractFields = $contractFields;
        $this->contractContactSections = $contractContactSections;
        $this->documentSections = $documentSections;
        $this->publicProfile = $publicProfile ?? new PublicProfileSettings();
        $this->raw = $raw;
    }

    /**
     * @param array{
     *     profileSections?: array<string, ProfileSection>,
     *     specialFields?: array<string, SpecialField>,
     *     contractFields?: array<string, ContractField>,
     *     contractContactSections?: array<string, ContractContactSection>,
     *     documentSections?: array<string, DocumentSection>,
     *     publicProfile?: PublicProfileSettings,
     *     raw?: array<string, mixed>,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            profileSections: $array['profileSections'] ?? [],
            specialFields: $array['specialFields'] ?? [],
            contractFields: $array['contractFields'] ?? [],
            contractContactSections: $array['contractContactSections'] ?? [],
            documentSections: $array['documentSections'] ?? [],
            publicProfile: $array['publicProfile'] ?? null,
            raw: $array['raw'] ?? [],
        );
    }

    public function getProfileSection(string $identifier): ?ProfileSection
    {
        return $this->profileSections[$identifier] ?? null;
    }

    /**
     * Resolves a profile field by its settings key or by the property it
     * addresses, across all sections.
     */
    public function getProfileField(string $identifier): ?ProfileField
    {
        foreach ($this->profileSections as $section) {
            $field = $section->getField($identifier);
            if ($field !== null) {
                return $field;
            }
            foreach ($section->fields as $candidate) {
                if ($candidate->propertyName === $identifier) {
                    return $candidate;
                }
            }
        }
        return null;
    }

    public function getSpecialField(string $identifier): ?SpecialField
    {
        return $this->specialFields[$identifier] ?? null;
    }

    /**
     * Resolves a contract field by its settings key or by the property it
     * addresses.
     */
    public function getContractField(string $identifier): ?ContractField
    {
        $field = $this->contractFields[$identifier] ?? null;
        if ($field !== null) {
            return $field;
        }
        foreach ($this->contractFields as $candidate) {
            if ($candidate->propertyName === $identifier) {
                return $candidate;
            }
        }
        return null;
    }

    public function getContractContactSection(string $identifier): ?ContractContactSection
    {
        return $this->contractContactSections[$identifier] ?? null;
    }

    /**
     * Resolves a contact field by its settings key or by the property it
     * addresses, across all contact sections - the first section carrying
     * the property wins, so a lookup by the shared `type` property should
     * go through {@see ContractContactSection::getField()} instead.
     */
    public function getContractContactField(string $identifier): ?ContractContactField
    {
        foreach ($this->contractContactSections as $section) {
            $field = $section->getField($identifier);
            if ($field !== null) {
                return $field;
            }
            foreach ($section->fields as $candidate) {
                if ($candidate->propertyName === $identifier) {
                    return $candidate;
                }
            }
        }
        return null;
    }

    public function getDocumentSection(string $identifier): ?DocumentSection
    {
        return $this->documentSections[$identifier] ?? null;
    }

    /**
     * Resolves a document section by the record type its rows carry, which is
     * what a stored profile information record knows about itself.
     */
    public function getDocumentSectionByType(string $type): ?DocumentSection
    {
        foreach ($this->documentSections as $section) {
            if ($section->type === $type) {
                return $section;
            }
        }
        return null;
    }

    /**
     * The validation set of one profile section, or of every section folded
     * into one set when no section is named. A later section replaces an
     * earlier one's validation of the same property.
     */
    public function getProfileValidationSet(?string $sectionIdentifier = null): ValidationSet
    {
        if ($sectionIdentifier !== null) {
            return $this->getProfileSection($sectionIdentifier)?->validationSet
                ?? new ValidationSet(identifier: $sectionIdentifier, validations: []);
        }
        $validations = [];
        foreach ($this->profileSections as $section) {
            $validations = array_replace($validations, $section->validationSet->validations);
        }
        return new ValidationSet(identifier: 'profile', validations: $validations);
    }

    /**
     * Every validation that may participate in an update of the profile
     * record: all profile sections plus the special fields addressing a
     * direct profile property, such as `skipSync`. This is the set the
     * profile TCA merges.
     */
    public function getProfileUpdateValidationSet(): ValidationSet
    {
        $validations = $this->getProfileValidationSet()->validations;
        foreach ($this->specialFields as $field) {
            if ($field->hasDirectProfileProperty()) {
                $validations[$field->identifier] = $field->validation;
            }
        }
        return new ValidationSet(identifier: 'profileUpdate', validations: $validations);
    }

    /**
     * The validations of the named fields of one profile section, keyed by
     * property name. Fields the section does not have are left out, and an
     * unknown section yields an empty set.
     *
     * @param list<string> $fieldIdentifiers
     */
    public function getProfileValidationSetForFields(array $fieldIdentifiers, string $sectionIdentifier): ValidationSet
    {
        $section = $this->getProfileSection($sectionIdentifier);
        return new ValidationSet(
            identifier: $sectionIdentifier,
            validations: $section === null
                ? []
                : $this->collectProfileValidations($fieldIdentifiers, $section),
        );
    }

    public function getContractContactValidationSet(string $sectionIdentifier): ValidationSet
    {
        return $this->getContractContactSection($sectionIdentifier)?->validationSet
            ?? new ValidationSet(identifier: $sectionIdentifier, validations: []);
    }

    /**
     * @param list<string> $fieldIdentifiers
     */
    public function getContractContactValidationSetForFields(
        array $fieldIdentifiers,
        string $sectionIdentifier,
    ): ValidationSet {
        $section = $this->getContractContactSection($sectionIdentifier);
        return new ValidationSet(
            identifier: $sectionIdentifier,
            validations: $section === null
                ? []
                : $this->collectContractContactValidations($fieldIdentifiers, $section),
        );
    }

    public function getDocumentValidationSet(string $sectionIdentifier): ValidationSet
    {
        return $this->getDocumentSection($sectionIdentifier)?->validationSet
            ?? new ValidationSet(identifier: $sectionIdentifier, validations: []);
    }

    public function getDocumentValidationSetByType(string $type): ValidationSet
    {
        return $this->getDocumentSectionByType($type)?->validationSet
            ?? new ValidationSet(identifier: $type, validations: []);
    }

    /**
     * The `types.<record type>.columnsOverrides` fragment for the profile
     * information table: the validation of a document section applies to
     * the record type of that section only, never to the column as such.
     * The contracts section has its own table and is left out.
     *
     * @return array<string, mixed>
     */
    public function getDocumentValidationTcaTypesConfig(): array
    {
        $configuration = [];
        foreach ($this->documentSections as $section) {
            if ($section->isContractSection()) {
                continue;
            }
            foreach ($section->validationSet->validations as $validation) {
                if ($validation->tcaConfig !== []) {
                    $configuration['types'][$section->type]['columnsOverrides'][$validation->fieldName]['config']
                        = $validation->tcaConfig;
                }
            }
        }
        return $configuration;
    }

    /**
     * @param list<string> $fieldIdentifiers
     * @return array<string, Validation>
     */
    private function collectProfileValidations(array $fieldIdentifiers, ProfileSection $section): array
    {
        $validations = [];
        foreach ($fieldIdentifiers as $fieldIdentifier) {
            $field = $section->getField($fieldIdentifier);
            if ($field === null) {
                foreach ($section->fields as $candidate) {
                    if ($candidate->propertyName === $fieldIdentifier) {
                        $field = $candidate;
                        break;
                    }
                }
            }
            if ($field !== null) {
                $validations[$field->propertyName] = $field->validation;
            }
        }
        return $validations;
    }

    /**
     * @param list<string> $fieldIdentifiers
     * @return array<string, Validation>
     */
    private function collectContractContactValidations(
        array $fieldIdentifiers,
        ContractContactSection $section,
    ): array {
        $validations = [];
        foreach ($fieldIdentifiers as $fieldIdentifier) {
            $field = $section->getField($fieldIdentifier);
            if ($field === null) {
                foreach ($section->fields as $candidate) {
                    if ($candidate->propertyName === $fieldIdentifier) {
                        $field = $candidate;
                        break;
                    }
                }
            }
            if ($field !== null) {
                $validations[$field->propertyName] = $field->validation;
            }
        }
        return $validations;
    }
}
