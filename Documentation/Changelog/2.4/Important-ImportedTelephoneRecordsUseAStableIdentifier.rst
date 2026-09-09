..  _important-imported-telephone-records-use-a-stable-identifier:

=============================================================
Important: Imported telephone records use a stable identifier
=============================================================

Description
===========

The import identifier of a synchronised phone-number record used to be built
from its type, so it read ``phone:fe_users:<uid>`` for a telephone number. That
made the identity of the record depend on a value that is now configurable, and
a reconfiguration would have created a second record instead of updating the
first one.

The identifier is now built from the **source field** of ``fe_users`` and never
from the type:

*   telephone numbers: ``telephone:fe_users:<uid>`` — changed
*   fax numbers: ``fax:fe_users:<uid>`` — unchanged

The record is matched on that identifier alone. A record still carrying the
legacy ``phone:fe_users:<uid>`` is recognised, renamed and — where its type is
still the unselectable ``phone`` — retyped, in one step. Where a contract
carries both a legacy and a canonical record, the canonical one wins and the
legacy one is left untouched, because its provenance cannot be established
safely.

Impact
======

Two stored values change for records the synchronisation writes:
``import_identifier`` for telephone records, and ``type`` wherever it still held
``phone`` or ``fax`` and that value is not selectable on the installation.
Anything outside this extension that keys on ``phone:fe_users:<uid>`` has to be
adjusted.

**No upgrade wizard is shipped, deliberately.** Existing records are repaired by
the next :bash:`academic:updateprofiles` run — the same command that wrote them —
and that run is what the affected installations already schedule. A bulk
migration would have to decide what the synchronisation deliberately refuses to
decide: which of two colliding records is the real one, whether soft-deleted and
workspace rows take part, and what to write where the configured type is not
selectable.

Six cases are consequently not repaired by a synchronisation run, and an
installation that needs them corrected has to act deliberately:

*   profiles carrying ``skip_sync = 1``
*   frontend users that are soft-deleted
*   profiles whose ``tx_academicpersons_feuser_mm`` relation was removed
*   records on page ids excluded by the configured ``--include-pids`` or
    ``--exclude-pids``
*   installations that ran an import once and never run it again
*   records whose ``fe_users.telephone`` has since been emptied, which the
    synchronisation removes rather than repairs

Affected Installations
======================

Every installation that imports profiles from frontend users with
:bash:`academic:createprofiles` or :bash:`academic:updateprofiles`.

.. index:: Backend, CLI, Database, ext:academic_persons
