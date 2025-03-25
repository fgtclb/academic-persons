<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {

    ExtensionManagementUtility::addStaticFile(
        'academic_persons',
        'Configuration/TypoScript/Default',
        'Academic Persons Settings'
    );

    ExtensionManagementUtility::addStaticFile(
        'academic_persons',
        'Configuration/TypoScript/Standalone',
        'Academic Persons Standalone'
    );

})();
