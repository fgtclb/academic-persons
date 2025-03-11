<?php

declare(strict_types=1);

/**
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$profileInformationConfig = function (string $type): array {
    return [
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
                'new' => true,
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
        'foreign_field' => 'profile',
        'foreign_sortby' => 'sorting',
        'foreign_table' => 'tx_academicpersons_domain_model_profile_information',
        'foreign_match_fields' => [
            'type' => $type,
        ],
        'overrideChildTca' => [
            'columns' => [
                'type' => [
                    'config' => [
                        'default' => $type,
                    ],
                ],
            ],
        ],
    ];
};

return [
    'ctrl' => [
        'label' => 'last_name',
        'label_alt' => 'first_name',
        'label_alt_force' => true,
        'default_sortby' => 'last_name',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.ctrl.label',
        'delete' => 'deleted',
        'origUid' => 't3_origuid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'searchFields' => 'first_name,middle_name,last_name',
        'typeicon_classes' => [
            'default' => 'tx_academicpersons_domain_model_profile',
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
        'starttime' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
            ],
        ],
        'fe_group' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                        -1,
                    ],
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                        -2,
                    ],
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                        '--div--',
                    ],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
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
                'foreign_table' => 'tx_academicpersons_domain_model_profile',
                'foreign_table_where' => 'AND {#tx_academicpersons_domain_model_profile}.{#pid}=###CURRENT_PID### AND {#tx_academicpersons_domain_model_profile}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'slug' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.slug.label',
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => [
                        'first_name',
                        'last_name',
                    ],
                    'fieldSeparator' => '-',
                    'prefixParentPageSlug' => false,
                    'replacements' => [
                        '/' => '',
                    ],
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInPid',
                'default' => '',
            ],
        ],
        'gender' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.gender.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.gender.items.none',
                        '',
                    ],
                    [
                        'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.gender.items.mr',
                        'mr',
                    ],
                    [
                        'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.gender.items.ms',
                        'ms',
                    ],
                    [
                        'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.gender.items.diverse',
                        'diverse',
                    ],
                ],
                'default' => '',
            ],
        ],
        'title' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.title.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'first_name' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.first_name.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'required' => true,
            ],
        ],
        'first_name_alpha' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.first_name_alpha.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'size' => 2,
                'max' => 1,
            ],
        ],
        'middle_name' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.middle_name.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'last_name' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.last_name.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'required' => true,
            ],
        ],
        'last_name_alpha' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.last_name_alpha.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'size' => 2,
                'max' => 1,
            ],
        ],
        'image' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.image.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'file',
                'maxitems' => 6,
                'allowed' => 'common-image-types',
            ],
        ],
        'contracts' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.contracts.label',
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
                        'new' => true,
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
                'foreign_field' => 'profile',
                'foreign_sortby' => 'sorting',
                'foreign_table' => 'tx_academicpersons_domain_model_contract',
            ],
        ],
        'website_title' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.website_title.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 250,
            ],
        ],
        'website' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.website.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 2048,
            ],
        ],
        'teaching_area' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.teaching_area.label',
            'exclude' => true,
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'profile',
            ],
        ],
        'core_competences' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.core_competences.label',
            'exclude' => true,
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'profile',
            ],
        ],
        'supervised_thesis' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.supervised_thesis.label',
            'exclude' => true,
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'profile',
            ],
        ],
        'supervised_doctoral_thesis' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.supervised_doctoral_thesis.label',
            'exclude' => true,
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'profile',
            ],
        ],
        'miscellaneous' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.miscellaneous.label',
            'exclude' => true,
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'profile',
            ],
        ],
        'publications_link_title' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.publications_link_title.label',
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'publications_link' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.publications_link.label',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 2048,
            ],
        ],
        'cooperation' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.cooperation.label',
            'exclude' => true,
            'config' => $profileInformationConfig('cooperation'),
        ],
        'lectures' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.lectures.label',
            'exclude' => true,
            'config' => $profileInformationConfig('lecture'),
        ],
        'memberships' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.memberships.label',
            'exclude' => true,
            'config' => $profileInformationConfig('membership'),
        ],
        'press_media' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.press_media.label',
            'exclude' => true,
            'config' => $profileInformationConfig('press_media'),
        ],
        'publications' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.publications.label',
            'exclude' => true,
                'config' => $profileInformationConfig('publication'),
        ],
        'scientific_research' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.scientific_research.label',
            'exclude' => true,
            'config' => $profileInformationConfig('scientific_research'),
        ],
        'vita' => [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.vita.label',
            'exclude' => true,
            'config' => $profileInformationConfig('curriculum_vitae'),
        ],
    ],
    'palettes' => [
        'name' => [
            'showitem' => implode(',', [
                'gender',
                'title',
                '--linebreak--',
                'first_name',
                'first_name_alpha',
                '--linebreak--',
                'middle_name',
                '--linebreak--',
                'last_name',
                'last_name_alpha',
            ]),
        ],
        'website' => [
            'showitem' => implode(',', [
                'website',
                'website_title',
            ]),
        ],
        'publications' => [
            'showitem' => implode(',', [
                'publications_link_title',
                'publications_link',
            ]),
        ],
        'slug' => [
            'showitem' => implode(',', [
                'slug',
            ]),
        ],
        'hidden' => [
            'showitem' => implode(',', [
                'hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden',
            ]),
        ],
        'language' => [
            'showitem' => implode(',', [
                'sys_language_uid;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sys_language_uid_formlabel',
                'l10n_parent',
            ]),
        ],
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access',
            'showitem' => implode(',', [
                'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel',
                'endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel',
                '--linebreak--',
                'fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel',
            ]),
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.div.general.label',
                    '--palette--;;name',
                    '--palette--;;website',
                    'image',
                    '--palette--;;slug',
                '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.div.contracts.label',
                    'contracts',
                '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.div.unstructuredInformation.label',
                    'teaching_area',
                    'core_competences',
                    'supervised_thesis',
                    'supervised_doctoral_thesis',
                    'miscellaneous',
                    '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.div.structuredInformation.label',
                    'scientific_research',
                    'vita',
                    'memberships',
                    'cooperation',
                    '--palette--;;publications',
                    'publications',
                    'lectures',
                    'press_media',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language',
                    '--palette--;;language',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access',
                    '--palette--;;hidden',
                    '--palette--;;access',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended',
            ]),
        ],
    ],
];
