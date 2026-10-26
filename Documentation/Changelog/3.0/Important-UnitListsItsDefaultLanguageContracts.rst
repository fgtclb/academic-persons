.. _important-ace-943-academic-persons:

=============================================================
Important: Units list their contracts of the default language
=============================================================

Description
===========

TYPO3 14.3.10 gives every inline child the language of its parent when the
parent is created, changes its language or gets a new child, see
`TYPO3 issue 103970 <https://forge.typo3.org/issues/103970>`__. The core
expects an inline child to have exactly one parent.

A contract has more than one. Its organisational unit is a select with
:php:`'l10n_mode' => 'exclude'`, so a translated contract points at the default
language record there, and the inline list
:php:`tx_academicpersons_domain_model_organisational_unit.contracts` of that
record held the translations as well. Adding a contract to that list on TYPO3
14.3.10 therefore turned every translated contract of the record into a
contract of the default language, and reset its translation parent and source.

The list holds the contracts of the default language only now, through
:php:`'foreign_match_fields' => ['sys_language_uid' => 0]`.

Impact
======

The backend form of the record lists the contracts of the default language
only. The translations keep their language and their translation parent when a
contract is added to the list, on TYPO3 13 as on TYPO3 14.

A contract of a profile that is valid in all languages is no longer listed on
its organisational unit either, and keeps its language as well. Its profile
lists it as before.

Affected Installations
======================

Installations on TYPO3 14.3.10 or later with translated contracts. Nothing has
to be done.

.. index:: Backend, TCA, ext:academic_persons
