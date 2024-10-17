<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$lPath = 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile_information.';

return [
    'ctrl' => [
        'label' => 'type',
        'label_alt' => 'title',
        'label_alt_force' => true,
        'hideTable' => true,
        'default_sortby' => 'sorting',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'title' => $lPath . 'ctrl.label',
        'delete' => 'deleted',
        'origUid' => 't3_origuid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'type' => 'type',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,description',
        'typeicon_classes' => [
            'default' => 'tx_academicpersons_domain_model_profile_information',
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
                'foreign_table' => 'tx_academicpersons_domain_model_profile_information',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_profile_information}.{#pid}=###CURRENT_PID### AND {#tx_academicpersons_domain_model_profile_information}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'profile' => [
            'label' => $lPath . 'columns.profile.label',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_academicpersons_domain_model_profile',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_profile}.{#sys_language_uid} IN (-1,0)',
            ],
        ],
        'type' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'label' => $lPath . 'columns.title.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'required',
            ],
        ],
        'bodytext' => [
            'label' => $lPath . 'columns.bodytext.label',
            'config' => [
                'type' => 'text',
                'cols' => 80,
                'rows' => 5,
            ],
        ],
        'link' => [
            'label' => $lPath . 'columns.link.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 2048,
            ],
        ],
        'year' => [
            'label' => $lPath . 'columns.year.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'min' => 0,
                'max' => 9999,
                'eval' => 'int',
            ],
        ],
        'year_start' => [
            'label' => $lPath . 'columns.year_start.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'min' => 0,
                'max' => 9999,
                'eval' => 'int',
            ],
        ],
        'year_end' => [
            'label' => $lPath . 'columns.year_end.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'min' => 0,
                'max' => 9999,
                'eval' => 'int',
            ],
        ],
    ],
    'palettes' => [
        'date' => [
            'label' => $lPath . 'palette.date',
            'showitem' => implode(',', [
                'year',
                '--linebreak--',
                'year_start',
                'year_end',
            ]),
        ],
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
                    'profile',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language',
                    '--palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
        ],
        'cooperation' => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                    'profile',
                    '--palette--;;date',
                    'title',
                    'link',
                    'bodytext',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
        ],
        'curriculum_vitae' => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                    'profile',
                    '--palette--;;date',
                    'title',
                    'bodytext',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
        ],
        'lecture' => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                    'profile',
                    '--palette--;;date',
                    'title',
                    'link',
                    'bodytext',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
        ],
        'membership' => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                    'profile',
                    '--palette--;;date',
                    'title',
                    'link',
                    'bodytext',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
        ],
        'press_media' => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                    'profile',
                    '--palette--;;date',
                    'title',
                    'link',
                    'bodytext',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
        ],
        'publication' => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                    'profile',
                    '--palette--;;date',
                    'title',
                    'link',
                    'bodytext',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
            'columnsOverrides' => [
                'bodytext' => [
                    'config' => [
                        'enableRichtext' => true,
                        'richtextConfiguration' => 'linkOnly',
                    ],
                ],
            ],
        ],
        'scientific_research' => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                    'profile',
                    '--palette--;;date',
                    'title',
                    'link',
                    'bodytext',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
        ],
    ],
];
