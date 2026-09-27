..  index:: Configuration; Managed fields
..  _configuration-managed-fields:

==============
Managed fields
==============

A synchronisation or an import fills some fields of a person record, and an
editor who changes such a value in the backend loses the change on the next
run. The :yaml:`managedFields` map of
:file:`Configuration/AcademicPersons/Settings.yaml` names those fields, and the
backend record form renders them read-only, but only on the records the
synchronisation wrote. A record an editor added keeps every field editable.

The :yaml:`readonly` flag of the :ref:`validations <configuration-validations>`
is the other lock. It applies to every record of a table, so it also locks the
e-mail address an editor added by hand. Use :yaml:`readonly` for a field no one
may change, and :yaml:`managedFields` for a field the synchronisation owns.

..  _configuration-managed-fields-records:

Which records are locked
========================

A managed field is read-only on a record that

*   carries an import identifier, as every record written by the frontend
    user synchronisation does (see
    :ref:`configuration-frontend-user-sync-records`), and every imported
    record that sets one,
*   is a record of the default language, and
*   belongs to a profile that is not excluded from the synchronisation with
    :guilabel:`Skip synchronisation`. A hidden profile is still synchronised,
    so its records stay locked.

The field shows a note below its label, after the description it already has:
:guilabel:`Maintained by the synchronisation (fe_users:12), so it is read-only
here.`

Every other record keeps the behaviour it had: a record without an import
identifier, every record of an excluded profile, and every translation. The
synchronisation writes the default language only, so a translated position or
title is text an editor maintains. Fields that are shared by all languages,
such as the names, are read-only on a translation already.

The lock applies to the form only. The synchronisation, an import or a script
keep writing the fields, and a value that reaches the database some other way
is not refused.

Two cases behave differently from what the rules above suggest:

*   A copy of a synchronised record keeps its import identifier, so the copy is
    locked as well. Copying a record is not a way to get an editable one.
*   In a workspace, the profile and the contract a record belongs to are read
    as they are live. Switching :guilabel:`Skip synchronisation` on unlocks the
    profile form at once, and its contracts and contact records once the
    profile is published.

..  _configuration-managed-fields-keys:

The keys
========

The shipped lists are empty, so no field is managed until a package names one:

..  code-block:: yaml
    :caption: EXT:academic_persons/Configuration/AcademicPersons/Settings.yaml

    managedFields:
      profile: []
      contracts: []
      emailAddresses: []
      phoneNumbers: []
      physicalAddresses: []

Each list names fields the way the rest of the file does, by their key or by
the property they address, and only the list of the record's own type applies:

..  list-table::
    :header-rows: 1

    *   -   Key
        -   Names a field of
    *   -   :yaml:`profile`
        -   :yaml:`profile`, for instance :yaml:`title` or :yaml:`website`
    *   -   :yaml:`contracts`
        -   :yaml:`contracts.fields`, for instance :yaml:`position`
    *   -   :yaml:`emailAddresses`
        -   :yaml:`contracts.contactSections.emailAddresses.fields`, for
            instance :yaml:`emailAddress` or its property :yaml:`email`
    *   -   :yaml:`phoneNumbers`
        -   :yaml:`contracts.contactSections.phoneNumbers.fields`
    *   -   :yaml:`physicalAddresses`
        -   :yaml:`contracts.contactSections.physicalAddresses.fields`

:yaml:`type` names the type of the list it is in: listed below
:yaml:`phoneNumbers`, it locks the type of a phone number and nothing else.

A field the file does not configure cannot be managed. A name that matches no
field makes every person record form fail with the exception ``1790536034``,
which names it, until the list is corrected. Flush the TYPO3 caches after a
change, as after every change of the file.

..  _configuration-managed-fields-example:

Example: synchronised names and contacts
========================================

The three name fields ship locked for every profile, with :yaml:`readonly` and
:yaml:`disabled` (see :ref:`configuration-validations-defaults`). A site
package that wants them locked on synchronised profiles only replaces those
flags, and names the fields together with the contract position and the
e-mail address:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicPersons/Settings.yaml

    profile:
      firstName:
        validators:
          - required
          - frontendreadonly
      middleName:
        validators:
          - frontendreadonly
      lastName:
        validators:
          - required
          - frontendreadonly

    managedFields:
      profile:
        - firstName
        - middleName
        - lastName
      contracts:
        - position
      emailAddresses:
        - emailAddress

Backend editors can now correct the names of a profile they created, and the
synchronised ones stay locked. :yaml:`frontendreadonly` keeps profile owners
away from the names in the editing frontend, which does not read
:yaml:`managedFields`. :yaml:`required` keeps the first and last name
required in the backend, as the TCA of the profile table declares them.
