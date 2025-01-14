<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

// <<<<<<< HEAD
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

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
    // @todo Remove core11 condition when v11 support is dropped in 2.x.x.
    // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Deprecation-97126-TCEformsRemovedInFlexForm.html
    (((new Typo3Version())->getMajorVersion() < 12)
        ? 'FILE:EXT:academic_persons/Configuration/FlexForms/Core11/List.xml'
        : 'FILE:EXT:academic_persons/Configuration/FlexForms/List.xml')
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
    // @todo Remove core11 condition when v11 support is dropped in 2.x.x.
    // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Deprecation-97126-TCEformsRemovedInFlexForm.html
    (((new Typo3Version())->getMajorVersion() < 12)
        ? 'FILE:EXT:academic_persons/Configuration/FlexForms/Core11/List.xml'
        : 'FILE:EXT:academic_persons/Configuration/FlexForms/List.xml')
);

ExtensionUtility::registerPlugin(
    'AcademicPersons',
    'SelectedProfiles',
    'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.selectedprofiles.label',
    'EXT:academic_persons/Resources/Public/Icons/persons_icon.svg'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicpersons_selectedprofiles'] = implode(',', [
    'recursive',
    'select_key',
    'pages',
]);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicpersons_selectedprofiles'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'academicpersons_selectedprofiles',
    // @todo Remove core11 condition when v11 support is dropped in 2.x.x.
    // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Deprecation-97126-TCEformsRemovedInFlexForm.html
    (((new Typo3Version())->getMajorVersion() < 12)
        ? 'FILE:EXT:academic_persons/Configuration/FlexForms/Core11/SelectedProfiles.xml'
        : 'FILE:EXT:academic_persons/Configuration/FlexForms/SelectedProfiles.xml')
);

ExtensionUtility::registerPlugin(
    'AcademicPersons',
    'SelectedContracts',
    'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.selectedcontracts.label',
    'EXT:academic_persons/Resources/Public/Icons/persons_icon.svg'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicpersons_selectedcontracts'] = implode(',', [
    'recursive',
    'select_key',
    'pages',
]);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicpersons_selectedcontracts'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'academicpersons_selectedcontracts',
    // @todo Remove core11 condition when v11 support is dropped in 2.x.x.
    // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Deprecation-97126-TCEformsRemovedInFlexForm.html
    (((new Typo3Version())->getMajorVersion() < 12)
        ? 'FILE:EXT:academic_persons/Configuration/FlexForms/Core11/SelectedContracts.xml'
        : 'FILE:EXT:academic_persons/Configuration/FlexForms/SelectedContracts.xml')
);

// @todo After drop v11 Support transform first parameter to @see TYPO3\CMS\Core\Schema\Struct\SelectItem
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'label' : 0) => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:newContentElement.wizardItems.academic.card.title',
        ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'value' : 1) => 'academicpersons_card',
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
    'academicpersons_card',
    'after:header'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    // @todo Remove core11 condition when v11 support is dropped in 2.x.x.
    // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Deprecation-97126-TCEformsRemovedInFlexForm.html
    (((new Typo3Version())->getMajorVersion() < 12)
        ? 'FILE:EXT:academic_persons/Configuration/FlexForms/Core11/List.xml'
        : 'FILE:EXT:academic_persons/Configuration/FlexForms/List.xml'),
    'academicpersons_card'
);
