<?php

if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 12) {
    foreach ($GLOBALS['TCA']['tx_academicpersons_domain_model_organisational_unit']['columns'] as $field => &$column) {
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

    // TYPO3 v11 backward compatibility for new TCA type datetime.
    // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-97232-NewTCATypeDatetime.html
    $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns']['valid_from']['config'] = [
        'type' => 'input',
        'renderType' => 'inputDateTime',
        'eval' => 'date,int',
    ];
    $GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns']['valid_to']['config'] = [
        'type' => 'input',
        'renderType' => 'inputDateTime',
        'eval' => 'date,int',
    ];
}

