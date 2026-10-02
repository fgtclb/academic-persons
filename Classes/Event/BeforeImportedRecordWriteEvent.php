<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Offers each record {@see \FGTCLB\AcademicPersons\Import\ProfileImportWriter}
 * is about to write to the listeners, before it enters the DataHandler run:
 * the profile first, then each contract followed by its e-mail addresses,
 * phone numbers and physical addresses.
 *
 * The row is what the writer stores, keyed by database column. For a new
 * record that is every supplied field, with the organisational unit and the
 * function type of a contract resolved to their uids. For an existing record
 * it is the supplied fields that are managed on it, which may be none. A
 * listener may replace the row. The writer applies the same rules to the
 * replaced row as to the supplied one: on an existing record a column that is
 * not managed is dropped again, `hidden` is never written, and the page, the
 * identifier and the relations to the parent records are always the writer's.
 *
 * A listener may veto the record. It is then not written, and neither is any
 * record that belongs to it: a vetoed profile writes nothing of the person, a
 * vetoed contract none of its contact records. Vetoing stops the propagation,
 * so later listeners are not called.
 *
 * @api
 */
final class BeforeImportedRecordWriteEvent implements StoppableEventInterface
{
    private ?string $vetoReason = null;

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $suppliedFields
     */
    public function __construct(
        private readonly string $tableName,
        private readonly string $identifier,
        private readonly ?int $uid,
        private array $row,
        private readonly array $suppliedFields,
    ) {}

    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * The import identifier of the record.
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * The uid of the existing record, null for a record that is about to be
     * created.
     */
    public function getUid(): ?int
    {
        return $this->uid;
    }

    public function isNew(): bool
    {
        return $this->uid === null;
    }

    /**
     * @return array<string, mixed> database column => value
     */
    public function getRow(): array
    {
        return $this->row;
    }

    /**
     * The fields the import code supplied for the record, with the
     * organisational unit and the function type of a contract resolved to
     * their uids. On an existing record this holds the fields that are not
     * managed as well, which the row leaves out, so a listener decides on the
     * source data here and changes what is written through the row.
     *
     * @return array<string, mixed> database column => value
     */
    public function getSuppliedFields(): array
    {
        return $this->suppliedFields;
    }

    /**
     * @param array<string, mixed> $row database column => value
     */
    public function setRow(array $row): void
    {
        $this->row = $row;
    }

    /**
     * Keeps the record, and every record that belongs to it, from being
     * written. The reason is reported in the result of the write.
     *
     * @throws \InvalidArgumentException for an empty reason
     */
    public function veto(string $reason): void
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('A vetoed import record needs a reason.', 1790970746);
        }
        $this->vetoReason = $reason;
    }

    public function isVetoed(): bool
    {
        return $this->vetoReason !== null;
    }

    public function getVetoReason(): ?string
    {
        return $this->vetoReason;
    }

    public function isPropagationStopped(): bool
    {
        return $this->isVetoed();
    }
}
