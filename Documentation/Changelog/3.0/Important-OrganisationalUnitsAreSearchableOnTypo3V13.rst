.. _important-organisational-units-are-searchable-on-typo3-v13:

===========================================================
Important: Organisational units are searchable on TYPO3 v13
===========================================================

Description
===========

On TYPO3 v13 the search of the list module and the backend search found no
organisational unit by its text, only by its uid. The TCA of
:sql:`tx_academicpersons_domain_model_organisational_unit` named
:sql:`function_name` as its search field, a column of the function type table
that the unit table does not have, and TYPO3 v13 drops a search field that does
not exist.

The search fields of the unit are now :sql:`unit_name` and :sql:`unique_name`.

The profile information table had the same defect in part: it named
:sql:`description` next to :sql:`title`, and its text is in :sql:`bodytext`. It
now searches :sql:`title` and :sql:`bodytext`.

Impact
======

On TYPO3 v13 a search for the name or the unique name of an organisational unit
lists the unit. A search in the profile information table, which the list
module shows only when page TSconfig sets
:typoscript:`mod.web_list.table.tx_academicpersons_domain_model_profile_information.hideTable = 0`,
also finds the words of its text. TYPO3 v14 searches every suitable field of a
table on its own and was not affected.

Affected Installations
======================

Every installation on TYPO3 v13 whose editors search for organisational units
in the backend, or for the text of profile information records that page
TSconfig shows in the list module.

.. index:: Backend, TCA, ext:academic_persons
