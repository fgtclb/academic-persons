<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use FGTCLB\AcademicBase\TcaManipulator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {

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
    $GLOBALS['TCA']['tt_content']['types']['academicpersons_list']['columnsOverrides']['pi_flexform']['config']['ds']
        = 'FILE:EXT:academic_persons/Configuration/FlexForms/List.xml';

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
    $GLOBALS['TCA']['tt_content']['types']['academicpersons_listanddetail']['columnsOverrides']['pi_flexform']['config']['ds']
        = 'FILE:EXT:academic_persons/Configuration/FlexForms/List.xml';

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
    $GLOBALS['TCA']['tt_content']['types']['academicpersons_detail']['columnsOverrides']['pi_flexform']['config']['ds']
        = 'FILE:EXT:academic_persons/Configuration/FlexForms/Detail.xml';

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
    $GLOBALS['TCA']['tt_content']['types']['academicpersons_card']['columnsOverrides']['pi_flexform']['config']['ds']
        = 'FILE:EXT:academic_persons/Configuration/FlexForms/List.xml';

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
    $GLOBALS['TCA']['tt_content']['types']['academicpersons_selectedprofiles']['columnsOverrides']['pi_flexform']['config']['ds']
        = 'FILE:EXT:academic_persons/Configuration/FlexForms/SelectedProfiles.xml';

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
    $GLOBALS['TCA']['tt_content']['types']['academicpersons_selectedcontracts']['columnsOverrides']['pi_flexform']['config']['ds']
        = 'FILE:EXT:academic_persons/Configuration/FlexForms/SelectedContracts.xml';

})();
