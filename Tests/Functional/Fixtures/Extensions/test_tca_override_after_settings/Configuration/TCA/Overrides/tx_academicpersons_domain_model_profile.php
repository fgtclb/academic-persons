<?php

declare(strict_types=1);

defined('TYPO3') or die();

// A whole column replaced, with a label and a rich text configuration of the site
// package, and nothing of what the persons settings say about it.
$GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['teaching_area'] = [
    'exclude' => true,
    'label' => 'Teaching area of the site package',
    'config' => [
        'type' => 'text',
        'enableRichtext' => true,
        'richtextConfiguration' => 'minimal',
    ],
];

// One key of a column the persons settings configure. The settings leave the title
// editable, so this lock does not survive them.
$GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['title']['config']['readOnly'] = true;
