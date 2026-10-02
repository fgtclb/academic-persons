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
 * What happened to one record of an import, as an {@see ImportedRecordResult}
 * reports it.
 *
 * @api
 */
enum ImportedRecordOutcome: string
{
    /**
     * No live record carried the identifier, and the DataHandler created the
     * record.
     */
    case Created = 'created';

    /**
     * The record existed, and its managed fields were handed to the
     * DataHandler. Whether it stored them is in the errors of the result.
     */
    case Updated = 'updated';

    /**
     * The record existed, and none of the supplied fields is managed on it.
     */
    case Unchanged = 'unchanged';

    /**
     * The record was not written: its profile is excluded from the
     * synchronisation, the record belongs to another profile or contract, or a
     * record it belongs to was vetoed or skipped. The result says which.
     */
    case Skipped = 'skipped';

    /**
     * A listener of {@see \FGTCLB\AcademicPersons\Event\BeforeImportedRecordWriteEvent}
     * vetoed the record.
     */
    case Vetoed = 'vetoed';

    /**
     * The DataHandler did not create the record. Its errors are in the result.
     */
    case Failed = 'failed';

    /**
     * {@see ProfileImportWriter::retire()} handed the record to the DataHandler
     * to hide it. Whether it did is in the errors of the result.
     */
    case Hidden = 'hidden';

    /**
     * {@see ProfileImportWriter::retire()} handed the record to the DataHandler
     * to delete it. Whether it did is in the errors of the result.
     */
    case Deleted = 'deleted';
}
