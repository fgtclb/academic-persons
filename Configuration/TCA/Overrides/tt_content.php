<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(function () {
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'academicpersons',
        'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:content.ctype.group.label',
    );

    // Add Content Element Detail
    (function () {
        $contentIdentifier = 'academicpersons_list';
        // ToDo: After drop v11 Support transform first parameter to @see TYPO3\CMS\Core\Schema\Struct\SelectItem
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
            [
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'label' : 0) => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.list.label',
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'value' : 1) => $contentIdentifier,
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'icon' : 2) => 'persons_icon',
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'group' : 3) => 'academicpersons',
            ],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
            'academic_persons'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'tt_content',
            implode(',', [
                '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
                'pi_flexform',
            ]),
            $contentIdentifier,
            'after:header'
        );

        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicpersons_list'] = 'recursive,select_key';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicpersons_list'] = 'pi_flexform';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            '*',
            'FILE:EXT:academic_persons/Configuration/FlexForms/Profile.xml',
            $contentIdentifier
        );
    })();

    // Add Content Element Detail
    (function () {
        $contentIdentifier = 'academicpersons_detail';
        // ToDo: After drop v11 Support transform first parameter to @see TYPO3\CMS\Core\Schema\Struct\SelectItem
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
            [
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'label' : 0) => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.detail.label',
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'value' : 1) => $contentIdentifier,
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'icon' : 2) => 'persons_icon',
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'group' : 3) => 'academicpersons',
            ],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
            'academic_persons'
        );
    })();

    // Add Content Element ListAndDetail
    (function () {
        $contentIdentifier = 'academicpersons_listanddetail';
        // ToDo: After drop v11 Support transform first parameter to @see TYPO3\CMS\Core\Schema\Struct\SelectItem
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
            [
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'label' : 0) => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.listAndDetail.label',
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'value' : 1) => $contentIdentifier,
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'icon' : 2) => 'persons_icon',
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'group' : 3) => 'academicpersons',
            ],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
            'academic_persons'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'tt_content',
            implode(',', [
                '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
                'pi_flexform',
            ]),
            $contentIdentifier,
            'after:header'
        );

        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicpersons_listanddetail'] = 'recursive,select_key';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicpersons_listanddetail'] = 'pi_flexform';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            '*',
            'FILE:EXT:academic_persons/Configuration/FlexForms/Profile.xml',
            $contentIdentifier
        );
    })();

    // Add Content Element Card
    (function () {
        $contentIdentifier = 'academicpersons_card';
        // ToDo: After drop v11 Support transform first parameter to @see TYPO3\CMS\Core\Schema\Struct\SelectItem
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
            [
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'label' : 0) => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:newContentElement.wizardItems.academic.card.title',
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'value' : 1) => $contentIdentifier,
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'icon' : 2) => 'persons_icon',
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'group' : 3) => 'academicpersons',
            ],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
            'academic_persons'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'tt_content',
            implode(',', [
                '--div--;LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:element.tab.configuration',
                'pi_flexform',
            ]),
            $contentIdentifier,
            'after:header'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            '*',
            'FILE:EXT:academic_persons/Configuration/FlexForms/flexform_profile_list.xml',
            $contentIdentifier
        );
    })();
})();
