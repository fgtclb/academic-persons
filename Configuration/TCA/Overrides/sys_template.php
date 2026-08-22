<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

(static function (): void {

    //==================================================================================================================
    // Static TypoScript templates, selectable in a "sys_template" record for installations that do not use site sets.
    //
    // The registered folders are the same ones the sets of this extension deliver through their "typoscript" key.
    // Use one mechanism per site, not both - see the extension documentation, chapter "Configuration".
    //==================================================================================================================
    ExtensionManagementUtility::addStaticFile(
        'academic_persons',
        'Configuration/TypoScript/List',
        'Academic Persons: Profile list',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_persons',
        'Configuration/TypoScript/ListAndDetail',
        'Academic Persons: Profile list and detail',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_persons',
        'Configuration/TypoScript/Detail',
        'Academic Persons: Profile detail',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_persons',
        'Configuration/TypoScript/Card',
        'Academic Persons: Profile card',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_persons',
        'Configuration/TypoScript/SelectedProfiles',
        'Academic Persons: Selected profiles',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_persons',
        'Configuration/TypoScript/SelectedContracts',
        'Academic Persons: Selected contracts',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_persons',
        'Configuration/TypoScript/Full',
        'Academic Persons: All components',
    );

    //==================================================================================================================
    // The two entries below keep the values that installations already store in "sys_template.include_static_file".
    //
    // "Default" is the shared "plugin.tx_academicpersons" block every component folder includes - selecting it is
    // equivalent to "All components" as long as no component ships TypoScript of its own. "Standalone" adds the page
    // object of "Configuration/TypoScript/StandalonePage" on top of "All components".
    //==================================================================================================================
    ExtensionManagementUtility::addStaticFile(
        'academic_persons',
        'Configuration/TypoScript/Default',
        'Academic Persons: Shared plugin settings',
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_persons',
        'Configuration/TypoScript/Standalone',
        'Academic Persons: Standalone page',
    );

})();
