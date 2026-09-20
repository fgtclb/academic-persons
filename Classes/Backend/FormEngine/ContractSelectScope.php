<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Backend\FormEngine;

/**
 * Which contracts a backend contract select offers, resolved from the page TSconfig of
 * the edited field by {@see ContractSelectScopeResolver}.
 *
 * An empty {@see self::$storagePageIds} means "every contract", which is what the
 * selects offered before the setting existed and what they keep offering without it.
 *
 * This branch supports PHP 8.1, which has no `readonly` class modifier, so the
 * immutability is declared on the properties.
 */
final class ContractSelectScope
{
    /**
     * @param int[] $storagePageIds Pages the contracts have to be stored on, subpages already resolved
     * @param int[] $alwaysIncludeUids Contracts the edited record references, offered wherever they are stored
     */
    public function __construct(
        public readonly array $storagePageIds = [],
        public readonly array $alwaysIncludeUids = [],
    ) {}
}
