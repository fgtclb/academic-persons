.. _important-ace-857-academic-persons:

==========================================================
Important: Hidden profiles in lists show their translation
==========================================================

Description
===========

With the plugin option :guilabel:`Show hidden records`, a hidden profile is
listed by the following plugins:

* **List** (:php:`academicpersons_list`)
* **List and detail** (:php:`academicpersons_listanddetail`)
* **Card** (:php:`academicpersons_card`)
* **Selected profiles** (:php:`academicpersons_selectedprofiles`)

In version 2.4 these plugins lifted the hidden flag through the query settings
only. Extbase looks up the translation of a record through the page repository
of TYPO3, which on TYPO3 v13 follows the visibility settings of the context, so
the hidden translation was not found. On a translated page a hidden profile was
listed with the values of its default language record.

The profiles are now fetched with the visibility lifted for the lookup of the
translation as well, and a hidden profile is listed with its translation, under
both fallback types :yaml:`strict` and :yaml:`fallback`. Only that lookup sees
hidden records, the relations of a profile keep their own visibility. TYPO3 v14
finds the hidden translation on its own.

Impact
======

On a translated page, a hidden profile in one of the lists above shows its
translation where it showed its default language values. Without the option
nothing changes.

Affected Installations
======================

Installations on TYPO3 v13 with translated pages that enable
:guilabel:`Show hidden records` on one of the plugins above.

.. index:: Frontend, ext:academic_persons
