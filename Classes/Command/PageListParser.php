<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Command;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Reads the `--include-pids` and `--exclude-pids` option of the profile commands
 * `academic:createprofiles`, `academic:updateprofiles` and `academic:cleanupprofiles`.
 *
 * A mistyped page list must not silently widen or narrow a run, so any part that is no
 * page uid makes the whole list invalid. Spaces around the commas and empty parts are
 * accepted, a page uid that is given twice counts once.
 *
 * @internal for the profile commands of this extension only, not part of the public API.
 */
final readonly class PageListParser
{
    public const ERROR_MESSAGE = '--include-pids and --exclude-pids take a comma-separated list of page uids.';

    /**
     * @param mixed $value the value of the option, `null` when it is not given
     * @return list<int>|null null for a list with a part that is no page uid
     */
    public function parse(mixed $value): ?array
    {
        if ($value !== null && !is_string($value) && !is_int($value)) {
            return null;
        }
        $pids = [];
        foreach (GeneralUtility::trimExplode(',', (string)$value, true) as $part) {
            if (!MathUtility::canBeInterpretedAsInteger($part) || (int)$part < 0) {
                return null;
            }
            $pids[] = (int)$part;
        }
        return array_values(array_unique($pids));
    }
}
