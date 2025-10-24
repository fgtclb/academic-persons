.. include:: /Includes.rst.txt

.. _breaking-1746705800:

==========================
Breaking: Removed partials
==========================

Description
===========

Some partials got removed as the templating structure has changed.


Impact
======

Those partials include:

* `Resources/Private/Partials/List/AlphabetPagination.html`
* `Resources/Private/Partials/List/ListItem.html`
* `Resources/Private/Partials/List/Pagination.html`
* `Resources/Private/Partials/SelectedContracts/ListItem.html`


Affected Installations
======================

EXT:academic_partners installations overriding those partials.


Migration
=========

Adapt overrides accordingly to the new templating structure.

.. index:: Fluid, Frontend
