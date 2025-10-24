.. include:: /Includes.rst.txt

.. _feature-1746706700:

====================================================================================
Feature: Add `academic:createprofiles` options `--include-pids` and `--exclude-pids`
====================================================================================

Description
===========

`EXT:academic_persons` provides a command to create profiles for
frontend users, extendable by dispatching events to allow devs
customizing the profile creation in projects, for example to
base it on special needs like retrieving updated user data from
LDAP oder other services.

Until now, all frontend users without profiles on any pid has
been fetched, which does not respect use-cases where frontend
users for dedicated logins are required and profile creation
is not wanted, needed or suitable.

This change adds following new options to the provided command
`vendor/bin/typo3 academic:createprofiles`:

* `--include-pids`: comma-separated list of storage pid's from
  which frontend users should be fetched (only).

* `--exclude-pids`: comma-separated list of storage pid's from
  which frontend users should be ignored (skipped).


Important note
--------------

While both options can be used together it is important to
know that `--exclude-pids` takes higher priorities and are
ignored even if pid is also included in `include-pids`.

.. index:: CLI
