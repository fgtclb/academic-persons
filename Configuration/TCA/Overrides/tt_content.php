<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {

    $typo3MajorVersion = (new Typo3Version())->getMajorVersion();

    //==================================================================================================================
    // Add custom content element group `academicpersons`
    //==================================================================================================================
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'academic',
        'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:content.ctype.group.label',
    );

    //==================================================================================================================
    // Plugin: academicpersons_list
    //==================================================================================================================
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.list.label',
            'value' => 'academicpersons_list',
            'icon' => 'persons_icon',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
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
        sprintf('FILE:EXT:academic_persons/Configuration/FlexForms/Core%s/List.xml', $typo3MajorVersion),
        'academicpersons_list'
    );

    //==================================================================================================================
    // Plugin: academicpersons_listanddetail
    //==================================================================================================================
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.listAndDetail.label',
            'value' => 'academicpersons_listanddetail',
            'icon' => 'persons_icon',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
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
        sprintf('FILE:EXT:academic_persons/Configuration/FlexForms/Core%s/List.xml', $typo3MajorVersion),
        'academicpersons_listanddetail'
    );

    //==================================================================================================================
    // Plugin: academicpersons_detail
    //==================================================================================================================
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.detail.label',
            'value' => 'academicpersons_detail',
            'icon' => 'persons_icon',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_persons'
    );
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        sprintf('FILE:EXT:academic_persons/Configuration/FlexForms/Core%s/Detail.xml', $typo3MajorVersion),
        'academicpersons_detail'
    );

    //==================================================================================================================
    // Plugin: academicpersons_card
    //==================================================================================================================
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:newContentElement.wizardItems.academic.card.title',
            'value' => 'academicpersons_card',
            'icon' => '',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
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
        sprintf('FILE:EXT:academic_persons/Configuration/FlexForms/Core%s/List.xml', $typo3MajorVersion),
        'academicpersons_card'
    );

    //==================================================================================================================
    // Plugin: academicpersons_selectedprofiles
    //==================================================================================================================
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.selectedprofiles.label',
            'value' => 'academicpersons_selectedprofiles',
            'icon' => '',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
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
        sprintf('FILE:EXT:academic_persons/Configuration/FlexForms/Core%s/SelectedProfiles.xml', $typo3MajorVersion),
        'academicpersons_selectedprofiles'
    );

    //==================================================================================================================
    // Plugin: academicpersons_selectedcontracts
    //==================================================================================================================
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.selectedcontracts.label',
            'value' => 'academicpersons_selectedcontracts',
            'icon' => '',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
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
        sprintf('FILE:EXT:academic_persons/Configuration/FlexForms/Core%s/SelectedContracts.xml', $typo3MajorVersion),
        'academicpersons_selectedcontracts'
    );

})();
