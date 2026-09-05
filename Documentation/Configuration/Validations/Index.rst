..  index:: Configuration; Validations
..  _configuration-validations:

===================
Validation settings
===================

Every field of :file:`Configuration/AcademicPersons/Settings.yaml` carries a
list of **flags** that say whether it is required, read only or disabled, and
what kind of value it takes. One list drives **both editing contexts**:

*   the TYPO3 backend record editor (FormEngine), through generated TCA, and
*   the editing frontend of `EXT:academic_persons_edit
    <https://extensions.typo3.org/extension/academic_persons_edit>`__.

This is why the file ships with :guilabel:`academic_persons`, which owns the
records and their TCA, and not with the editing extension. The backend half
applies even when the editing extension is not installed. Where the fields
live - the :yaml:`profile`, :yaml:`special`, :yaml:`contracts` and
:yaml:`documentSections` maps - is documented on the
:ref:`Profile sections <configuration-sections>` page; this page is about the
flags.

..  attention::
    The syntax of this file is still considered experimental and may change in
    a future release.

..  _configuration-validations-where:

Where the flags are declared
============================

A profile, contract or contact field carries its flags as a list:

..  code-block:: yaml

    profile:
      gender:
        section: information
        fieldType: select
        renderType: select
        validators:
          - required
      firstName:
        section: information
        fieldType: input
        renderType: text
        validators:
          - readonly
          - disabled

A document section carries a map from field to flag list, with an expanded map
for a rich text field:

..  code-block:: yaml

    documentSections:
      publications:
        validators:
          title:
            - required
          year:
            - required
            - number
          link:
            - url
          description:
            editor:
              type: ckeditor
              limit: 500

A field without flags is unconfigured: it is editable, not required, and no
validator runs for it. A field that is not listed at all is not offered by the
editing frontend.

..  _configuration-validations-flags:

Available flags
===============

Flag names are matched case insensitively. Anything not listed here is kept in
the list and has no effect.

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
        -   The value must be a valid email address; the field is rendered as an
            email input and the TCA column becomes an ``email`` column.
    *   -   :yaml:`url`
        -   The value must be a valid URL; the field is rendered as a URL input.
            The TCA column is untouched.
    *   -   :yaml:`number`
        -   The field is rendered as a number input and the TCA column becomes a
            ``number`` column. No additional server side validation is
            performed.
    *   -   :yaml:`date`
        -   The field is rendered as a date input. The TCA column keeps its own
            ``datetime`` configuration.
    *   -   :yaml:`tel`
        -   The field is rendered as a telephone input. No phone number format
            is enforced, and the TCA column is untouched.
    *   -   :yaml:`textarea`
        -   The field is rendered as a multi line text control. The TCA column
            is untouched.
    *   -   :yaml:`html`
        -   The field is rich text: the editing frontend renders the rich text
            editor and sanitises the submitted markup. The TCA column is
            untouched.

Only :yaml:`required`, :yaml:`email` and :yaml:`url` run a validator on the
server; the other flags select the control and the input normalisation.
Validator class names and validator options cannot be put in the list.

..  note::
    :yaml:`disabled` and :yaml:`readonly` both **cancel** :yaml:`required`. A
    field that cannot be edited cannot be demanded from the editor, so combining
    them has no effect - the field is simply locked.

    :yaml:`disabled` additionally implies :yaml:`readonly`. FormEngine has no
    equivalent of the HTML :html:`disabled` attribute, so a disabled field is
    presented as read only in the backend.

..  _configuration-validations-limits:

Character limits
================

A rich text field may limit the number of **readable** characters - markup is
not counted. A profile or contract field declares it next to its render type,
a document field in its editor block:

..  code-block:: yaml

    profile:
      miscellaneous:
        section: aboutme
        fieldType: textarea
        renderType: ckeditor
        characterLimit: 1000
        validators:
          - html

    documentSections:
      publications:
        validators:
          description:
            editor:
              type: ckeditor
              limit: 500

The limit is effective only on a :yaml:`ckeditor` control; on any other control
it is ignored. It is checked on the server and shown by the editing frontend;
it is **never copied into the TCA**, because FormEngine's ``max`` would count
the markup.

..  _configuration-validations-defaults:

Fields that are locked by default
=================================

The three name fields ship as :yaml:`readonly` and :yaml:`disabled`:

..  code-block:: yaml

    profile:
      firstName:
        validators:
          - readonly
          - disabled
      middleName:
        validators:
          - readonly
          - disabled
      lastName:
        validators:
          - readonly
          - disabled

This is intentional. Profile names are usually owned by the connected frontend
user record - commonly fed from a directory service such as LDAP or Active
Directory, and synchronised into the profile - so they must not be overwritten
from an editing form.

The consequences, which surprise people who did not expect them:

*   :guilabel:`First name`, :guilabel:`Middle name` and :guilabel:`Last name`
    are **read only in the backend** record editor, for every backend user.
*   The same three fields are rendered locked in the editing frontend, and a
    value submitted for them is discarded on the server.

If the profile names are maintained in TYPO3 rather than synchronised from
elsewhere, remove the two flags as described below.

..  _configuration-validations-backend:

Effects in the TYPO3 backend
============================

The flags of every section are merged into the TCA of the matching table, so a
locked field is read only in the record editor and a required field is marked
as such:

..  list-table::
    :header-rows: 1

    *   -   Section
        -   Table
    *   -   :yaml:`profile` fields and :yaml:`special.skipSync`
        -   :sql:`tx_academicpersons_domain_model_profile`
    *   -   :yaml:`contracts.fields`
        -   :sql:`tx_academicpersons_domain_model_contract`
    *   -   :yaml:`contracts.contactSections.emailAddresses`
        -   :sql:`tx_academicpersons_domain_model_email`
    *   -   :yaml:`contracts.contactSections.phoneNumbers`
        -   :sql:`tx_academicpersons_domain_model_phone_number`
    *   -   :yaml:`contracts.contactSections.physicalAddresses`
        -   :sql:`tx_academicpersons_domain_model_address`
    *   -   every other :yaml:`documentSections` entry
        -   :sql:`tx_academicpersons_domain_model_profile_information`, as
            ``columnsOverrides`` of the record type of that section

The property name is translated to the database column automatically:
:yaml:`firstName` addresses :sql:`first_name`; a field that names a
:yaml:`fieldName` addresses that column instead.

The seven timeline sections share one table, so their flags apply to **their
record type only**: a required title of publications does not make the title
of a lecture required. The :yaml:`fieldType` and :yaml:`renderType` of a field
never reach the TCA - the column keeps the type its TCA file declares.

..  _configuration-validations-frontend:

Effects in the editing frontend
===============================

When `EXT:academic_persons_edit
<https://extensions.typo3.org/extension/academic_persons_edit>`__ is installed,
the same flags are used three times:

#.  The control is rendered with the matching :html:`disabled`,
    :html:`readonly` and :html:`required` attributes and the input type the
    flags select.
#.  :yaml:`required`, :yaml:`email` and :yaml:`url` add server side validation
    of the submitted data, and a character limit is enforced.
#.  A :yaml:`disabled` or :yaml:`readonly` property is **never written** to the
    record, whatever the request contains. This is deliberate: it protects
    already stored data, and it is what prevents a locked field from being
    emptied when a form is submitted.

Validation never falls back from one section to another: a contact record is
validated against its contact section, a timeline entry against the section of
its record type, and the profile against its profile sections.

..  _configuration-validations-override:

Overriding the flags
====================

The flags live in the map that carries the field, so changing them means
overriding that map - see :ref:`Overriding the file
<configuration-sections-override>`. The files are merged on the top level only:
a site package that defines :yaml:`profile` replaces the shipped
:yaml:`profile` map completely, layout and fields alike, and there is no syntax
for changing a single flag of a single field.

Example - making the profile names editable again, in the backend and in the
editing frontend. The shipped :yaml:`profile` map is repeated with the two
flags removed from the three name fields; the layout keys and the other fields
are copied unchanged and are shortened here for readability:

..  code-block:: yaml

    profile:
      structure:
        # ... as shipped
      details:
        # ... as shipped
      gender:
        section: information
        fieldType: select
        renderType: select
        validators:
          - required
      firstName:
        section: information
        fieldType: input
        renderType: text
      middleName:
        section: information
        fieldType: input
        renderType: text
      lastName:
        section: information
        fieldType: input
        renderType: text
      # ... the remaining fields as shipped

..  note::
    Because both editing contexts read the same configuration, an override
    always changes them together. Unlocking the profile names for the editing
    frontend also makes those columns writable in the backend record editor.

There is no TypoScript and no site set equivalent for these settings.

..  _configuration-validations-migration:

Migrating a pre-3.0 override
============================

Before 3.0 the file had two top-level maps, :yaml:`validations` with one flag
list per record type and :yaml:`profileInformationsTypes` with the seven
timeline entry types, and the manual told integrators to restate the complete
:yaml:`validations` block in the site package. Such a file keeps working after
the update: the two keys are mapped onto the section maps at runtime, before
the settings graph is built, and a warning naming the package and the key is
logged once per cache build. The mapping is transitional and is removed in
academic_persons 4.0, so the override should be rewritten.

The console command prints the replacement:

..  code-block:: bash

    vendor/bin/typo3 academic:persons:settings:migrate

For every active package that still ships a legacy key it prints the package,
the keys it found, one comment line per entry that could not be mapped, and
the four maps :yaml:`profile`, :yaml:`special`, :yaml:`contracts` and
:yaml:`documentSections` as the runtime mapping produces them - the complete
document that replaces the legacy keys in that package's file. It exits with
1 when such a package exists and with 0 otherwise, so a deployment pipeline
can run it as a check. The command never writes the file: the override lives
in a site package that is under version control and usually deployed read
only, so the printed maps are pasted into the file after review, and the
TYPO3 caches are flushed afterwards. When EXT:reports is installed, the
:guilabel:`Status` report lists the same packages under
:guilabel:`Academic Persons` as a warning.

How the legacy keys map:

..  list-table::
    :header-rows: 1

    *   -   Legacy key
        -   Mapped onto
    *   -   :yaml:`validations.profile.<property>`
        -   :yaml:`profile.<field>.validators`
    *   -   :yaml:`validations.contract.<property>`
        -   :yaml:`contracts.fields.<field>.validators`
    *   -   :yaml:`validations.emailAddress.{email, type}`
        -   :yaml:`contracts.contactSections.emailAddresses.fields.{emailAddress, emailAddressType}.validators`
    *   -   :yaml:`validations.phoneNumber.{phoneNumber, type}`
        -   :yaml:`contracts.contactSections.phoneNumbers.fields.{phoneNumber, phoneNumberType}.validators`
    *   -   :yaml:`validations.physicalAddress.<property>`
        -   :yaml:`contracts.contactSections.physicalAddresses.fields.<field>.validators`,
            :yaml:`type` onto :yaml:`physicalAddressType`
    *   -   :yaml:`validations.profileInformation.<property>`
        -   :yaml:`documentSections.<section>.validators.<field>` of every
            timeline section; :yaml:`yearStart`, :yaml:`yearEnd` and
            :yaml:`bodytext` onto :yaml:`from`, :yaml:`to` and
            :yaml:`description`
    *   -   :yaml:`profileInformationsTypes.<section>`
        -   the :yaml:`label` of :yaml:`documentSections.<section>`; its
            :yaml:`type` and :yaml:`fieldName` are reported, not applied

A field is matched by its key or by the property it names, so
:yaml:`emailAddress.email` reaches the :yaml:`emailAddress` field whose
:yaml:`propertyName` is :yaml:`email`. A legacy set decides the five flags the
old shape knew - :yaml:`required`, :yaml:`readonly`, :yaml:`disabled`,
:yaml:`email` and :yaml:`number` - for **every** field of its target: a field
the set does not list has none of them, exactly as an unlisted property was
unconfigured before, which is what made the 2.x example above unlock the
profile names by not listing them. The flags the old shape could not express
- :yaml:`url`, :yaml:`date`, :yaml:`tel`, :yaml:`textarea`, :yaml:`html` -
stay as the section maps declare them.

Two things are not mapped and are reported by the command and in the log:

*   A property the section maps do not know, and an eighth timeline entry
    type declared under :yaml:`profileInformationsTypes`, are skipped. The
    type needs a profile relation and a TCA column the settings never
    created; the Breaking entry on the section based settings describes how
    to keep one.
*   The :yaml:`type` and :yaml:`fieldName` of a timeline entry type are not
    applied. Until 2.4 the two generated the inline column of the profile
    table, so overriding one moved the backend relation and the frontend
    selection together; since 3.0.0 the seven relations are declared by the
    TCA of the profile table, and applying the override would move the
    frontend half alone - records created in the editing frontend would be
    invisible in the backend, and the other way round. The section keeps the
    record type and the relation field that match the TCA, and the value the
    override named is printed as a note. Act on that note: a timeline type of
    your own needs its own column in a TCA override of the profile table, as
    :ref:`configuration-sections-documents` and the :ref:`upgrade` page
    describe.
