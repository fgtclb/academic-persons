.. _feature-letter-navigation-active-letter-reset:

============================================================
Feature: The selected letter can link back to the whole list
============================================================

Description
===========

The selected letter of the letter navigation is marked as the current one and is
not a link. The new setting
:typoscript:`plugin.tx_academicpersons.alphabet.activeLetterResets` turns it
into a link back to the list without a letter - the target of
:guilabel:`A-Z` - while it stays marked as the current one. A visually hidden
"show all profiles", the new label `list.alphabetFilter.showAll`, tells
assistive technology where the link leads.

It is a site setting of the aggregate set `fgtclb/academic-persons` and a
constant of the shared static template, off by default in both. See
:ref:`configuration-letter-navigation`.

Impact
======

Nothing changes until the setting is switched on.

.. index:: Frontend, TypoScript, ext:academic_persons
