..  _feature-legacy-settings-overlay-and-migration-command:

======================================================
Feature: Legacy settings overlay and migration command
======================================================

Description
===========

A site package that still ships the pre-3.0 shape of
:file:`Configuration/AcademicPersons/Settings.yaml` - the :yaml:`validations`
map with one flag list per record type, and the :yaml:`profileInformationsTypes`
map - is no longer ignored. Its two keys are mapped onto the four section
maps of 3.0 at runtime, before the settings graph is built, so the
installation keeps behaving as it was configured on the day of the update:
the backend record editor and the editing frontend see the flags the override
declared, not the shipped defaults.

The mapping is an overlay on the shipped maps. A legacy set decides the five
flags the old shape knew - :yaml:`required`, :yaml:`readonly`,
:yaml:`disabled`, :yaml:`email` and :yaml:`number` - for every field of its
target; a field the set does not list has none of them, exactly as it was
unconfigured before, and the flags the old shape could not express -
:yaml:`url`, :yaml:`date`, :yaml:`tel`, :yaml:`textarea`, :yaml:`html` -
stay as the section maps declare them. Two things are not mapped losslessly
and are reported:

*   An eighth timeline entry type declared under
    :yaml:`profileInformationsTypes` is not migrated. It needs a profile
    relation and a TCA column the settings never created; see the Breaking
    entry on the section based settings for how to keep one.
*   The :yaml:`type` and :yaml:`fieldName` of a timeline entry type are not
    applied. Until 2.4 the two generated the inline column of the profile
    table, so overriding one moved the backend relation and the frontend
    selection together; since 3.0 the seven relations are declared in the TCA
    of the profile table, and applying the override would move the frontend
    half alone - records created in the editing frontend would be invisible in
    the backend, and the other way round. The section keeps the record type and
    the relation field that match the TCA, and the value the override named is
    printed as a note. The :yaml:`label` of the type is mapped as before.

Every package that ships a legacy key is logged once per key at warning
level, naming the package, the key and the command below. The mapping is
transitional and is removed in academic_persons 4.0.

**The console command** ``academic:persons:settings:migrate`` prints, for
every active package that still ships a legacy key, the four section maps
those keys are mapped onto - the document that replaces the legacy keys in
that package's file - together with the notes about what could not be
mapped, and exits with 1 when such a package exists, so a deployment
pipeline can gate on it:

..  code-block:: bash

    vendor/bin/typo3 academic:persons:settings:migrate > migrated.yaml

The command never writes the file. The override lives in a site package that
is under version control and usually deployed read-only, so a write would be
lost on the next deployment or leave a dirty working tree; the printed maps
are pasted into the package after review.

**The status report** of EXT:reports lists, under *Academic Persons*, every
active package that still ships a legacy key as a warning. The status
provider is registered only when EXT:reports is installed; there is no
dependency on it.

Impact
======

An installation with a pre-3.0 override runs on its own flags again after
the update, with a warning in the log and in the status report until the
override is rewritten. **No cache flush is needed for the overlay to take
effect**: the normalised settings graph is cached under the identifier
``AcademicPersons_Settings_v3``, while releases before 3.0 wrote
``AcademicPersons_Settings``, so the first request after the update is a
cache miss and rebuilds the graph through the overlay. A flush stays
necessary after every later edit of the file, as before. The migration itself - replacing the legacy keys with
the printed maps and flushing the caches - is described on the
:ref:`Validation settings <configuration-validations-migration>` page.

..  index:: Configuration, CLI, Backend, ext:academic_persons
