<?php

declare(strict_types=1);

defined('TYPO3') or die();

// A record type replaced as a whole, with a form layout of the site package and
// nothing of what the persons settings say about the fields of that type.
$GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information']['types']['publication'] = [
    'showitem' => 'type, title, year, link, bodytext',
];
