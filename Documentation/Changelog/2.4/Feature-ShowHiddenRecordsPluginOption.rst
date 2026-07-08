.. include:: /Includes.rst.txt

.. _feature-ace-250-academic-persons:

===================================================================
Feature: "Show hidden records" plugin option for the person plugins
===================================================================

Description
===========

A new boolean plugin option **Show hidden records**
(:typoscript:`settings.showHiddenRecords`, checkbox/toggle, default off)
was added to the following plugins:

* **List** (:php:`academicpersons_list`)
* **List and detail** (:php:`academicpersons_listanddetail`)
* **Card** (:php:`academicpersons_card`)
* **Selected profiles** (:php:`academicpersons_selectedprofiles`)
* **Selected contracts** (:php:`academicpersons_selectedcontracts`)

The `List`, `List and detail` and `Card` plugins share the
core-version-aware :file:`List.xml` flexform; `Selected profiles` and
`Selected contracts` use their own :file:`SelectedProfiles.xml` /
:file:`SelectedContracts.xml` flexforms. All are provided for both the
TYPO3 v12 and v13 flexform data structures.

When the option is enabled, the affected frontend listing includes hidden
(disabled) records, independent of the Context API visibility settings.
Only the `hidden` enable column (`disabled`) is ignored; the `deleted`,
`starttime`/`endtime` and `fe_group` restrictions stay in effect.

The single-profile **Detail** plugin (:php:`academicpersons_detail`) is
not affected by this option: it resolves its profile through Extbase
argument mapping, which is independent of the repository query settings.

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
