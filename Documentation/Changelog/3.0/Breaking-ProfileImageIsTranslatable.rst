.. _breaking-profile-image-is-translatable:

======================================
Breaking: The profile image translates
======================================

Description
===========

The :sql:`image` column of :sql:`tx_academicpersons_domain_model_profile` was
declared :php:`'l10n_mode' => 'exclude'` with
:php:`'l10n_display' => 'defaultAsReadonly'`: every translation of a profile
carried the default-language image, the translation form showed it read-only,
and there was no way to give one language a picture of its own.

The column now declares :php:`'behaviour' => ['allowLanguageSynchronization' => true]`
instead. A translation starts out following the default-language image — the
core's ``parent`` localization state, which is also what every existing
translation is in, because no :sql:`l10n_state` has been stored for the column
yet — and switches to the ``custom`` state as soon as it gets an image of its
own. In the ``parent`` state an image the default-language profile gains or
changes reaches the translation on the next write of the default record
through the TYPO3 DataHandler — a backend save and the translation
synchronisation of this extension both are one — and a removal reaches it
through the DataHandler's own delete cascade, which deletes the localizations
of a deleted reference. In the ``custom`` state the translation keeps its
image whatever happens to the default one.

The relation itself is written by one service,
:php:`FGTCLB\AcademicPersons\Service\ProfileImageRelationWriter` (:php:`@internal`),
which drives every change through the DataHandler and maintains the
localization state. The frontend profile editing of
`EXT:academic_persons_edit` uses that service as well from 3.0.0 on - its
editor replacement of the same release no longer writes the image relation
through Extbase - so an image uploaded there for a translation reaches the
``custom`` state exactly as a backend upload does.

The title and alternative text of the image reference follow the name of the
profile record the reference belongs to: a backend save, a localization and a
frontend profile update all rewrite the :sql:`title` and :sql:`alternative` of
the profile's own :sql:`sys_file_reference` row from that record's title and
names. Only the reference row is written; the :sql:`sys_file_metadata` row of
the file, which a file may share between the languages of a profile, stays the
backend editor's.

Impact
======

*   The backend translation form shows the image field editable, with the
    language synchronization toggle the core renders for such columns. An
    editor can keep a translation in sync with the default language or give it
    an image of its own.

*   The translation synchronisation of this extension (see
    :ref:`important-translation-sync-covers-child-exclude-columns`) needs no
    change for it: the same core pass that propagates the exclude columns
    honours the localization state of the image.

*   The image reference of a profile carries the profile's name as title and
    alternative text after the next save of the record — from a backend save,
    a localization, or a profile update announced through
    :php:`AfterProfileUpdateEvent`. A title or alternative text maintained on
    the reference row by hand is overwritten then; text maintained on the
    file's metadata is not.

*   Workspaces are unchanged for an edit: the writer addresses live records and
    lets the DataHandler produce the versioned rows, so a draft edit of a
    profile image stays in its workspace. An installation-wide repair is the
    exception and says so: the repair wizard of
    :composer:`fgtclb/academic-persons-edit` skips workspace rows and acts in
    the live workspace regardless of the workspace the person running it has
    selected.

*   Code that read the image of a translation through the default-language
    record — because the translation could never differ — has to resolve the
    translation's own reference now.

Affected Installations
======================

Every installation with translated profiles. Existing data needs no change:
translations without a stored localization state follow the default-language
image exactly as before.

Installations that uploaded profile images through
:php:`academic_persons_edit` before 3.0 should run the upgrade wizard
``academicPersonsEdit_repairLocalizedProfileImages`` of that extension, which
brings the relation rows the old upload and synchronisation paths wrote into
the shape the localization state expects.

.. index:: TCA, Backend, Frontend, FAL, Localization, ext:academic_persons
