.. _important-content-element-titles-and-descriptions:

============================================================
Important: Content elements have new titles and descriptions
============================================================

Description
===========

The content elements of this extension have new titles and new descriptions, in
English and in German. They follow one wording that all academic extensions
share now. An editor sees the title in the new content element wizard and in
the type field of a content element, and the description below the title in the
wizard.

..  list-table::
    :header-rows: 1

    *   -   Content type
        -   Title until now
        -   Title now
        -   Description now
    *   -   :typoscript:`academicpersons_card`
        -   :guilabel:`Profile Card` (German :guilabel:`Profilkarte`)
        -   :guilabel:`Person Contact Card` (German :guilabel:`Personen Kontaktkarte`)
        -   Displays selected data for selected persons as a contact card.
    *   -   :typoscript:`academicpersons_detail`
        -   :guilabel:`Persons Detail` (German :guilabel:`Personen Detail`)
        -   :guilabel:`Person Details` (German :guilabel:`Personen Details`)
        -   Detailed view of persons.
    *   -   :typoscript:`academicpersons_list`
        -   :guilabel:`Persons List` (German :guilabel:`Personen Liste`)
        -   :guilabel:`Person List` (German :guilabel:`Personen Liste`)
        -   Lists persons in a directory.
    *   -   :typoscript:`academicpersons_listanddetail`
        -   :guilabel:`Persons List and Detail` (German :guilabel:`Personen Liste und Detail`)
        -   :guilabel:`Person List and Details` (German :guilabel:`Personen Liste und Details`)
        -   Lists persons and their details on a single page.
    *   -   :typoscript:`academicpersons_selectedcontracts`
        -   :guilabel:`Profiles: Selected Contracts` (German :guilabel:`Profile: Ausgewählte Verträge`)
        -   :guilabel:`Selected Person’s Contracts` (German :guilabel:`Personen Verträge selektiert`)
        -   Lists contracts for selected persons.
    *   -   :typoscript:`academicpersons_selectedprofiles`
        -   :guilabel:`Profiles: Selected Profiles` (German :guilabel:`Profile: Ausgewählte Profile`)
        -   :guilabel:`Selected Person’s Profiles` (German :guilabel:`Personen Profile selektiert`)
        -   Lists profiles of selected persons.

The type field and the wizard read the title from two different labels for
:typoscript:`academicpersons_detail`, :typoscript:`academicpersons_list` and
:typoscript:`academicpersons_listanddetail`. Both labels carry the new title.

Impact
======

Editors see the new titles and descriptions. The content types, the label keys
and their files did not change. A site that replaces a title or a description,
with page TSconfig of the wizard, with
:typoscript:`TCEFORM.tt_content.CType.altLabels` or with a language file
override, keeps its own text.

Affected Installations
======================

Every installation that offers a content element of this extension to its
editors. Nothing has to be migrated.

.. index:: Backend, TSConfig, ext:academic_persons
