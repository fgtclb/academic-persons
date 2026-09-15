.. _important-ace-667-academic-persons:

================================================================
Important: Profile synchronization ignores the visibility window
================================================================

Description
===========

The profile synchronization keeps hidden profiles and profiles of disabled
frontend users up to date, see :ref:`feature-1782285580` and
:ref:`breaking-1782285582`. Profiles outside their start or end time, and
profiles restricted to a frontend user group, were still skipped: the profile
update command did not find them and never updated them. Frontend users
outside their own start or end time were not selected by either command.

Both commands now ignore the whole visibility window:

*   The profile update command finds a profile regardless of its hidden
    flag, start time, end time and frontend user group and updates it from
    its frontend user. Of these four, only the ones the profile table
    declares as enable columns in the installation are ignored; any other
    enable column stays in effect.
*   The profile create and update commands select frontend users outside
    their start and end time.

Deleted profiles and deleted frontend users stay excluded. The
synchronization still never changes the visibility of a profile: ``hidden``,
``starttime``, ``endtime`` and ``fe_group`` keep their values, and a profile
outside its window stays invisible in the frontend. The "show hidden records"
plugin option lifts the hidden flag only, as before.

The :php:`$showHidden` argument of
:php:`\FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository::findByFrontendUser()`
now lifts all four enable fields. It exists for the synchronization and is
not meant for display.

Impact
======

Profiles outside their visibility window are updated on the next
synchronization run. Profiles restricted to a frontend user group are
included, because a command-line run has no frontend user group that could
match. A frontend user outside its start and end time gets a profile on the
next create run.

To exclude a profile from the synchronization, use its ``skip_sync`` flag,
not its visibility.

Affected Installations
======================

Installations that run the profile create or update command and control the
visibility of profiles or frontend users through start time, end time or
frontend user groups, for example from a directory import.

.. index:: CLI, Database, ext:academic_persons
