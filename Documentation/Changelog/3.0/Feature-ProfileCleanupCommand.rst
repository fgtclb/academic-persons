.. _feature-profile-cleanup-command:

======================================================================
Feature: Command to hide or delete profiles of inactive frontend users
======================================================================

Description
===========

:bash:`academic:updateprofiles` keeps profiles up to date with their frontend
users, but never changes whether a profile is shown. A profile therefore stayed
online after its frontend user was disabled or deleted, and projects wrote a
command of their own to take it offline.

The new command :bash:`academic:cleanupprofiles` looks at the profiles the
synchronisation manages, and at the frontend users linked to each of them:

*   When every linked frontend user is disabled or past its end time, the
    profile is hidden. :bash:`--disabled=keep` leaves it.
*   When every linked frontend user is deleted, or its record is gone, the
    profile is deleted together with its translations and contracts.
    :bash:`--deleted=hide` hides it instead, :bash:`--deleted=keep` leaves it.
*   A profile with at least one active frontend user, a profile with
    :guilabel:`Disable profile sync` set, a profile without a linked frontend
    user and a profile an editor linked to a login the synchronisation does
    not read are never touched.

:bash:`--include-pids` and :bash:`--exclude-pids` choose the profiles by the
pages of their frontend users: one frontend user on an included page takes a
profile in, one on an excluded page leaves it alone. :bash:`--dry-run` lists
what the command would do and writes nothing.

Every change is saved through the DataHandler, like a change in the backend:
the history records it, the cached list and detail pages are cleared, and a
deleted profile can be restored. The command never shows a profile again.

See :ref:`configuration-profile-cleanup`.

Impact
======

The command changes nothing until it is run. Before scheduling it, run it once
with :bash:`--dry-run`: deleting is the default for profiles whose frontend
users are all deleted.

A profile deleted or restored in the backend now clears the cached list and
detail pages as well. Before, an installation without the automatic cache
tagging of the core kept showing a deleted profile until the cache expired. It
is off in instances upgraded from a TYPO3 version before v13.3.

A project that ships a command of its own named
:bash:`academic:cleanupprofiles` keeps running that one: of two commands with
the same name, the one registered last is used. Remove the project's command to
use this one.

.. index:: CLI, ext:academic_persons
