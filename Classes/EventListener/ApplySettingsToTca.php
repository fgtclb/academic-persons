<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\EventListener;

use FGTCLB\AcademicBase\Settings\TcaValidationMerger;
use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationSet;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ProjectProfileFieldCheck;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Applies the validation of the persons settings to the TCA of the six person
 * tables: `required`, `readOnly` and the `email` and `number` types of a column,
 * and the same per record type of the profile information table.
 *
 * It runs once the TCA is compiled, after every `Configuration/TCA/Overrides` file,
 * so a site package that replaces a column or adds one keeps what the settings say
 * about it, and the settings win over an override of the same keys. The listener of
 * EXT:content_blocks builds its TCA in `BeforeTcaOverridesEvent` and is earlier
 * anyway. Should that extension move it to this event, `after` keeps the settings
 * behind it. The ordering costs nothing while the extension is not loaded, because
 * an ordering that names no registered listener is dropped.
 *
 * A site package that has to change one of these keys after the settings orders a
 * listener of its own after `academic-persons/apply-settings-to-tca`. That
 * identifier is public API, the class is not.
 *
 * A project field of the settings, one declared `custom`, is merged like any other
 * field once {@see ProjectProfileFieldCheck} allows its column. A column it refuses
 * gets nothing and raises an `E_USER_DEPRECATED` notice naming it. A notice rather
 * than an exception, because a typo must not take the backend and the install tool
 * down with the TCA, and rather than a log entry alone, because a test run fails on
 * it. The frontend editor refuses the same column when it is used.
 *
 * @internal not part of public API.
 */
#[AsEventListener(
    identifier: 'academic-persons/apply-settings-to-tca',
    after: 'content-blocks-tca',
)]
final readonly class ApplySettingsToTca
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    private const PROFILE_INFORMATION_TABLE = 'tx_academicpersons_domain_model_profile_information';

    public function __construct(
        private AcademicPersonsSettings $academicPersonsSettings,
        private TcaValidationMerger $tcaValidationMerger,
        private ProjectProfileFieldCheck $projectProfileFieldCheck,
    ) {}

    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $tca = $event->getTca();
        $validationSets = [
            self::PROFILE_TABLE => $this->getProfileValidationSet($tca),
            'tx_academicpersons_domain_model_contract' => $this->academicPersonsSettings->getDocumentValidationSet('contracts'),
            'tx_academicpersons_domain_model_email' => $this->academicPersonsSettings->getContractContactValidationSet('emailAddresses'),
            'tx_academicpersons_domain_model_phone_number' => $this->academicPersonsSettings->getContractContactValidationSet('phoneNumbers'),
            'tx_academicpersons_domain_model_address' => $this->academicPersonsSettings->getContractContactValidationSet('physicalAddresses'),
        ];
        foreach ($validationSets as $table => $validationSet) {
            if (!is_array($tca[$table]['columns'] ?? null)) {
                continue;
            }
            $tca[$table] = $this->tcaValidationMerger->merge(
                $tca[$table],
                $this->withExistingColumns($validationSet, $tca[$table]['columns']),
            );
            $tca[$table] = $this->addEmailSoftReferences($tca[$table]);
        }
        // One table for seven record types, so the flags of a section land in the
        // `columnsOverrides` of its type and never on the shared column.
        if (is_array($tca[self::PROFILE_INFORMATION_TABLE]['types'] ?? null)) {
            ArrayUtility::mergeRecursiveWithOverrule(
                $tca[self::PROFILE_INFORMATION_TABLE],
                $this->academicPersonsSettings->getDocumentValidationTcaTypesConfig(),
            );
        }
        $event->setTca($tca);
    }

    /**
     * Every profile section plus the special fields addressing a profile column,
     * except the owner's visibility switch of the profile editor: it writes the
     * `disabled` enable column, and taking it away from owners must not take the
     * checkbox away from backend editors. A project field whose column is refused
     * is left out as well.
     *
     * @param array<string, mixed> $tca
     */
    private function getProfileValidationSet(array $tca): ValidationSet
    {
        $validationSet = $this->academicPersonsSettings->getProfileUpdateValidationSet();
        $disabledColumn = $tca[self::PROFILE_TABLE]['ctrl']['enablecolumns']['disabled'] ?? null;
        $refusedProperties = $this->findRefusedProjectFields($tca);
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
     * The property names of the project fields whose column is refused, each one
     * announced with a notice.
     *
     * @param array<string, mixed> $tca
     * @return list<string>
     */
    private function findRefusedProjectFields(array $tca): array
    {
        $profileTca = $tca[self::PROFILE_TABLE] ?? null;
        if (!is_array($profileTca) || !is_array($profileTca['columns'] ?? null)) {
            return [];
        }
        $refusedProperties = [];
        foreach ($this->academicPersonsSettings->getCustomProfileFields() as $propertyName => $field) {
            $problem = $this->projectProfileFieldCheck->findProblem($field, $profileTca);
            if ($problem === null) {
                continue;
            }
            $refusedProperties[] = $propertyName;
            trigger_error(
                $problem . ' The settings of the field are not applied to the TCA, and the frontend profile editor'
                . ' fails until the settings or the TCA are corrected.',
                E_USER_DEPRECATED,
            );
        }
        return $refusedProperties;
    }

    /**
     * A fragment for a column the TCA does not have would be a column without a type.
     *
     * @param array<string, mixed> $columns
     */
    private function withExistingColumns(ValidationSet $validationSet, array $columns): ValidationSet
    {
        return new ValidationSet(
            identifier: $validationSet->identifier,
            validations: array_filter(
                $validationSet->validations,
                static fn(Validation $validation): bool => isset($columns[$validation->fieldName]),
            ),
        );
    }

    /**
     * The core sets the soft reference of every `email` column while it prepares the
     * TCA, before this event. A column the `email` flag turns into one gets it here.
     * The profile information table carries its flags per record type, where the core
     * sets no soft reference either.
     *
     * @param array<string, mixed> $tableTca
     * @return array<string, mixed>
     */
    private function addEmailSoftReferences(array $tableTca): array
    {
        foreach ($tableTca['columns'] as $column => $configuration) {
            if (($configuration['config']['type'] ?? null) === 'email') {
                $tableTca['columns'][$column]['config']['softref'] = 'email[subst]';
            }
        }
        return $tableTca;
    }
}
