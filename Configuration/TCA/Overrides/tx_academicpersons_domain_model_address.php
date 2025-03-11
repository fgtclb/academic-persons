<?php


if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 12) {
    foreach ($GLOBALS['TCA']['tx_academicpersons_domain_model_address']['columns'] as $field => &$column) {
    }
}
