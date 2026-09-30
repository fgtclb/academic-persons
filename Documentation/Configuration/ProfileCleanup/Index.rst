..  index:: Configuration; Profile cleanup
..  _configuration-profile-cleanup:

===============
Profile cleanup
===============

:bash:`academic:updateprofiles` keeps a profile up to date with its frontend
user and never changes whether the profile is shown. When a frontend user is
disabled or deleted, for example by an LDAP import that no longer finds the
person, the profile stays online. :bash:`academic:cleanupprofiles` hides or
deletes such profiles:

..  code-block:: bash

    vendor/bin/typo3 academic:cleanupprofiles --dry-run

Like the create and the update command, it can be run from the scheduler with
the task :guilabel:`Execute console commands`.

..  _configuration-profile-cleanup-rule:

Which profiles are cleaned up
=============================

A profile is cleaned up only when **every** frontend user linked to it is
inactive:

*   A frontend user counts as **deleted** when it is deleted, or when the
    record it was linked to does not exist any more.
*   A frontend user counts as **disabled** when it is disabled, or when its
    end time has passed. A start time that lies ahead does not count: that
    frontend user is about to become active.

A profile whose frontend users are all deleted is handled by :bash:`--deleted`.
A profile whose frontend users are all inactive, at least one of them disabled
rather than deleted, is handled by :bash:`--disabled`.

The command looks only at profiles the synchronisation manages: a profile
with an import identifier, which the synchronisation writes, or with a
frontend user of the record type the synchronisation reads.

The backend shows :guilabel:`Disable profile sync` only for a profile with an
import identifier. A profile reached through the record type alone, for
example one written by a profile factory of a project that sets no import
identifier, cannot be excluded that way. Keep it out of the cleanup with
:bash:`--exclude-pids`, or give it an import identifier.

These profiles are never touched:

*   a profile with at least one active frontend user,
*   a profile with :guilabel:`Disable profile sync` set (:sql:`skip_sync`),
*   a profile without any linked frontend user, for example one an editor
    created,
*   a profile an editor linked to a login the synchronisation does not read.

The command looks at the default language of a profile, and at profiles set to
all languages. A translation is hidden or deleted together with it.

..  _configuration-profile-cleanup-options:

Options
=======

..  list-table::
    :header-rows: 1

    *   -   Option
        -   Meaning
    *   -   :bash:`--disabled`
        -   What happens to a profile whose frontend users are all disabled or
            past their end time: ``hide`` (the default) or ``keep``.
    *   -   :bash:`--deleted`
        -   What happens to a profile whose frontend users are all deleted:
            ``delete`` (the default), ``hide`` or ``keep``.
    *   -   :bash:`--include-pids`, :bash:`-i`
        -   A comma-separated list of page uids. Only profiles with a frontend
            user stored on one of these pages are cleaned up.
    *   -   :bash:`--exclude-pids`, :bash:`-e`
        -   A comma-separated list of page uids. A profile with a frontend user
            stored on one of these pages is left alone.
    *   -   :bash:`--dry-run`
        -   Lists every profile the command would hide or delete, and writes
            nothing.

The page options choose which profiles are looked at. Whether a profile is
cleaned up is still decided by all its frontend users, wherever they are
stored. A frontend user record that no longer exists lies on no page, so a
profile linked only to such records is cleaned up by a run without
:bash:`--include-pids` only. A list with anything but page uids, such as
``12;13``, stops the command before it reads anything.

..  _configuration-profile-cleanup-writes:

What the command writes
=======================

Every change is saved like a change in the backend, so it is recorded in the
history of the profile, and the cached list and detail pages are cleared:

*   **Hiding** sets the profile to hidden. Its translations follow.
*   **Deleting** deletes the profile with its translations and its contracts,
    together with their addresses, e-mail addresses and phone numbers. A
    deleted profile can be restored from its history in the backend.

A profile that is hidden already is not hidden again, and not listed. The
command lists every profile it hides or deletes, and exits with an error when
one of them could not be saved.

..  important::

    **The command never shows a profile again.** When a frontend user is
    enabled again, or imported again after it was deleted, its profile stays
    hidden until an editor shows it. A frontend user imported again is usually
    a new record, so the command could not tell it from somebody else anyway.

..  _configuration-profile-cleanup-first-run:

The first run
=============

Deleting is the default for profiles of deleted frontend users. Before the
first scheduled run, run the command with :bash:`--dry-run` and check the
list. An import that failed and disabled or deleted every frontend user would
otherwise hide or delete every profile. When deleting is not wanted at all,
schedule the command with :bash:`--deleted=hide`.
