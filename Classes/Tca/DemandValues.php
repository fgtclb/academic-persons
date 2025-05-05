<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tca;

use FGTCLB\AcademicPersons\DemandValues\GroupByValues;
use FGTCLB\AcademicPersons\DemandValues\SortByValues;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DemandValues
{
    /**
     * @param array{items: list<array<string, string|int>>} $parameters
     */
    public function getGroupByValues(array &$parameters): void
    {
        $values = GeneralUtility::makeInstance(GroupByValues::class)->getAll();
        foreach ($values as $value => $label) {
            $parameters['items'][] = [$label, $value];
        }
    }

    /**
     * @param array{items: list<array<string, string|int>>} $parameters
     */
    public function getSortByValues(array &$parameters): void
    {
        $values = GeneralUtility::makeInstance(SortByValues::class)->getAll();
        foreach ($values as $value => $label) {
            $parameters['items'][] = [$label, $value];
        }
    }
}
