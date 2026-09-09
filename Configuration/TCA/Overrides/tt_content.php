<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use FGTCLB\AcademicBase\TcaManipulator;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {

    // The "List.xml" and "Detail.xml" data structures exist once per supported
    // TYPO3 major version because their "settings.pageTitleFormat" value picker
    // cannot be written in a way both versions accept:
    //
    // * TYPO3 v14 reads "config.valuePicker.items" as associative "label"/"value"
    //   pairs (core feature #106092) and migrates positional pairs on the fly,
    //   which makes "FlexFormTools::migrateFlexField()" raise E_USER_DEPRECATED.
    // * TYPO3 v13 reads the positional pair "$item[0]"/"$item[1]" and has no
    //   migration for it at all. Given associative keys its "InputTextElement"
    //   raises "Undefined array key 1" and, under strict types, a TypeError -
    //   the plugin cannot be opened in the backend (ACE-560).
    //
    // FlexForm XML cannot carry a runtime switch, so the switch lives here. Only
    // the two structures that really differ are split; "SelectedProfiles.xml" and
    // "SelectedContracts.xml" carry no value picker and stay shared.
    //
    // The newest known variant is the default, so an unforeseen major version
    // gets a data structure that exists rather than a path that does not: an
    // unresolvable "ds" is swallowed by "TcaFlexPrepare" and renders an empty
    // FlexForm tab, which is the silent failure ACE-293 was about.
    //
    // @todo typo3/cms-core >=14 Drop the "Core13" variants and move the "Core14"
    //       ones back up one level once TYPO3 v13 support is removed.
    $flexFormPath = 'FILE:EXT:academic_persons/Configuration/FlexForms/'
        . ((new Typo3Version())->getMajorVersion() < 14 ? 'Core13' : 'Core14')
        . '/';

    //==================================================================================================================
    // Plugin: academicpersons_list
    //==================================================================================================================
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.list.label',
            'value' => 'academicpersons_list',
            'icon' => 'persons_icon',
            'group' => 'academic',
        ],
        'academic_persons'
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
            'pages',
        ]),
        'academicpersons_list',
        'after:header'
    );
    (new TcaManipulator())->addContentElementPluginFlexForm(
        'academicpersons_list',
        $flexFormPath . 'List.xml',
    );

    //==================================================================================================================
    // Plugin: academicpersons_listanddetail
    //==================================================================================================================
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.listAndDetail.label',
            'value' => 'academicpersons_listanddetail',
            'icon' => 'persons_icon',
            'group' => 'academic',
        ],
        'academic_persons'
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
            'pages',
        ]),
        'academicpersons_listanddetail',
        'after:header'
    );
    (new TcaManipulator())->addContentElementPluginFlexForm(
        'academicpersons_listanddetail',
        $flexFormPath . 'List.xml',
    );

    //==================================================================================================================
    // Plugin: academicpersons_detail
    //==================================================================================================================
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.detail.label',
            'value' => 'academicpersons_detail',
            'icon' => 'persons_icon',
            'group' => 'academic',
        ],
        'academic_persons'
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
        ]),
        'academicpersons_detail',
        'after:header'
    );
    (new TcaManipulator())->addContentElementPluginFlexForm(
        'academicpersons_detail',
        $flexFormPath . 'Detail.xml',
    );

    //==================================================================================================================
    // Plugin: academicpersons_card
    //==================================================================================================================
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:newContentElement.wizardItems.academic.card.title',
            'value' => 'academicpersons_card',
            'icon' => '',
            'group' => 'academic',
        ],
        'academic_persons'
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
        ]),
        'academicpersons_card',
        'after:header'
    );
    (new TcaManipulator())->addContentElementPluginFlexForm(
        'academicpersons_card',
        $flexFormPath . 'List.xml',
    );

    //==================================================================================================================
    // Plugin: academicpersons_selectedprofiles
    //==================================================================================================================
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.selectedprofiles.label',
            'value' => 'academicpersons_selectedprofiles',
            'icon' => '',
            'group' => 'academic',
        ],
        'academic_persons'
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
        ]),
        'academicpersons_selectedprofiles',
        'after:header'
    );
    (new TcaManipulator())->addContentElementPluginFlexForm(
        'academicpersons_selectedprofiles',
        'FILE:EXT:academic_persons/Configuration/FlexForms/SelectedProfiles.xml',
    );

    //==================================================================================================================
    // Plugin: academicpersons_selectedcontracts
    //==================================================================================================================
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.selectedcontracts.label',
            'value' => 'academicpersons_selectedcontracts',
            'icon' => '',
            'group' => 'academic',
        ],
        'academic_persons'
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
            'pi_flexform',
        ]),
        'academicpersons_selectedcontracts',
        'after:header'
    );
    (new TcaManipulator())->addContentElementPluginFlexForm(
        'academicpersons_selectedcontracts',
        'FILE:EXT:academic_persons/Configuration/FlexForms/SelectedContracts.xml',
    );

})();
