<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Tca;

use Fgtclb\AcademicPersons\Types\EmailAddressTypes;
use Fgtclb\AcademicPersons\Types\PhoneNumberTypes;
use Fgtclb\AcademicPersons\Types\PhysicalAddressTypes;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RecordTypes
{
    /**
     * @param array{items: list<array<string, string|int>>} $parameters
     */
    public function getPhysicalAddressTypes(array &$parameters): void
    {
        $typo3MajorVersion = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
        $selectLabelKey = ($typo3MajorVersion >= 12) ? 'label' : 0;
        $selectValueKey = ($typo3MajorVersion >= 12) ? 'value' : 1;
        $types = GeneralUtility::makeInstance(PhysicalAddressTypes::class)->getAll();
        foreach ($types as $value => $label) {
            $parameters['items'][] = [
                $selectLabelKey => $label,
                $selectValueKey => $value,
            ];
        }
    }

    /**
     * @param array{items: list<array<string, string|int>>} $parameters
     */
    public function getEmailAddressTypes(array &$parameters): void
    {
        $typo3MajorVersion = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
        $selectLabelKey = ($typo3MajorVersion >= 12) ? 'label' : 0;
        $selectValueKey = ($typo3MajorVersion >= 12) ? 'value' : 1;
        $types = GeneralUtility::makeInstance(EmailAddressTypes::class)->getAll();
        foreach ($types as $value => $label) {
            $parameters['items'][] = [
                $selectLabelKey => $label,
                $selectValueKey => $value,
            ];
        }
    }

    /**
     * @param array{items: list<array<string, string|int>>} $parameters
     */
    public function getPhoneNumberTypes(array &$parameters): void
    {
        $typo3MajorVersion = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
        $selectLabelKey = ($typo3MajorVersion >= 12) ? 'label' : 0;
        $selectValueKey = ($typo3MajorVersion >= 12) ? 'value' : 1;
        $types = GeneralUtility::makeInstance(PhoneNumberTypes::class)->getAll();
        foreach ($types as $value => $label) {
            $parameters['items'][] = [
                $selectLabelKey => $label,
                $selectValueKey => $value,
            ];
        }
    }
}
