<?php

declare(strict_types=1);

// The shape the profile `image` column had until 3.0: a `file` column whose value
// is carried into every translation by `l10n_mode=exclude`. No shipped column has
// it any more (ACE-506), so the ACE-487 pin of the synchronisation keeps it here.
$GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['tx_testexcludefilecolumn_document'] = [
    'label' => 'Document (exclude)',
    'l10n_mode' => 'exclude',
    'config' => [
        'type' => 'file',
        'maxitems' => 1,
        'allowed' => 'common-image-types',
    ],
];
