<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * A database column that two or more fields of one validation set of the
 * persons settings name.
 *
 * The fields are listed in the order the TCA merge applies them, so the last
 * one decides the backend flags of the column, and the flags of the others are
 * lost. `recordType` is set for the profile information table, whose flags land
 * in the `columnsOverrides` of a record type rather than on the column.
 *
 * @internal not part of public API.
 */
#[Exclude]
final readonly class SharedColumn
{
    /**
     * @param list<string> $fields at least two, in the order the merge applies them
     */
    public function __construct(
        public string $table,
        public string $column,
        public array $fields,
        public string $recordType = '',
    ) {}

    /**
     * The field whose flags the column gets.
     */
    public function getDecidingField(): string
    {
        return $this->fields[count($this->fields) - 1] ?? '';
    }

    /**
     * The fields whose flags are lost.
     *
     * @return list<string>
     */
    public function getLostFields(): array
    {
        return array_slice($this->fields, 0, -1);
    }

    /**
     * Table and column, with the record type where the flags are set per type,
     * for example `tx_academicpersons_domain_model_profile.teaching_area`.
     */
    public function getSubject(): string
    {
        $subject = $this->table . '.' . $this->column;
        if ($this->recordType !== '') {
            $subject .= sprintf(' (record type "%s")', $this->recordType);
        }
        return $subject;
    }
}
