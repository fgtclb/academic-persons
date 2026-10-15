..  _developers-import:

=================
Importing persons
=================

Persons of a source other than the frontend users, an HR or campus management
system for instance, are written by import code of the project. This extension
offers the parts such code needs: an identifier that finds what an earlier run
wrote, and a writer that applies the rules of the managed fields and announces
the result. :ref:`developers-event-before-imported-record-write-event` lets a
listener change or veto each record the writer is about to write.

..  _developers-import-identifier:

Finding what an import wrote
============================

The profile, the contract, the e-mail address, the phone number, the physical
address, the location, the organisational unit and the function type carry a
column :sql:`import_identifier`. It holds the key of the record in the source
it came from, written as :samp:`{source}:{key}`, for instance ``hr:4711``. The
frontend user synchronisation writes ``fe_users:<uid>`` and the identifiers
listed in :ref:`The records of a list <configuration-frontend-user-sync-records>`.
Pick a source name of your own for an import, so its identifiers never meet
the ones of another source.

:php:`\FGTCLB\AcademicPersons\Import\ImportedRecordFinder` looks up the record
that carries an identifier, so the next run of the import updates it instead of
creating a second one:

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/Import/HrImport.php

    $uid = $this->importedRecordFinder->findUid(
        'tx_academicpersons_domain_model_profile',
        'hr:' . $employee['id'],
    );
    $id = $uid ?? StringUtility::getUniqueId('NEW');
    $datamap['tx_academicpersons_domain_model_profile'][$id] = [
        'import_identifier' => 'hr:' . $employee['id'],
        // ...
    ];

:php:`findUid()` returns the uid, or :php:`null` when no record carries the
identifier.

*   It finds the record hidden or not, and whatever its start and end time: an
    import updates what it wrote even after an editor has hidden it.
*   It finds live records of the default language or of all languages only.
    Deleted records, workspace versions and translations are never found.
*   Nothing keeps two records from carrying one identifier, a copy made in the
    backend keeps the one of its original. The record with the lowest uid is
    found then.
*   The identifier is compared exactly, on every database. MySQL and MariaDB
    alone would ignore case and trailing spaces.
*   The empty identifier, which every record an editor created carries, finds
    nothing.
*   A table without the column is refused with an
    :php:`\InvalidArgumentException`.

The backend shows the identifier read-only, and only on a record that has one.
The DataHandler writes the column all the same. Editors find a profile,
location, organisational unit or function type by its identifier in the list
module and in the backend search. The backend search never lists contracts and
contact records. Page TSconfig shows them in the list module, whose search then
finds them:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/page.tsconfig

    mod.web_list.table.tx_academicpersons_domain_model_contract.hideTable = 0

On SQLite a search term with ``_`` finds nothing: the core search escapes it
for :sql:`LIKE` without naming an escape character, and SQLite has no default
one. A part of the identifier without it, ``users:12``, finds the record.

..  _developers-import-writer:

Writing what an import supplies
===============================

:php:`\FGTCLB\AcademicPersons\Import\ProfileImportWriter` writes the persons
of a source other than the frontend users, one person per call. Import code
reads its source and maps each person to plain data objects:
:php:`ImportedProfile` holds the profile fields and its contracts,
:php:`ImportedContract` the contract fields, the import identifiers of its
organisational unit and function type, and its e-mail addresses, phone numbers
and physical addresses as :php:`ImportedContact`. Every record carries its
import identifier, and its fields are database columns.

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/Import/HrImport.php

    use FGTCLB\AcademicPersons\Import\ImportedContact;
    use FGTCLB\AcademicPersons\Import\ImportedContract;
    use FGTCLB\AcademicPersons\Import\ImportedProfile;
    use FGTCLB\AcademicPersons\Import\ProfileImportWriter;
    use FGTCLB\AcademicPersons\Import\RetirePolicy;

    final readonly class HrImport
    {
        public function __construct(
            private HrClient $hrClient,
            private ProfileImportWriter $profileImportWriter,
        ) {}

        public function run(int $storagePage): void
        {
            $keep = [];
            foreach ($this->hrClient->fetchEmployees() as $employee) {
                $person = new ImportedProfile(
                    identifier: 'hr:' . $employee['id'],
                    pid: $storagePage,
                    fields: [
                        'first_name' => $employee['firstName'],
                        'last_name' => $employee['lastName'],
                    ],
                    contracts: [
                        new ImportedContract(
                            identifier: 'hr:contract:' . $employee['contractId'],
                            fields: ['position' => $employee['position']],
                            organisationalUnitIdentifier: 'hr:unit:' . $employee['unitId'],
                            emailAddresses: [
                                new ImportedContact(
                                    identifier: 'hr:mail:' . $employee['id'],
                                    fields: ['email' => $employee['mail'], 'type' => 'business'],
                                ),
                            ],
                        ),
                    ],
                );
                $result = $this->profileImportWriter->write($person);
                foreach ($result->records as $record) {
                    $keep[] = $record->identifier;
                }
            }
            $this->profileImportWriter->retire('hr', $keep, RetirePolicy::Hide);
        }
    }

Every record the source supplied is kept, a vetoed one included. Leave a
record out of the list to retire it. Retire only after a run that got through
the whole source: a run that stopped early would retire every person it did
not get to.

:php:`write()` returns an :php:`ImportResult`: one :php:`ImportedRecordResult`
per record, with its table, identifier, uid and an
:php:`ImportedRecordOutcome` (created, updated, unchanged, skipped, vetoed or
failed) and a reason where one applies, the messages of the write, such as an
organisational unit no record carries, and the errors the DataHandler logged.
An updated record is one whose managed fields were handed to the DataHandler:
whether it stored them is in the errors.

How a record is written:

*   A record whose identifier no live record carries is created with every
    supplied field. A new profile is created on the page the person names, its
    new records on the page of the profile.
*   An existing record gets only the supplied fields that are managed on it,
    see :ref:`configuration-managed-fields`. Without such a declaration none
    of its fields changes. :sql:`hidden` is never written on an existing record.
*   A profile with :sql:`skip_sync` set is not written, nor is anything of it.
    The result reports it as skipped.
*   A record the identifier finds below another profile or contract is skipped
    and reported. The writer does not move records between persons.
*   A new contract or contact record is added after the ones its parent has,
    an editor's included.
*   The organisational unit and the function type are looked up by their
    identifiers. One that no record carries is not set and is reported as a
    message.
*   Fields that name a column the writer sets itself, the uid, the page, the
    identifier, the language and workspace columns and the relations, are
    refused with an :php:`\InvalidArgumentException`, and so is an identifier
    that is empty or used twice for one table within a person.

The whole person is one DataHandler run as an administrator in the live
workspace, marked :php:`ProfileWriteCorrelation::Import`. History, the
reference index and the hooks apply as for a backend save. The profile is
announced once, synchronously and with the origin
:php:`ProfileUpdateOrigin::Import`. With :composer:`fgtclb/academic-persons-edit`
installed, that synchronises its translations and its slug, from a command as
well. Every profile is synchronised in the request that writes it, and the
memory a long import needs grows with it, see
:ref:`important-backend-saves-announce-profile-updates`.

:php:`retire()` hides or deletes, as the :php:`RetirePolicy` says, every
profile, contract and contact record whose identifier starts with
:samp:`{source}:` and is not in the list of identifiers to keep. The list is
one list for every table. Records without an identifier, records of other
sources and records of a profile excluded from the synchronisation are never
retired. Hiding leaves a record that is hidden already alone. Deleting a
profile deletes its contracts and their contact records with it, the ones an
editor created included. The profiles whose contracts or contact records were
retired are announced. Hiding and deleting are handed to the DataHandler, its
errors are in the result.

Before a record is written, the writer hands it to the listeners of
:ref:`developers-event-before-imported-record-write-event`, which may change
what is written or veto the record. The announcement of the written profile is
:ref:`developers-event-after-profile-update-event`, with the origin
:php:`ProfileUpdateOrigin::Import`.
