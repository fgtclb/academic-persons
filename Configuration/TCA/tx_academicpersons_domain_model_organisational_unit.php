<?php

declare(strict_types=1);

/**
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$ll = fn (string $langKey): string => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_organisational_unit.' . $langKey;
 
return [
    'ctrl' => [
        'label' => 'unit_name',
        'default_sortby' => 'unit_name',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'title' => $ll('ctrl.label'),
        'delete' => 'deleted',
        'origUid' => 't3_origuid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'function_name',
        'typeicon_classes' => [
            'default' => 'tx_academicpersons_domain_model_organisational_unit',
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
                'foreign_table' => 'tx_academicpersons_domain_model_organisational_unit',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_organisational_unit}.{#pid}=###CURRENT_PID### AND {#tx_academicpersons_domain_model_organisational_unit}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'his_id' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'parent' => [
            'label' => $ll('columns.parent.label'),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_academicpersons_domain_model_organisational_unit',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_organisational_unit}.{#sys_language_uid} IN (-1, 0)',
                'default' => 0,
            ],
        ],
        'unit_name' => [
            'label' => $ll('columns.unit_name.label'),
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 60,
                'eval' => 'required',
            ],
        ],
        'unique_name' => [
            'label' => $ll('columns.unique_name.label'),
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 60,
            ],
        ],
        'display_text' => [
            'label' => $ll('columns.display_text.label'),
            'config' => [
                'type' => 'text',
            ],
        ],
        'long_text' => [
            'label' => $ll('columns.long_text.label'),
            'config' => [
                'type' => 'text',
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
    ],
    'palettes' => [
        'language' => [
            'showitem' => implode(',', [
                'sys_language_uid;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sys_language_uid_formlabel',
                'l10n_parent',
            ]),
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                    'parent',
                    'unit_name',
                    'unique_name',
                    'display_text',
                    'long_text',
                    'valid_from',
                    'valid_to',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language',
                    '--palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
        ],
    ],
];
