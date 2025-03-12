<?php

if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 12) {
    foreach ($GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns'] as $field => &$column) {
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

    // TYPO3 v11 backward compatibility for new TCA type datetime.
    // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-97232-NewTCATypeDatetime.html
    $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns']['valid_from']['config'] = [
        'type' => 'input',
        'renderType' => 'inputDateTime',
        'eval' => 'date',
    ];
    $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns']['valid_to']['config'] = [
        'type' => 'input',
        'renderType' => 'inputDateTime',
        'eval' => 'date',
    ];

    // Add removed TCA type=inline fal related apperance configuration again to hide them, only for TYPO3 v11 b/c.
    // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-98479-RemovedFileReferenceRelatedFunctionality.html#breaking-98479-removed-file-reference-related-functionality
    $relatedColumnsForRemovedFalRelatedInlineApperanceFields = [
        'physical_addresses',
        'email_addresses',
        'phone_numbers',
    ];
    foreach ($relatedColumnsForRemovedFalRelatedInlineApperanceFields as $column) {
        if (!isset($GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns'][$column]['config']['type'])
            || $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns'][$column]['config']['type'] !== 'inline'
        ) {
            continue;
        }
        $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns'][$column]['config']['appearance']['fileUploadAllowed'] = false;
        $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns'][$column]['config']['appearance']['fileByUrlAllowed'] = false;
    }
}
