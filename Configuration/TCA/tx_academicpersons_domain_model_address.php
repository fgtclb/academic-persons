<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'ctrl' => [
        'label' => 'type',
        'label_alt' => 'street,street_number,zip,city',
        'label_alt_force' => true,
        'default_sortby' => 'sorting',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.ctrl.label',
        'delete' => 'deleted',
        'origUid' => 't3_origuid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'street,zip,city,state,country,additional',
        'typeicon_classes' => [
            'default' => 'tx_academicpersons_domain_model_address',
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
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
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0,
                    ],
                ],
                'foreign_table' => 'tx_academicpersons_domain_model_address',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_address}.{#pid}=###CURRENT_PID### AND {#tx_academicpersons_domain_model_address}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'employee_type' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.employee_type.label',
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'displayCond' => 'FIELD:contract:REQ:false',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToOne',
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'organisational_level_1' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.organisational_level_1.label',
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'displayCond' => 'FIELD:contract:REQ:false',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToOne',
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'organisational_level_2' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.organisational_level_2.label',
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'displayCond' => 'FIELD:contract:REQ:false',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToOne',
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'organisational_level_3' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.organisational_level_3.label',
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'displayCond' => 'FIELD:contract:REQ:false',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToOne',
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'street' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.street.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 120,
                'eval' => 'required',
            ],
        ],
        'street_number' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.street_number.label',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 6,
            ],
        ],
        'additional' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.additional.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 120,
            ],
        ],
        'zip' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.zip.label',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 10,
                'eval' => 'required',
            ],
        ],
        'city' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.city.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 100,
                'eval' => 'required',
            ],
        ],
        'state' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.state.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 60,
            ],
        ],
        'country' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.country.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 100,
                'eval' => 'required',
            ],
        ],
        'type' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.type.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.type.items.undefined.label', ''],
                ],
                'itemsProcFunc' => \Fgtclb\AcademicPersons\Tca\RecordTypes::class . '->getPhysicalAddressTypes',
            ],
        ],
        'contract' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'palettes' => [
        'general' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general',
            'showitem' => '
                type,
                --linebreak--,
                employee_type,
                --linebreak--,
                organisational_level_1,
                organisational_level_2,
                organisational_level_3,
            ',
        ],
        'address' => [
            'showitem' => '
                street,
                street_number,
                --linebreak--,
                additional,
                --linebreak--,
                zip,
                city,
                --linebreak--,
                state,
                --linebreak--,
                country
            ',
        ],
        'language' => [
            'showitem' => '
                sys_language_uid;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sys_language_uid_formlabel,
                l10n_parent,
            ',
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;address,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
];
