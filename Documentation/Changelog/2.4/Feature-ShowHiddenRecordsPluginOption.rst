.. _feature-ace-250-academic-persons:

===================================================================
Feature: "Show hidden records" plugin option for the person plugins
===================================================================

Description
===========

A new boolean plugin option **Show hidden records**
(:typoscript:`settings.showHiddenRecords`, a toggle, off by default) was added
to the following plugins:

* **Persons List** (:php:`academicpersons_list`)
* **Persons List and Detail** (:php:`academicpersons_listanddetail`)
* **Profile Card** (:php:`academicpersons_card`)
* **Profiles: Selected Profiles** (:php:`academicpersons_selectedprofiles`)
* **Profiles: Selected Contracts** (:php:`academicpersons_selectedcontracts`)
* **Persons Detail** (:php:`academicpersons_detail`)

The :guilabel:`Persons List`, :guilabel:`Persons List and Detail` and
:guilabel:`Profile Card` plugins share the core-version-aware :file:`List.xml`
flexform. :guilabel:`Profiles: Selected Profiles`,
:guilabel:`Profiles: Selected Contracts` and :guilabel:`Persons Detail` use
their own :file:`SelectedProfiles.xml`, :file:`SelectedContracts.xml` and
:file:`Detail.xml` flexforms. All are provided for both the TYPO3 v12 and v13
flexform data structures.

When the option is enabled, the affected frontend listing includes hidden
(disabled) records, also for visitors without a preview of hidden records. Only
the `hidden` enable column (`disabled`) is ignored. The `deleted`,
`starttime`/`endtime` and `fe_group` restrictions stay in effect.

The single-profile **Persons Detail** plugin resolves its profile through
Extbase argument mapping, which normally respects enable fields. When the
option is enabled, the detail view re-resolves the referenced profile including
hidden records, so a hidden profile can be displayed on its detail page.

Impact
======

Editors can now opt in per plugin instance to display hidden profiles or
contracts in the frontend, for example to preview intentionally hidden
records without changing the global preview settings. The option is off
by default, so existing plugin instances keep their current behaviour.

Affected Installations
======================

All installations using the `EXT:academic_persons` extension starting
with version 2.4. No action is required for existing installations.

.. index:: Backend, Frontend, ext:academic_persons
