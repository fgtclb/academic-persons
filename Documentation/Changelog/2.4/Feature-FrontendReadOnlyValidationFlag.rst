.. _feature-1790531591:

=============================================================
Feature: A validator flag that locks the frontend editor only
=============================================================

Description
===========

The :yaml:`readonly` and :yaml:`disabled` flags of a field in
:file:`Configuration/AcademicPersons/Settings.yaml` lock it in the editing
frontend and in the TYPO3 backend record editor at the same time. Installations
that synchronise a field from a directory service usually want profile owners
kept away from it while backend editors can still correct it, and had to reset
the backend lock field by field through TCEFORM.

The new flag :yaml:`frontendreadonly` locks a field in the editing frontend
only. The example keeps the name fields away from profile owners and lets
backend editors correct them:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/AcademicPersons/Settings.yaml

    validations:
      profile:
        firstName:
          - required
          - frontendreadonly
        middleName:
          - frontendreadonly
        lastName:
          - required
          - frontendreadonly
      # contract, emailAddress, phoneNumber, physicalAddress and
      # profileInformation repeated as shipped

A :yaml:`validations` map of a site package replaces the shipped one as a
whole, so every shipped set that should stay has to be repeated. A field that
is listed gets exactly the flags listed for it: without :yaml:`required`, the
backend no longer requires it either.

*   The editing frontend shows the field read-only and ignores a submitted
    value, exactly as for :yaml:`readonly`.
*   The backend record editor keeps the field editable. A :yaml:`required`
    beside it still makes the field required there, while the editing frontend
    does not ask the owner for a value they cannot change.
*   Listed together with :yaml:`readonly` or :yaml:`disabled`, the field stays
    locked in the backend as well.

The flag is matched case-insensitively, like every other flag, and works in
every validation set.

From academic_persons 3.0 on, the flags are listed in the :yaml:`validators`
of the field itself, for example
:yaml:`profile.lastName.validators: [required, frontendreadonly]`. A
:yaml:`validations` map of 2.4 that lists the flag keeps it after the update
to 3.0.

Impact
======

Nothing changes for an installation that does not list the flag. A TCEFORM
reset of :php:`readOnly` that only served to unlock the backend can be
replaced by :yaml:`frontendreadonly` in place of :yaml:`readonly`.

.. index:: Backend, Frontend, ext:academic_persons
