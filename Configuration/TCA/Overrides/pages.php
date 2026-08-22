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
    // Page TSconfig, selectable in the page field "Page TSconfig".
    //
    // The files are the same ones the sets of this extension deliver. Use one mechanism per site, not both - and note
    // that on TYPO3 v12 there are no site sets, so this registration is the only way to reach these files there.
    //==================================================================================================================
    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_persons',
        'Configuration/TSconfig/List/page.tsconfig',
        'Academic Persons: Profile list',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_persons',
        'Configuration/TSconfig/ListAndDetail/page.tsconfig',
        'Academic Persons: Profile list and detail',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_persons',
        'Configuration/TSconfig/Detail/page.tsconfig',
        'Academic Persons: Profile detail',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_persons',
        'Configuration/TSconfig/Card/page.tsconfig',
        'Academic Persons: Profile card',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_persons',
        'Configuration/TSconfig/SelectedProfiles/page.tsconfig',
        'Academic Persons: Selected profiles',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_persons',
        'Configuration/TSconfig/SelectedContracts/page.tsconfig',
        'Academic Persons: Selected contracts',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_persons',
        'Configuration/TSconfig/Full/page.tsconfig',
        'Academic Persons: All components',
    );

})();
