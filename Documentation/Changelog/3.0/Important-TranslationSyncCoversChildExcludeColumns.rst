.. _important-translation-sync-covers-child-exclude-columns:

========================================================
Important: Translation sync covers child exclude columns
========================================================

Description
===========

The update path of the translation synchronisation — the case where a
translation already exists — re-submitted the :php:`l10n_mode=exclude` values
of the profile record only. A child record's exclude value changed after the
child's translation existed therefore stayed stale in that translation: a
contract's :php:`valid_from`, an address type, a profile information year.

The datamap now covers the whole default-language inline child tree: every
child's propagatable exclude values are part of the same single DataHandler
pass, and the core :php:`DataMapProcessor` carries them into every translation
of every touched record.

Two things did **not** change, and are now pinned by tests:

*   File references and MM relations added to the default record after its
    translation exists were *always* carried over — the core synchronizes all
    exclude columns of a touched record from its database row, including the
    relational ones. The previously documented gap was design-inferred and did
    not exist.
*   :php:`enableLogging` stays on: :sql:`sys_log` rows with ``userid=0`` are
    the audit trail of what the synchronisation wrote.

Impact
======

Editing an exclude column of a contract, address, email address, phone number
or profile information record of an already-translated profile now reaches the
record's translations on the next synchronisation, the same way it always did
for the profile's own exclude columns.

Affected Installations
======================

Every installation using the translation synchronisation of this extension —
through the frontend editing of :php:`academic_persons_edit`, or by
dispatching :php:`AfterProfileUpdateEvent` from its own hooks.
(:php:`academic:updateprofiles` does not dispatch the event and is therefore
not affected — that gap is tracked separately.)

.. index:: Frontend, Backend, PHP-API, ext:academic_persons
