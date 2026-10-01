..  _feature-1790866812:

======================================================================
Feature: Import identifiers are shown, searchable and can be looked up
======================================================================

Description
===========

The profile, the contract, the e-mail address, the phone number, the physical
address, the location, the organisational unit and the function type carry a
column :sql:`import_identifier`. The frontend user synchronisation writes it,
but no backend form showed it, the backend search did not find it and import
code had to query it by hand.

*   The backend form of a record that carries an identifier shows it
    read-only, in a new palette :guilabel:`Import` of the tab
    :guilabel:`Extended`. The contract form gains that tab for it.
*   On a profile, :guilabel:`Disable profile sync` moved into the same palette.
    It sat next to the visibility switch on the tab :guilabel:`Access` before.
*   The search of the list module and the backend search find a profile,
    location, organisational unit or function type by its identifier, on
    TYPO3 v13 and v14. The backend search never lists contracts and contact
    records. Page TSconfig shows them in the list module, whose search then
    finds them.
*   :php:`\FGTCLB\AcademicPersons\Import\ImportedRecordFinder::findUid()`
    returns the uid of the live record of a table that carries an identifier,
    hidden and time-restricted records included. It is public API.
*   The identifier is written as :samp:`{source}:{key}`, for instance
    ``fe_users:12`` or ``hr:4711``.

See :ref:`developers-import-identifier`.

Impact
======

Editors see the field on every record the synchronisation or an import wrote,
and no field on the records they created themselves. The column was
:php:`passthrough` before and is a read-only :php:`input` now. The DataHandler
does not read :php:`readOnly`, so the synchronisation and imports write it as
before. A project that hides the field adds a TCEFORM setting:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/page.tsconfig

    TCEFORM.tx_academicpersons_domain_model_contract.import_identifier.disabled = 1

A search for a number in the list module also compares it with the text of
every searchable field, so searching a folder of person records for a uid such
as ``12`` now also lists the records whose identifier contains ``12``.

The address table gains the index on the column that the other tables have:
**run the database analyzer once after updating**.

On SQLite the core search cannot match ``_``: it escapes the character for
:sql:`LIKE` without naming an escape character, and SQLite has no default one.
A search for ``fe_users:12`` finds nothing there, one for ``users:12`` finds the
record.

Affected Installations
======================

Every installation of this extension. The new field shows only on records with
an identifier.

..  index:: Backend, Database, PHP-API, TCA, ext:academic_persons
