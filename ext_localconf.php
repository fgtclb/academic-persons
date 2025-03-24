<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Fgtclb\AcademicPersons\Controller\ProfileController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {

    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['profile'] = 'EXT:academic_persons/Configuration/CKEditor/Profile.yaml';
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['linkOnly'] = 'EXT:academic_persons/Configuration/CKEditor/LinkOnly.yaml';

    ExtensionUtility::configurePlugin(
        'AcademicPersons',
        'List',
        [
            ProfileController::class => 'list',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'AcademicPersons',
        'SelectedProfiles',
        [
            ProfileController::class => 'selectedProfiles',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'AcademicPersons',
        'SelectedContracts',
        [
            ProfileController::class => 'selectedContracts',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'AcademicPersons',
        'Detail',
        [
            ProfileController::class => 'detail',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'AcademicPersons',
        'ListAndDetail',
        [
            ProfileController::class => implode(',', [
                'list',
                'detail',
            ]),
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'AcademicPersons',
        'Card',
        [
            ProfileController::class => 'card',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['academicPersons']
        = \Fgtclb\AcademicPersons\Hook\DataHandlerHooks::class;
})();
