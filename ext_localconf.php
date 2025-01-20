<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Fgtclb\AcademicPersons\Controller\ProfileController;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

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

    if ($versionInformation->getMajorVersion() < 12) {
        // Starting with TYPO3 v12.0 Configuration/page.tsconfig in an Extension is automatically loaded during build time.
        // @see https://docs.typo3.org/m/typo3/reference-tsconfig/12.4/en-us/UsingSetting/PageTSconfig.html#pagesettingdefaultpagetsconfig
        ExtensionManagementUtility::addPageTSConfig('
            @import \'EXT:academic_programs/Configuration/page.tsconfig\'
        ');

        // Starting with TYPO3 v12.0 can this remove
        // @see https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ExtensionArchitecture/HowTo/UpdateExtensions/UpdateWizards/Creation.html#upgrade-wizard-interface
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['academicPerson_pluginContent']
            = \Fgtclb\AcademicPersons\Upgrades\PluginContentUpgrade::class;
    }
})();
