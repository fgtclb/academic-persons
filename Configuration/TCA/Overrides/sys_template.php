<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'academic_persons',
    'Configuration/TypoScript/Default',
    'Academic Persons Settings'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'academic_persons',
    'Configuration/TypoScript/Standalone',
    'Academic Persons Standalone'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'academic_persons',
    'Configuration/TypoScript/StylingExamples/',
    'Academic Persons Styling Example'
);
