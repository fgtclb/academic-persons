<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(function () {
    ExtensionUtility::registerPlugin(
        'AcademicPersons',
        'List',
        'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.list.label',
        'EXT:academic_persons/Resources/Public/Icons/persons_icon.svg'
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicpersons_list'] = 'recursive,select_key';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicpersons_list'] = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        'academicpersons_list',
        'FILE:EXT:academic_persons/Configuration/FlexForms/flexform_profile_list.xml'
    );

    ExtensionUtility::registerPlugin(
        'AcademicPersons',
        'Detail',
        'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.detail.label',
        'EXT:academic_persons/Resources/Public/Icons/persons_icon.svg'
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicpersons_detail'] = 'recursive,select_key';

    ExtensionUtility::registerPlugin(
        'AcademicPersons',
        'ListAndDetail',
        'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.listAndDetail.label',
        'EXT:academic_persons/Resources/Public/Icons/persons_icon.svg'
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicpersons_listanddetail'] = 'recursive,select_key';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicpersons_listanddetail'] = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        'academicpersons_listanddetail',
        'FILE:EXT:academic_persons/Configuration/FlexForms/flexform_profile_list.xml'
    );

    // Add Content Element Card
    (function () {
        $contentIdentifier = 'academicpersons_card';
        // ToDo: After drop v11 Support transform first parameter to @see TYPO3\CMS\Core\Schema\Struct\SelectItem
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
            [
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'label' : 0) => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:newContentElement.wizardItems.academic.card.title',
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'value' : 1) => $contentIdentifier,
                ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'icon' : 2) => '',
            ],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
            'academic_persons'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'tt_content',
            implode(',', [
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
