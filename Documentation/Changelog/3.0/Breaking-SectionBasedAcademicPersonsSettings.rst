..  _breaking-section-based-academic-persons-settings:

================================================
Breaking: Section-based AcademicPersons settings
================================================

Description
===========

:file:`Configuration/AcademicPersons/Settings.yaml` changes shape. The flat
schema of two top-level maps - :yaml:`profileInformationsTypes` listing the
seven timeline entry types, and :yaml:`validations` with one flag list per
record type - could say which fields are required or locked, and nothing
else. The editing frontend of :composer:`fgtclb/academic-persons-edit` needs
the order of the fields, the control each one is rendered with, its help
text, the rows and actions of a sortable list, and the character limit of a
rich text field. All of that is now declared in one place, in four top-level
maps: :yaml:`profile`, :yaml:`special`, :yaml:`contracts` and
:yaml:`documentSections`. The
:ref:`Profile sections <configuration-sections>` page documents the shape,
the :ref:`Validation settings <configuration-validations>` page the flags.

There is still **one file, one factory and one cache entry**. The public
detail layout - :yaml:`structure` and :yaml:`details` - lives in the same
:yaml:`profile` map as the editable fields, so an override of the layout
restates the fields with it. The backend TCA **does** consume the graph: five
TCA files of this extension merge the validation set of their own section,
exactly as they merged the flat sets before, and the sixth - the profile
information table, one table shared by the seven timeline types - merges a
:php:`types` fragment so a section's flags land in the
:php:`columnsOverrides` of its own record type. The normalised graph is cached in
the core cache under ``AcademicPersons_Settings_v3``, the identifier the move
of the validation primitives to `EXT:academic_base` introduced in the same
release; it is not changed a second time.

What an integrator sees:

*   :yaml:`profileInformationsTypes` is gone. The seven relations of a
    profile to its timeline entries (:sql:`scientific_research`, :sql:`vita`,
    :sql:`memberships`, :sql:`cooperation`, :sql:`publications`,
    :sql:`lectures`, :sql:`press_media`) are part of the domain model and are
    declared by the TCA file of the profile table. They used to be generated
    from the map, so an override that dropped an entry silently lost a backend
    column; they now exist whatever the settings say. The same seven appear as
    :yaml:`documentSections`, which carry their label, record type, relation
    field, row fields, actions and validators.
*   :yaml:`validations` is gone. The flags of a field are declared on the field:
    :yaml:`profile.<field>.validators` for the profile,
    :yaml:`contracts.fields.<field>.validators` for the contract,
    :yaml:`contracts.contactSections.<section>.fields.<field>.validators` for
    the address, email and phone records, and
    :yaml:`documentSections.<section>.validators.<field>` for the timeline
    entries. Every set keeps to its own section: a timeline section reaches
    the backend as ``columnsOverrides`` of its record type, never as a column
    configuration all seven types share.
*   The flag vocabulary grows by :yaml:`url`, :yaml:`tel`, :yaml:`textarea`
    and :yaml:`html`, and a rich text field can carry a :yaml:`characterLimit`.
    :yaml:`fieldType` and :yaml:`renderType` describe the frontend control
    only; **the TCA column keeps the type its TCA file declares**.
*   The shipped flags change, and both editing contexts apply that. **Newly
    required**: the profile's :yaml:`gender` - the profile TCA column gets
    ``required`` and ``minitems``, so the backend record editor refuses to
    save a profile without a gender, and the editing frontend runs its *not
    empty* validation - and the contract's :yaml:`validFrom`. **Newly
    validated**: :yaml:`website`, :yaml:`publicationsLink` and every
    timeline entry's :yaml:`link` carry the :yaml:`url` flag. **Relaxed**:
    the :yaml:`type` of an email address or phone number is no longer
    required, and the address's :yaml:`streetNumber` and :yaml:`zip` lose
    the :yaml:`number` flag - their columns return from the ``number`` TCA
    type the flag set to the ``input`` their TCA file declares, so a street
    number like ``12a`` is stored as entered instead of being cast to ``12``.
    Unchanged: the address's :yaml:`country`, and the :yaml:`title` and
    :yaml:`year` of every timeline entry were required before.
*   The timeline entry validators address the year columns: :yaml:`year` is
    the record's year, and the keys :yaml:`from` and :yaml:`to` alias its
    :yaml:`yearStart` and :yaml:`yearEnd` properties.
*   Every field of the shipped file carries a :yaml:`helptext`, and the label
    file of :guilabel:`academic_persons` gains the 38 :xml:`helptext.*`
    units they reference - the help of the profile, contract, contact and
    timeline fields, rendered by the editing frontend. A project replaces
    the text either by pointing :yaml:`helptext` at an :php:`LLL:` key of
    its own (or at literal text), or by overriding the shipped unit through
    :typoscript:`locallangXMLOverride`.

The internal PHP API changes with the file. :php:`AcademicPersonsSettings`
exposes the graph - :php:`profileSections`, :php:`specialFields`,
:php:`contractFields`, :php:`contractContactSections`,
:php:`documentSections` and :php:`publicProfile` - built from the new value
objects :php:`ProfileSection`, :php:`ProfileField`, :php:`SpecialField`,
:php:`ContractField`, :php:`ContractContactSection`,
:php:`ContractContactField`, :php:`DocumentSection` and
:php:`PublicProfileSettings`, and answers validation questions per section.
Removed without replacement:

..  list-table::
    :header-rows: 1

    *   -   Removed
        -   Instead
    *   -   :php:`FGTCLB\AcademicPersons\Settings\ProfileInformationType`
        -   :php:`DocumentSection`, resolved through
            :php:`AcademicPersonsSettings::getDocumentSection()` or
            :php:`getDocumentSectionByType()`
    *   -   :php:`AcademicPersonsSettings::getProfileInformationType()`
        -   :php:`getDocumentSection()`
    *   -   :php:`AcademicPersonsSettings::getValidationSet()`,
            :php:`getValidationSetWithFallback()`
        -   :php:`getProfileValidationSet()`,
            :php:`getProfileUpdateValidationSet()`,
            :php:`getContractContactValidationSet()` and
            :php:`getDocumentValidationSet()`, plus
            :php:`getProfileValidationSetForFields()` and
            :php:`getContractContactValidationSetForFields()` for a subset
            of a section's fields - every one of them returns an empty set
            for an unknown identifier, there is no separate fallback method
    *   -   :php:`AcademicPersonsSettings::$profileInformationTypes`,
            :php:`$validations`
        -   the graph properties above

All of it is :php:`@internal` and was consumed by `EXT:academic_persons_edit`
only, which is adapted.

Impact
======

**Every site package that overrides the file has to be migrated.** The old
maps are not read any more: a file that still declares :yaml:`validations` or
:yaml:`profileInformationsTypes` contributes nothing, and the installation runs
on the shipped defaults - locked name fields, the required contact fields, and
the seven shipped timeline sections.

An eighth timeline entry type that an override declared under
:yaml:`profileInformationsTypes` used to get a backend inline column for free.
It no longer does: the profile relations are fixed. Such a type can be kept by
declaring the column in a TCA override of the profile table and its section
under :yaml:`documentSections` - the TCA file's loop over the seven relations
is the template for the column. The Extbase model of this extension never had
a property for an additional type, so it was reachable in the backend only.

Code that reads the removed methods or the removed class fails with an
undefined method or a class not found error.

**Existing profiles without a gender can no longer be saved in the backend**
until a gender is chosen, and a frontend profile form that does not post one
is rejected. An installation that does not want the gender required removes
the flag in its override. Street numbers and zip codes accept non-numeric
values again; values already cast to integers stay as they are.

Affected Installations
======================

Every installation with a site package shipping
:file:`Configuration/AcademicPersons/Settings.yaml`, and every installation
whose project code reads :php:`AcademicPersonsSettings` directly.

Migration
=========

#.  Copy the shipped :file:`EXT:academic_persons/Configuration/AcademicPersons/Settings.yaml`
    over the override in the site package, and re-apply the project's changes
    to it: a locked or unlocked profile field is its :yaml:`validators` list
    under :yaml:`profile`, a required contact field its list under
    :yaml:`contracts.contactSections`, a required timeline field its entry
    under the :yaml:`validators` map of every section it applies to.
#.  Keep every map the override declares complete. The files are merged on
    the top level only, so a :yaml:`profile` map in the override replaces the
    shipped one - the layout keys and every field included.
#.  Decide on the changed defaults: drop :yaml:`required` from
    :yaml:`profile.gender.validators` if profiles without a gender are to stay
    saveable, restore :yaml:`number` on :yaml:`streetNumber` and :yaml:`zip`
    if numeric values are to be enforced, and add :yaml:`required` back to
    the two :yaml:`<section>Type` fields if a contact type is mandatory.
#.  Flush all TYPO3 caches.

..  index:: Configuration, TCA, Frontend, PHP-API, ext:academic_persons
