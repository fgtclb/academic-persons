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
 * What a write or a retirement of {@see ProfileImportWriter} did: one
 * {@see ImportedRecordResult} per record, the problems that did not stop it,
 * and the errors the DataHandler reported.
 *
 * @api
 */
#[Exclude]
final readonly class ImportResult
{
    /**
     * @param list<ImportedRecordResult> $records in the order they were handed over
     * @param list<string> $messages problems the import code should know about, such as an unknown organisational unit
     * @param list<string> $errors the error log of the DataHandler
     */
    public function __construct(
        public array $records = [],
        public array $messages = [],
        public array $errors = [],
    ) {}

    /**
     * The result of one record, or null when the import did not get to it.
     */
    public function getRecord(string $tableName, string $identifier): ?ImportedRecordResult
    {
        foreach ($this->records as $record) {
            if ($record->tableName === $tableName && $record->identifier === $identifier) {
                return $record;
            }
        }
        return null;
    }

    /**
     * @return list<ImportedRecordResult>
     */
    public function getRecordsWithOutcome(ImportedRecordOutcome $outcome): array
    {
        return array_values(array_filter(
            $this->records,
            static fn(ImportedRecordResult $record): bool => $record->outcome === $outcome,
        ));
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
