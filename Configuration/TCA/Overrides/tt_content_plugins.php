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
    'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.list.label'
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
    'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.detail.label'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicpersons_detail'] = 'recursive,select_key';

ExtensionUtility::registerPlugin(
    'AcademicPersons',
    'ListAndDetail',
    'LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.listAndDetail.label'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['academicpersons_listanddetail'] = 'recursive,select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['academicpersons_listanddetail'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'academicpersons_listanddetail',
    'FILE:EXT:academic_persons/Configuration/FlexForms/flexform_profile_list.xml'
);
