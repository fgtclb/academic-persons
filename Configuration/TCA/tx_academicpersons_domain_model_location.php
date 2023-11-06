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
        'label' => 'title',
        'default_sortby' => 'title asc',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_location.ctrl.label',
        'delete' => 'deleted',
        'origUid' => 't3_origuid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title',
        'typeicon_classes' => [
            'default' => 'tx_academicpersons_domain_model_location',
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
                'foreign_table' => 'tx_academicpersons_domain_model_location',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_location}.{#pid}=###CURRENT_PID### AND {#tx_academicpersons_domain_model_location}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_location.columns.title.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 100,
                'eval' => 'required',
            ],
        ],
    ],
    'palettes' => [
        'general' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general',
            'showitem' => '
                title,
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
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
];
