.. _feature-configurable-frontend-user-mapping:

==================================================
Feature: Configure what frontend users synchronise
==================================================

Description
===========

The synchronisation of :bash:`academic:createprofiles` and
:bash:`academic:updateprofiles` copied a fixed set of :sql:`fe_users` columns:
the names, the title, the website, one address, one e-mail address, telephone
and fax. Anything else - a mobile number, a position, a room - needed a
complete profile factory of its own.

The new top-level map :yaml:`frontendUserSync` of
:file:`Configuration/AcademicPersons/Settings.yaml` names the column behind
every synchronised property:

*   :yaml:`profile` and :yaml:`contract` map single properties, among them the
    contract's :yaml:`position` and :yaml:`room`.
*   :yaml:`physicalAddresses`, :yaml:`emailAddresses` and :yaml:`phoneNumbers`
    are lists; every entry becomes one record of the imported contract, and a
    phone number carries its :yaml:`type`.
*   A property mapped to ``''`` is not synchronised: the value an editor
    entered stays. A map without any source of the contract leaves the
    imported contract alone.

A site package changes the keys it names, like any other key of the file. A
custom profile factory can apply the same map through
:php:`\FGTCLB\AcademicPersons\Profile\FrontendUserProfileMapper` instead of
copying it. See :ref:`configuration-frontend-user-sync`.

Impact
======

Nothing changes for an installation without a map of its own: the shipped map
is the previous synchronisation, with the same import identifiers, so existing
records are matched as before and no migration is needed.

A mistake in a map of a site package makes the synchronisation throw the
exception ``1790142324`` before anything is written, and a column the frontend
user record does not have the exception ``1790142326``; the rest of the
installation is not affected.

.. index:: CLI, PHP-API, ext:academic_persons
