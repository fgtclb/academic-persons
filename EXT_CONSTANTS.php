<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/*
 * @todo Remove this file — its `autoload.files` entry in composer.json, the
 *       require_once in ext_localconf.php and the ACADEMIC_PERSONS_CASCADE_REMOVE
 *       constant — once TYPO3 v13 support is dropped (typo3/cms-core:>13), and
 *       replace `#[Cascade(ACADEMIC_PERSONS_CASCADE_REMOVE)]` in the domain
 *       models with the plain string form `#[Cascade('remove')]`.
 *
 *       Background: TYPO3 v14 expects the string argument for the Extbase
 *       `#[Cascade]` attribute, while TYPO3 v13 still requires the array form
 *       (`['value' => 'remove']`) and passing the string there is a fatal error.
 *       PHP attribute arguments must be constant expressions and cannot contain
 *       a runtime version switch, so the version-specific value is provided via
 *       this constant. It is loaded through both `autoload.files` (Composer mode)
 *       and a require in ext_localconf.php (Classic mode), guarded by
 *       `defined()` so the double include is harmless.
 */
defined('ACADEMIC_PERSONS_CASCADE_REMOVE')
    || define(
        'ACADEMIC_PERSONS_CASCADE_REMOVE',
        (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 14
            ? 'remove'
            : ['value' => 'remove']
    );
