..  _developers-event-before-imported-record-write-event:

..  _developers-import-writer-event:

==============================
BeforeImportedRecordWriteEvent
==============================

:php:`\FGTCLB\AcademicPersons\Event\BeforeImportedRecordWriteEvent` is
dispatched once for every record of a person before it is written: the profile
first, then each contract followed by its contact records. It carries the
table, the identifier, the uid of an existing record, the row that is going to
be written and the fields the import code supplied. On an existing record the
row holds the managed fields only, so a listener decides on
:php:`getSuppliedFields()` and changes what is written through the row.

When it is dispatched
=====================

:php:`\FGTCLB\AcademicPersons\Import\ProfileImportWriter::write()` dispatches
the event, for each person import code hands it, see
:ref:`developers-import-writer`. Within one person the order is the profile,
then for each contract the contract, its e-mail addresses, its phone numbers
and its physical addresses. All of it happens before the DataHandler run of the
person starts, so nothing of the person is written yet.

A profile whose :sql:`skip_sync` flag is set is skipped before the event, and
so is a record the identifier finds below another profile or contract. Neither
reaches a listener. :php:`retire()` dispatches no event.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   What it does
    *   -   :php:`getTableName()`
        -   The table of the record.
    *   -   :php:`getIdentifier()`
        -   The import identifier of the record.
    *   -   :php:`getUid()`, :php:`isNew()`
        -   The uid of the existing record, :php:`null` for a record that is
            about to be created.
    *   -   :php:`getRow()`, :php:`setRow()`
        -   What the writer stores, keyed by database column: every supplied
            field for a new record, the managed fields only for an existing
            one.
    *   -   :php:`getSuppliedFields()`
        -   Every field the import code supplied, the ones that are not
            managed included, with the organisational unit and the function
            type of a contract resolved to their uids.
    *   -   :php:`veto()`
        -   Keeps the record, and every record that belongs to it, from being
            written. Takes the reason, which must not be empty.
    *   -   :php:`isVetoed()`, :php:`getVetoReason()`
        -   Whether a listener vetoed the record, and why.

Example
=======

A project that imports persons from a campus management system leaves student
assistants out of the directory, and trims a value the source delivers with
spaces:

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/EventListener/SkipStudentAssistants.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MySitepackage\EventListener;

    use FGTCLB\AcademicPersons\Event\BeforeImportedRecordWriteEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class SkipStudentAssistants
    {
        #[AsEventListener(identifier: 'my-sitepackage/skip-student-assistants')]
        public function __invoke(BeforeImportedRecordWriteEvent $event): void
        {
            if ($event->getTableName() !== 'tx_academicpersons_domain_model_contract') {
                return;
            }
            if (($event->getSuppliedFields()['position'] ?? '') === 'Student assistant') {
                $event->veto('Student assistants are not listed.');
                return;
            }
            $row = $event->getRow();
            if (isset($row['room'])) {
                $event->setRow([...$row, 'room' => trim((string)$row['room'])]);
            }
        }
    }

Rules and pitfalls
==================

:php:`setRow()` replaces the row. The writer applies its rules to it again:
on an existing record a column that is not managed is dropped, :sql:`hidden`
is never written, and the page, the identifier and the relations stay the
writer's. :php:`veto()` keeps the record and every record that belongs to it
from being written and needs a reason, which the result reports. Vetoing stops
the propagation, so later listeners are not called.

*   A vetoed profile writes nothing of the person, a vetoed contract none of
    its contact records.
*   A vetoed record is still one the source supplied. Keep its identifier in
    the list handed to :php:`retire()`, or the next retire hides or deletes
    the record a previous run wrote.
*   A listener is called before anything of the person is written. Do not
    write person records from it, the writer does that in one run afterwards.

Introduced in
=============

*   :ref:`feature-1790970752` introduced the event together with the import
    writer.
