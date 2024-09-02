<?php

declare(strict_types=1);

/**
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$ll = fn (string $langKey): string => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.' . $langKey;

return [
    'ctrl' => [
        'label' => 'type',
        'label_alt' => implode(',', [
            'street',
            'street_number',
            'zip',
            'city',
        ]),
        'label_alt_force' => true,
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
        'searchFields' => implode(',', [
            'street',
            'zip',
            'city',
            'state',
            'country',
            'additional',
        ]),
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
        'contract' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'street' => [
            'label' => $ll('columns.street.label'),
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 120,
                'eval' => 'required',
            ],
        ],
        'street_number' => [
            'label' => $ll('columns.street_number.label'),
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 6,
            ],
        ],
        'additional' => [
            'label' => $ll('columns.additional.label'),
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 120,
            ],
        ],
        'zip' => [
            'label' => $ll('columns.zip.label'),
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 10,
                'eval' => 'required',
            ],
        ],
        'city' => [
            'label' => $ll('columns.city.label'),
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 100,
                'eval' => 'required',
            ],
        ],
        'state' => [
            'label' => $ll('columns.state.label'),
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 60,
            ],
        ],
        'country' => [
            'label' => $ll('columns.country.label'),
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 100,
                'eval' => 'required',
            ],
        ],
        'type' => [
            'label' => $ll('columns.type.label'),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        $ll('columns.type.items.undefined.label'),
                        ''
                    ],
                ],
                'itemsProcFunc' => \Fgtclb\AcademicPersons\Tca\RecordTypes::class . '->getPhysicalAddressTypes',
            ],
        ],
    ],
    'palettes' => [
        'address' => [
            'showitem' => implode(',', [
                'type',
                '--linebreak--',
                'street',
                'street_number',
                '--linebreak--',
                'additional',
                '--linebreak--',
                'zip',
                'city',
                '--linebreak--',
                'state',
                'country',
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
                    '--palette--;;address',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language',
                    '--palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
        ],
    ],
];
