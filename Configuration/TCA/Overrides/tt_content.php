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
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_persons/Configuration/FlexForms/List.xml',
        'academicpersons_list'
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
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_persons/Configuration/FlexForms/List.xml',
        'academicpersons_listanddetail'
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
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_persons/Configuration/FlexForms/Detail.xml',
        'academicpersons_detail'
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
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_persons/Configuration/FlexForms/List.xml',
        'academicpersons_card'
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
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_persons/Configuration/FlexForms/SelectedProfiles.xml',
        'academicpersons_selectedprofiles'
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
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_persons/Configuration/FlexForms/SelectedContracts.xml',
        'academicpersons_selectedcontracts'
    );

})();
