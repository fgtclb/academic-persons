<?php

declare(strict_types=1);

use FGTCLB\AcademicPersons\Registry\AcademicPersonsSettingsRegistry;
use FGTCLB\AcademicPersons\Tca\RecordTypes;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
$tcaConfiguration = [
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
        'title' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.ctrl.label',
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
        'sortby' => 'sorting',
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
                        'label' => '',
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
                        'label' => '',
                        'value' => 0,
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
        'sorting' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.sorting.label',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'contract' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_academicpersons_domain_model_contract',
                'maxitems' => 1,
            ],
        ],
        'street' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.street.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'street_number' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.street_number.label',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 255,
            ],
        ],
        'additional' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.additional.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'zip' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.zip.label',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 255,
                'required' => true,
            ],
        ],
        'city' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.city.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'state' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.state.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'country' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.country.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
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
                    [
                        'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_address.columns.type.items.undefined.label',
                        'value' => '',
                    ],
                ],
                'itemsProcFunc' => RecordTypes::class . '->getPhysicalAddressTypes',
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

// @todo MAIN TCA Files should be kept without dynamic calls, and following should be done in override files.
$settingsRegistry = GeneralUtility::makeInstance(AcademicPersonsSettingsRegistry::class);
// @todo Why this not matching convention ? Can't we use a convention based approach here ?
$validations = $settingsRegistry->getValidationsForTca('physicalAddress');
$tcaConfiguration = array_replace_recursive($tcaConfiguration, $validations);

return $tcaConfiguration;
