<?php

declare(strict_types=1);

defined('TYPO3') or die();

// The persons settings require a position, so a site package cannot make it optional here.
$GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns']['position']['config']['required'] = false;
