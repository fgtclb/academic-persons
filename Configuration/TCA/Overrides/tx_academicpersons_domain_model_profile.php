<?php

// tx_academicpersons_domain_model_profile
if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 12) {
    foreach ($GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'] as $field => &$column) {
        // Migrate TYPO3 v12 required to v11 eval required to keep backward compatibility.
        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-97035-UtilizeRequiredDirectlyInTCAFieldConfiguration.html
        if (isset($column['config'])
            && is_array($column['config'])
            && isset($column['config']['required'])
            && ((bool)$column['config']['required']) === true
            && !str_contains($column['eval'] ?? '', 'required')
        ) {
            $column['eval'] = ltrim(
                ($column['eval'] ?? '') . ',required',
                ','
            );
            unset($column['config']['required']);
        }
    }
    unset($column);

    // The getFileFieldTCAConfig() method has been replaced with the new field type File in TYPO3 v12.0.
    // @see https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/Fal/UsingFal/Tca.html
    // @see https://docs.typo3.org/m/typo3/reference-tca/12.4/en-us/ColumnsConfig/Type/File/Index.html#columns-file-migration
    $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['images']['config'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
        'image',
        [
            'maxitems' => 1,
            'appearance' => [
                'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                'showPossibleLocalizationRecords' => true,
            ],
            // Needed for create file references with Extbase
            'foreign_match_fields' => [
                'fieldname' => 'image',
                'tablenames' => 'tx_academicpersons_domain_model_profile',
                'table_local' => 'sys_file',
            ],
            // custom configuration for displaying fields in the overlay/reference table
            // to use the imageoverlayPalette instead of the basicoverlayPalette
            'overrideChildTca' => [
                'types' => [
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                        'showitem' => '
                            --palette--;;imageoverlayPalette,
                            --palette--;;filePalette',
                    ],
                ],
            ],
        ],
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
    );

    // TYPO3 v11 backward compatibility for new TCA type datetime.
    // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-97232-NewTCATypeDatetime.html
    $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns']['starttime']['config'] = [
        'type' => 'input',
        'renderType' => 'inputDateTime',
        'eval' => 'datetime,int',
        'default' => 0,
    ];
    $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns']['endtime']['config'] = [
        'type' => 'input',
        'renderType' => 'inputDateTime',
        'eval' => 'datetime,int',
        'default' => 0,
        'range' => [
            'upper' => mktime(0, 0, 0, 1, 1, 2038),
        ],
    ];

    // Add removed TCA type=inline fal related apperance configuration again to hide them, only for TYPO3 v11 b/c.
    // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-98479-RemovedFileReferenceRelatedFunctionality.html#breaking-98479-removed-file-reference-related-functionality
    $relatedColumnsForRemovedFalRelatedInlineApperanceFields = [
        'cooperation', 'lecture', 'membership', 'press_media',
        'publication', 'scientific_research', 'curriculum_vitae',
        'profile',
    ];
    foreach ($relatedColumnsForRemovedFalRelatedInlineApperanceFields as $column) {
        if (!isset($GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'][$column]['config']['type'])
            || $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'][$column]['config']['type'] !== 'inline'
        ) {
            continue;
        }
        $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'][$column]['config']['appearance']['fileUploadAllowed'] = false;
        $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'][$column]['config']['appearance']['fileByUrlAllowed'] = false;
    }
}
