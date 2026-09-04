..  index:: Configuration; Sections
..  _configuration-sections:

================
Profile sections
================

:file:`Configuration/AcademicPersons/Settings.yaml` describes a profile in four
top-level maps. It ships with :guilabel:`academic_persons`, which owns the
records and their TCA, and it is the one file the backend record editor, the
public detail view and the editing frontend of `EXT:academic_persons_edit
<https://extensions.typo3.org/extension/academic_persons_edit>`__ read.

..  list-table::
    :header-rows: 1

    *   -   Map
        -   Describes
    *   -   :yaml:`profile`
        -   The public detail layout (:yaml:`structure` and :yaml:`details`),
            and every directly editable profile property with its section,
            control and validators.
    *   -   :yaml:`special`
        -   The components of the editing frontend that are not one property:
            the composed display name, the image and the synchronisation
            switch.
    *   -   :yaml:`contracts`
        -   The contract fields, and the address, email and phone sections a
            contract owns.
    *   -   :yaml:`documentSections`
        -   The sortable lists attached to a profile: the seven timeline entry
            types and the contracts.

The order of every map and list is preserved and is what the editing frontend
renders. The :ref:`validator flags <configuration-validations>` are documented
on their own page.

..  attention::
    The syntax of this file is still considered experimental and may change in
    a future release.

..  _configuration-sections-profile:

The profile map
===============

Two keys describe the public layout, everything else is a field:

..  code-block:: yaml

    profile:
      structure:
        left:
          - menuSections
        right:
          - headline
          - position
          - profileImage
          - contact
          - subline
          - profileEntries
          - menuSectionsDatas
      details:
        headline:
          - title
          - firstName
          - middleName
          - lastName
        subline: 'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:detail.subline'
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
        helptext: 'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:helptext.firstName'
      miscellaneous:
        section: aboutme
        fieldType: textarea
        renderType: ckeditor
        characterLimit: 1000
        validators:
          - html

:yaml:`structure`
    The layout columns and the ordered elements in each column. The shipped
    detail template renders ``left`` as the desktop navigation and ``right`` as
    the main content; on mobile, the ``left`` elements are inserted again
    directly before ``subline``.

:yaml:`details`
    Per element, the ordered profile properties, the relation map, the label
    reference or the special renderer it renders. Supported elements are
    :yaml:`menuSections`, :yaml:`headline`, :yaml:`position`,
    :yaml:`profileImage`, :yaml:`contact`, :yaml:`subline`,
    :yaml:`profileEntries` and :yaml:`menuSectionsDatas`; an unknown element
    renders nothing. :yaml:`position` and :yaml:`contact` take the special
    renderer :yaml:`special: datasFromContracts`. :yaml:`menuSections` lists
    stable navigation identifiers and :yaml:`menuSectionsDatas` maps each of
    them to the profile relation it shows.

Every other key is a field, and fields share one shape across
:yaml:`profile`, :yaml:`contracts.fields` and
:yaml:`contracts.contactSections.<section>.fields`:

..  list-table::
    :header-rows: 1

    *   -   Key
        -   Meaning
    *   -   :yaml:`section`
        -   Profile fields only. Fields with the same section are grouped and
            rendered together, in file order; the first field of a section
            decides where the section appears.
    *   -   :yaml:`propertyName`
        -   The domain and form data property, when it differs from the key.
            Optional.
    *   -   :yaml:`fieldName`
        -   The database column, when it differs from the underscored property
            name. Optional.
    *   -   :yaml:`fieldType`
        -   :yaml:`input`, :yaml:`select`, :yaml:`textarea` or :yaml:`check`.
            Describes the frontend control; **the TCA column keeps the type its
            TCA file declares**.
    *   -   :yaml:`renderType`
        -   The renderer of the editing frontend: :yaml:`text`, :yaml:`select`,
            :yaml:`checkbox`, :yaml:`email`, :yaml:`phone`, :yaml:`date`,
            :yaml:`combinedLink` or :yaml:`ckeditor`.
    *   -   :yaml:`validators`
        -   The :ref:`flag list <configuration-validations-flags>`.
    *   -   :yaml:`characterLimit`
        -   Rich text fields (:yaml:`renderType: ckeditor`) only: the maximum
            number of readable characters. Checked on the server, never copied
            into the TCA.
    *   -   :yaml:`helptext`
        -   A label reference or literal text rendered next to the control.
    *   -   :yaml:`autocomplete`
        -   Contract and contact fields only: an HTML ``autocomplete`` token.
    *   -   :yaml:`options`
        -   Contract selects only: :yaml:`organisationalUnits`,
            :yaml:`functionTypes` or :yaml:`locations`.

A field is dropped silently when it has no section (profile fields), no
:yaml:`fieldType` or no :yaml:`renderType`. Removing a field from the file
removes it from the editing frontend and from the validation; it never removes
a column or stored data.

..  _configuration-sections-special:

The special map
===============

..  code-block:: yaml

    special:
      title:
        type: special
        renderType: title
        fields:
          - title
          - firstName
          - middleName
          - lastName
      image:
        type: special
        renderType: cropper
      skipSync:
        type: special
        fieldType: check
        renderType: checkbox

:yaml:`title` composes the display name from the listed profile properties,
:yaml:`image` is the profile image and :yaml:`skipSync` the switch that keeps
a profile out of the synchronisation from its frontend user. A special entry
with a :yaml:`fieldType` and without composed :yaml:`fields` addresses one
profile column directly and takes part in the profile validation; the other
two do not.

..  _configuration-sections-contracts:

The contracts map
=================

..  code-block:: yaml

    contracts:
      label: 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.contracts.label'
      type: contracts
      fieldName: contracts
      rowFields:
        - position
      actions:
        - view
        - down
        - up
        - delete
        - edit
      fields:
        position:
          fieldType: input
          renderType: text
          validators:
            - required
        organisationalUnit:
          fieldType: select
          renderType: select
          options: organisationalUnits
        validFrom:
          fieldType: input
          renderType: date
          validators:
            - required
            - date
      contactSections:
        emailAddresses:
          fields:
            emailAddress:
              propertyName: email
              fieldName: email
              fieldType: input
              renderType: email
              autocomplete: email
              validators:
                - required
                - email
            emailAddressType:
              propertyName: type
              fieldName: type
              fieldType: select
              renderType: select

:yaml:`fields` are the contract fields in editor order. The three contact
sections - :yaml:`physicalAddresses`, :yaml:`emailAddresses` and
:yaml:`phoneNumbers` - each carry their own :yaml:`fields` map. Their keys
are unique across the file, which is why :yaml:`emailAddress` names the
``email`` property and column and each ``<section>Type`` key names the
``type`` property of its own record.

:yaml:`label`, :yaml:`type`, :yaml:`fieldName`, :yaml:`rowFields` and
:yaml:`actions` complete the :yaml:`contracts` entry of the document sections
below.

..  _configuration-sections-documents:

The document sections
=====================

..  code-block:: yaml

    documentSections:
      contracts:
        type: contracts
      publications:
        label: 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.publications.label'
        type: publication
        fieldName: publications
        rowFields:
          - year
          - title
        actions:
          - view
          - down
          - up
          - delete
          - edit
        validators:
          title:
            - required
          link:
            - url
          from:
            - number
          to:
            - number
          year:
            - required
            - number
          description:
            editor:
              limit: 500
              type: ckeditor

Each key is a stable section identifier; the map order is the display order.

..  list-table::
    :header-rows: 1

    *   -   Key
        -   Meaning
    *   -   :yaml:`label`
        -   The label reference of the section heading.
    *   -   :yaml:`type`
        -   The record type of the rows, i.e. the ``type`` of the profile
            information records. :yaml:`contracts` is the reserved value for
            the contract section, which takes everything it does not declare
            from the top-level :yaml:`contracts` map.
    *   -   :yaml:`fieldName`
        -   The profile relation the rows hang off.
    *   -   :yaml:`readonly`
        -   :yaml:`true` disables creation and every mutating action; the
            section still offers ``view``.
    *   -   :yaml:`rowFields`
        -   The values shown in a compact row, in order. Timeline entries
            support ``from``, ``to``, ``year``, ``title`` and ``description``;
            contracts support ``from``, ``to`` and ``position``.
    *   -   :yaml:`actions`
        -   The actions offered per row, in order: ``view``, ``down``, ``up``,
            ``delete`` and ``edit``. An action not listed is not available.
            Listing both ``up`` and ``down`` also enables drag sorting.
    *   -   :yaml:`validators`
        -   A map from field to flag list, or to a map with :yaml:`validators`,
            individual ``<flag>: true`` entries and an :yaml:`editor` block.
            :yaml:`editor.type: ckeditor` implies the ``html`` flag and takes a
            readable-text :yaml:`limit`; :yaml:`editor.type: textarea` implies
            ``textarea``. The contract section validates against
            :yaml:`contracts.fields` instead.
    *   -   :yaml:`helptext`
        -   A map from field to label reference.

The validators of a timeline section address the record type of that section
only: a required title of publications does not make the title of a lecture
required, neither in the editing frontend nor in the backend, where the flags
land in the ``columnsOverrides`` of that record type. The field keys ``from``,
``to`` and ``description`` are aliases of the ``yearStart``, ``yearEnd`` and
``bodytext`` properties (columns ``year_start``, ``year_end`` and
``bodytext``); ``year`` addresses the ``year`` property and column.

Unknown row fields and actions are discarded, as are duplicates; both lists
are matched without regard to case.

..  _configuration-sections-override:

Overriding the file
===================

The file is collected from **all installed extensions**: every package that
contains :file:`Configuration/AcademicPersons/Settings.yaml` contributes, and
the package loaded last wins. The files are merged on the **top level only** -
a site package that defines :yaml:`profile` replaces the shipped
:yaml:`profile` map completely, layout and fields alike, and the maps it does
not mention stay as shipped. There is no deep merge and no syntax for changing
a single flag of a single field.

To change the shipped configuration:

#.  Add :file:`Configuration/AcademicPersons/Settings.yaml` to your site
    package.
#.  Make the site package **depend on** :guilabel:`academic_persons` in its
    :file:`composer.json` or :file:`ext_emconf.php`, so that it is loaded after
    it.
#.  Copy the complete map you want to change from
    :file:`EXT:academic_persons/Configuration/AcademicPersons/Settings.yaml`
    and edit the copy.
#.  Flush the TYPO3 caches. The normalised graph is cached in the core cache.

There is no TypoScript and no site set equivalent for these settings.

..  note::
    The backend record editor reads the same maps. Unlocking a field for the
    editing frontend, or requiring one, changes the backend form of that record
    the same way.
