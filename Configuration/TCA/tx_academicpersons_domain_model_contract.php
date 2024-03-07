<?php

declare(strict_types=1);

/**
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$ll = fn (string $langKey): string => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_contract.' . $langKey;

return [
    'ctrl' => [
        'label' => 'employee_type',
        'label_userFunc' => \Fgtclb\AcademicPersons\Tca\ContractLabels::class . '->getTitle',
        'default_sortby' => 'sorting',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'title' => $ll('ctrl.label'),
        'delete' => 'deleted',
        'hideTable' => true,
        'origUid' => 't3_origuid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'tx_academicpersons_domain_model_contract',
        ],
    ],
    'columns' => [
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'sys_language_uid' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'exclude' => true,
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0,
                    ],
                ],
                'foreign_table' => 'tx_academicpersons_domain_model_contract',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_contract}.{#pid}=###CURRENT_PID### AND {#tx_academicpersons_domain_model_contract}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'profile' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'employee_type' => [
            'label' => $ll('columns.employee_type.label'),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToOne',
                'minitems' => 1,
                'maxitems' => 1,
                'size' => 5,
            ],
        ],
        'organisational_level_1' => [
            'label' => $ll('columns.organisational_level_1.label'),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToOne',
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'organisational_level_2' => [
            'label' => $ll('columns.organisational_level_2.label'),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToOne',
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'organisational_level_3' => [
            'label' => $ll('columns.organisational_level_3.label'),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToOne',
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'physical_addresses_from_organisation' => [
            'label' => $ll('columns.physical_addresses_from_organisation.label'),
            'exclude' => true,
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_academicpersons_domain_model_address',
                'MM' => 'tx_academicpersons_contract_address_mm',
                'MM_match_fields' => [
                    'fieldname' => 'physical_addresses_from_organisation',
                ],
                'foreign_table_where' =>  implode(' AND ', [
                    'tx_academicpersons_domain_model_address.employee_type = ###REC_FIELD_employee_type###',
                    'tx_academicpersons_domain_model_address.organisational_level_1 = ###REC_FIELD_organisational_level_1###',
                    'tx_academicpersons_domain_model_address.organisational_level_2 = ###REC_FIELD_organisational_level_2###',
                    'tx_academicpersons_domain_model_address.organisational_level_3 = ###REC_FIELD_organisational_level_3###',
                ]),
                'size' => 5,
                'autoSizeMax' => 10,
                'maxitems' => 1,
            ],
        ],
        'physical_addresses' => [
            'label' => $ll('columns.physical_addresses.label'),
            'exclude' => true,
            'config' => [
                'type' => 'inline',
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => false,
                    'showNewRecordLink' => true,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'top',
                    'useCombination' => false,
                    'suppressCombinationWarning' => false,
                    'useSortable' => true,
                    'showPossibleLocalizationRecords' => true,
                    'showAllLocalizationLink' => true,
                    'showSynchronizationLink' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' =>  true,
                        'dragdrop' => true,
                        'sort' => false,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                    'showPossibleRecordsSelector' => false,
                    'fileUploadAllowed' => false,
                    'fileByUrlAllowed' => false,
                    'elementBrowserEnabled' => false,
                ],
                'enableCascadingDelete' => true,
                'foreign_field' =>  'contract',
                'foreign_sortby' => 'sorting',
                'foreign_table' => 'tx_academicpersons_domain_model_address',
            ],
        ],
        'email_addresses' => [
            'label' => $ll('columns.email_addresses.label'),
            'exclude' => true,
            'config' => [
                'type' => 'inline',
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => false,
                    'showNewRecordLink' => true,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'top',
                    'useCombination' => false,
                    'suppressCombinationWarning' => false,
                    'useSortable' => true,
                    'showPossibleLocalizationRecords' => true,
                    'showAllLocalizationLink' => true,
                    'showSynchronizationLink' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' =>  true,
                        'dragdrop' => true,
                        'sort' => false,
                        'hide' => true,
                        'delete' => true,
                        'localize' => false,
                    ],
                    'showPossibleRecordsSelector' => false,
                    'fileUploadAllowed' => false,
                    'fileByUrlAllowed' => false,
                    'elementBrowserEnabled' => false,
                ],
                'enableCascadingDelete' => true,
                'foreign_field' =>  'contract',
                'foreign_sortby' => 'sorting',
                'foreign_table' => 'tx_academicpersons_domain_model_email',
            ],
        ],
        'phone_numbers' => [
            'label' => $ll('columns.phone_numbers.label'),
            'exclude' => true,
            'config' => [
                'type' => 'inline',
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => false,
                    'showNewRecordLink' => true,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'top',
                    'useCombination' => false,
                    'suppressCombinationWarning' => false,
                    'useSortable' => true,
                    'showPossibleLocalizationRecords' => true,
                    'showAllLocalizationLink' => true,
                    'showSynchronizationLink' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' =>  true,
                        'dragdrop' => true,
                        'sort' => false,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                    'showPossibleRecordsSelector' => false,
                    'fileUploadAllowed' => false,
                    'fileByUrlAllowed' => false,
                    'elementBrowserEnabled' => false,
                ],
                'enableCascadingDelete' => true,
                'foreign_field' =>  'contract',
                'foreign_sortby' => 'sorting',
                'foreign_table' => 'tx_academicpersons_domain_model_phone_number',
            ],
        ],
        'position' => [
            'label' => $ll('columns.position.label'),
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 100,
            ],
        ],
        'location' => [
            'label' => $ll('columns.location.label'),
            'exclude' => true,
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_academicpersons_domain_model_location',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_location}.{#sys_language_uid} IN (-1, 0)',
                'default' => 0,
            ],
        ],
        'room' => [
            'label' => $ll('columns.room.label'),
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 100,
            ],
        ],
        'office_hours' => [
            'label' => $ll('columns.office_hours.label'),
            'exclude' => true,
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 60,
            ],
        ],
        'publish' => [
            'label' => $ll('columns.publish.label'),
            'exclude' => true,
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        'labelChecked' => 'Enabled',
                        'labelUnchecked' => 'Disabled',
                    ],
                ],
            ],
        ],
    ],
    'palettes' => [
        'general' => [
            'showitem' => implode(',', [
                'profile',
                'publish',
                'employee_type',
                '--linebreak--',
                'organisational_level_1',
                'organisational_level_2',
                'organisational_level_3',
            ]),
        ],
        'contactInformation' => [
            'showitem' => implode(',', [
                'location',
                'room',
                '--linebreak--',
                'position',
            ]),
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                    '--palette--;;general',
                    '--palette--;;contactInformation',
                    'office_hours',
                    'physical_addresses_from_organisation',
                    'physical_addresses',
                    'email_addresses',
                    'phone_numbers',
            ]),
        ],
    ],
];
