<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\DemandValues;

/**
 * The letters the letter navigation of the persons list offers, as they are passed in
 * `demand.alphabetFilter`.
 *
 * They are the range of the `StaticRangeMapper` the shipped route enhancers map the
 * letter with (`Configuration/Routes/List.yaml`, `ListAndDetail.yaml`), so a letter outside
 * this set has no speaking URL. Changing one means changing the other.
 */
final class AlphabetFilterLetters
{
    public const LETTERS = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];
}
