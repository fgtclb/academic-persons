..  _feature-1790970752:

====================================================
Feature: Import writer for persons of another source
====================================================

Description
===========

Only the frontend user synchronisation filled person records so far. A project
that imports persons from another source, an HR or campus management system,
had to write its own DataHandler pipeline, and such pipelines tended to ignore
the import identifier and :sql:`skip_sync` and never announced the update, so
translations and slugs went stale.

:php:`\FGTCLB\AcademicPersons\Import\ProfileImportWriter` does that part now.
Import code reads its source, maps each person to an
:php:`ImportedProfile` with its contracts and their contact records, and hands
it over:

*   Every record is matched by its import identifier. Writing the same person
    twice yields one profile.
*   A new record gets every supplied field. An existing record gets only the
    supplied fields that are managed on it, so what an editor changed beside
    them survives. Without a managed field declaration no field of an
    existing record changes.
*   The visibility of an existing record is never changed by a write.
*   A profile excluded from the synchronisation is left untouched and reported
    as skipped.
*   Organisational units and function types are looked up by their import
    identifiers, never created.
*   :php:`\FGTCLB\AcademicPersons\Event\BeforeImportedRecordWriteEvent` lets a
    listener change the values of each record or veto it.
*   :php:`retire()` hides or deletes the records of a source that it no longer
    supplies. Records without an identifier, records of other sources and
    excluded profiles are never retired themselves. Deleting a profile or a
    contract deletes the records that belong to it, the ones an editor added
    included.
*   Each person is one DataHandler run in the live workspace, so history, the
    reference index and the hooks apply as for a backend save, and the profile
    is announced once with the origin :php:`ProfileUpdateOrigin::Import`. With
    :composer:`fgtclb/academic-persons-edit` installed, that synchronises its
    translations and its slug, also from a command.

The writer, its data objects, its result and the event are public API.

See :ref:`developers-import-writer`.

Impact
======

Nothing changes for an installation that does not call the writer. Existing
import code keeps working.

Affected Installations
======================

Installations that import persons from a source other than the frontend users.

..  index:: PHP-API, ext:academic_persons
