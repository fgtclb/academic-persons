<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Import;

/**
 * What {@see ProfileImportWriter::retire()} does with a record its source no
 * longer supplies.
 *
 * @api
 */
enum RetirePolicy: string
{
    /**
     * Hides the record. An editor can show it again, and the next write of the
     * same identifier updates it without showing it.
     */
    case Hide = 'hide';

    /**
     * Deletes the record, together with its own records: a profile with its
     * contracts, a contract with its contact records.
     */
    case Delete = 'delete';
}
