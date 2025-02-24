<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

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
    'FILE:EXT:academic_persons/Configuration/FlexForms/List.xml'
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
    'FILE:EXT:academic_persons/Configuration/FlexForms/List.xml'
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
    'FILE:EXT:academic_persons/Configuration/FlexForms/SelectedProfiles.xml'
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
    'FILE:EXT:academic_persons/Configuration/FlexForms/SelectedContracts.xml'
);
