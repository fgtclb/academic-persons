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
        'title' => $ll('ctrl.label'),
        'label' => 'profile',
        'label_userFunc' => \Fgtclb\AcademicPersons\Tca\ContractLabels::class . '->getTitle',
        'default_sortby' => 'sorting',
        'hideTable' => true,
        'origUid' => 't3_origuid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
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
        'organisational_unit' => [
            'label' => $ll('columns.organisational_unit.label'),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        $ll('please_select'),
                        '',
                    ],
                ],
                'foreign_table' => 'tx_academicpersons_domain_model_organisational_unit',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_organisational_unit}.{#sys_language_uid} IN (-1, 0)',
                'minitems' => 1,
            ],
        ],
        'function_type' => [
            'label' => $ll('columns.function_type.label'),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        $ll('please_select'),
                        '',
                    ],
                ],
                'foreign_table' => 'tx_academicpersons_domain_model_function_type',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_function_type}.{#sys_language_uid} IN (-1, 0)',
                'minitems' => 1,
            ],
        ],
        'valid_from' => [
            'label' => $ll('columns.valid_from.label'),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'date',
            ],
        ],
        'valid_to' => [
            'label' => $ll('columns.valid_to.label'),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'date',
            ],
        ],
        'employee_type' => [
            'label' => $ll('columns.employee_type.label'),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToOne',
                'minitems' => 0,
                'maxitems' => 1,
                'size' => 5,
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
                'max' => 255,
            ],
        ],
        'location' => [
            'label' => $ll('columns.location.label'),
            'exclude' => true,
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        '',
                    ],
                ],
                'foreign_table' => 'tx_academicpersons_domain_model_location',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_location}.{#sys_language_uid} IN (-1, 0)',
                'default' => '',
            ],
        ],
        'room' => [
            'label' => $ll('columns.room.label'),
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
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
    ],
    'palettes' => [
        'general' => [
            'showitem' => implode(',', [
                'profile',
                'publish',
                '--linebreak--',
                'position',
                '--linebreak--',
                'organisational_unit',
                'function_type',
                '--linebreak--',
                'valid_from',
                'valid_to',
            ]),
        ],
        'contactInformation' => [
            'showitem' => implode(',', [
                'location',
                'room',
                '--linebreak--',
                'office_hours',
            ]),
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => implode(',', [
                '--div--;' . $ll('div.general.label'),
                    '--palette--;;general',
                    '--palette--;;contactInformation',
                    '--div--;' . $ll('div.addresses.label'),
                    'physical_addresses',
                    'email_addresses',
                    'phone_numbers',
                    '--div--;' . $ll('div.employeeType.label'),
                    'employee_type',
            ]),
        ],
    ],
];
