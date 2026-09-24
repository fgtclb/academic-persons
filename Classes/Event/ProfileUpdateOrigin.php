<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Event;

/**
 * Where the update an {@see AfterProfileUpdateEvent} announces came from.
 *
 * The case set is fixed, so that a listener can `match` over it exhaustively.
 */
enum ProfileUpdateOrigin: string
{
    /**
     * A profile created for a frontend user, by `academic:createprofiles`.
     */
    case Creation = 'creation';

    /**
     * A profile updated from its frontend user's data, by `academic:updateprofiles`.
     */
    case Synchronization = 'synchronization';

    /**
     * A change through the profile editing plugin of `academic_persons_edit`.
     */
    case FrontendEditing = 'frontend-editing';

    /**
     * A save through the DataHandler: the backend form, or any other code that
     * writes a default-language profile through it.
     */
    case Backend = 'backend';

    /**
     * A DataHandler run the import code marked with
     * {@see \FGTCLB\AcademicPersons\DataHandling\ProfileWriteCorrelation::Import}.
     */
    case Import = 'import';

    /**
     * A dispatcher that passes no origin, such as code written for an earlier version.
     */
    case Unknown = 'unknown';
}
