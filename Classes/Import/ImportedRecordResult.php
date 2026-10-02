<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Import;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * What happened to one record of an import.
 *
 * @api
 */
#[Exclude]
final readonly class ImportedRecordResult
{
    /**
     * @param string $tableName the table of the record
     * @param string $identifier its import identifier
     * @param int|null $uid its uid, null for a record that does not exist, and for
     *                      the records below a profile or contract that was not written
     * @param string $reason why it was skipped, vetoed or failed, empty otherwise
     */
    public function __construct(
        public string $tableName,
        public string $identifier,
        public ImportedRecordOutcome $outcome,
        public ?int $uid = null,
        public string $reason = '',
    ) {}
}
