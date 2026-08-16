..  index:: Configuration; Validations
..  _configuration-validations:

===================
Validation settings
===================

:file:`Configuration/AcademicPersons/Settings.yaml` describes, per record type
and per field, whether a field is **required**, **read only** or **disabled**.

One file drives **both editing contexts**:

*   the TYPO3 backend record editor (FormEngine), through generated TCA, and
*   the frontend editing forms of `EXT:academic_persons_edit
    <https://extensions.typo3.org/extension/academic_persons_edit>`__.

This is why the file ships with :guilabel:`academic_persons`, which owns the
records and their TCA, and not with the editing extension. The backend half
applies even when the editing extension is not installed.

..  attention::
    The syntax of this file is still considered experimental and may change in
    a future release.

Where the sets live
===================

Six sets exist, one per editable record type. The set name selects the record
type, the keys below it are **property names** in camel case, and each property
carries a list of flags:

..  code-block:: yaml

    validations:
      profile:
        firstName:
          - disabled
        website:
          - required

The six set names, and the records they configure:

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Record
    *   -   :yaml:`profile`
        -   Profile
    *   -   :yaml:`contract`
        -   Contract
    *   -   :yaml:`emailAddress`
        -   Email address
    *   -   :yaml:`phoneNumber`
        -   Phone number
    *   -   :yaml:`physicalAddress`
        -   Physical address
    *   -   :yaml:`profileInformation`
        -   Profile information

A property that is not listed is unconfigured: it is editable, not required, and
no validator runs for it.

Available flags
===============

Flag names are matched case insensitively. Anything not listed here is ignored.

..  list-table::
    :header-rows: 1

    *   -   Flag
        -   Effect
    *   -   :yaml:`required`
        -   The field must not be empty. Adds a *not empty* validation in the
            frontend and marks the field required in the backend.
    *   -   :yaml:`disabled`
        -   The field must not be edited at all. See the note below.
    *   -   :yaml:`readonly`
        -   The field is shown but cannot be written.
    *   -   :yaml:`email`
        -   The value must be a valid email address, and the field is rendered
            as an email input.
    *   -   :yaml:`number`
        -   The field is rendered as a number input. No additional server side
            validation is performed.

..  note::
    :yaml:`disabled` and :yaml:`readonly` both **cancel** :yaml:`required`. A
    field that cannot be edited cannot be demanded from the editor, so combining
    them has no effect — the field is simply locked.

    :yaml:`disabled` additionally implies :yaml:`readonly`. FormEngine has no
    equivalent of the HTML :html:`disabled` attribute, so a disabled field is
    presented as read only in the backend.

..  _configuration-validations-defaults:

Fields that are locked by default
=================================

Three profile fields ship as :yaml:`disabled`:

..  code-block:: yaml

    validations:
      profile:
        firstName:
          - disabled
          - required
        middleName:
          - disabled
        lastName:
          - disabled

This is intentional. Profile names are usually owned by the connected frontend
user record — commonly fed from a directory service such as LDAP or Active
Directory, and synchronised into the profile — so they must not be overwritten
from an editing form.

The consequences, which surprise people who did not expect them:

*   :guilabel:`First name`, :guilabel:`Middle name` and :guilabel:`Last name`
    are **read only in the backend** record editor, for every backend user.
*   The same three fields are rendered disabled in the frontend editing form,
    and a value submitted for them is discarded on the server.

The :yaml:`required` entry on :yaml:`firstName` has no effect, because
:yaml:`disabled` cancels it.

If the profile names are maintained in TYPO3 rather than synchronised from
elsewhere, remove those entries as described below.

Effects in the TYPO3 backend
============================

The settings are merged into the TCA of the matching table, so a locked field is
read only in the record editor and a required field is marked as such:

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Table
    *   -   :yaml:`profile`
        -   :sql:`tx_academicpersons_domain_model_profile`
    *   -   :yaml:`contract`
        -   :sql:`tx_academicpersons_domain_model_contract`
    *   -   :yaml:`emailAddress`
        -   :sql:`tx_academicpersons_domain_model_email`
    *   -   :yaml:`phoneNumber`
        -   :sql:`tx_academicpersons_domain_model_phone_number`
    *   -   :yaml:`physicalAddress`
        -   :sql:`tx_academicpersons_domain_model_address`
    *   -   :yaml:`profileInformation`
        -   :sql:`tx_academicpersons_domain_model_profile_information`

The property name is translated to the database column automatically:
:yaml:`firstName` addresses :sql:`first_name`.

Effects in the frontend editing plugin
======================================

When `EXT:academic_persons_edit
<https://extensions.typo3.org/extension/academic_persons_edit>`__ is installed,
the same configuration is used three times:

#.  The form field is rendered with the matching :html:`disabled`,
    :html:`readonly` and :html:`required` attributes.
#.  :yaml:`required` and :yaml:`email` add server side validation of the
    submitted form.
#.  A :yaml:`disabled` or :yaml:`readonly` property is **never written** to the
    record, whatever the request contains. This is deliberate: it protects
    already stored data, and it is what prevents a locked field from being
    emptied when a form is submitted.

..  _configuration-validations-override:

Overriding the settings
=======================

Settings are collected from **all installed extensions**. Every package that
contains :file:`Configuration/AcademicPersons/Settings.yaml` contributes, and
the package loaded last wins.

To change them for an installation:

#.  Add :file:`Configuration/AcademicPersons/Settings.yaml` to your site
    package.
#.  Make the site package **depend on** :guilabel:`academic_persons` in its
    :file:`composer.json` or :file:`ext_emconf.php`, so that it is loaded after
    it.
#.  Repeat the **complete** :yaml:`validations` block, see the warning below.
#.  Flush the TYPO3 caches.

..  warning::
    The files are merged on the top level only. :yaml:`validations` is a
    top-level key, so a site package that defines it replaces **all six sets**
    at once — the sets it does not repeat are lost, not inherited.

    Copy the whole :yaml:`validations` block from
    :file:`EXT:academic_persons/Configuration/AcademicPersons/Settings.yaml`
    and edit the copy. There is no syntax for removing a single flag from a
    single field.

Example — making the profile names editable again, in the backend and in the
frontend editing form. The other five sets are repeated unchanged and are
shortened here for readability:

..  code-block:: yaml

    validations:
      profile:
        # The three name fields are no longer listed and are therefore editable.
        website:
          - required
      contract:
        position:
          - required
      emailAddress:
        email:
          - required
          - email
      phoneNumber:
        phoneNumber:
          - required
      physicalAddress:
        street:
          - required
      profileInformation:
        title:
          - required

..  note::
    Because both editing contexts read the same configuration, an override
    always changes them together. Unlocking the profile names for the frontend
    editing form also makes those columns writable in the backend record editor.

There is no TypoScript and no site set equivalent for these settings.
