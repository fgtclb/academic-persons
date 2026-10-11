.. _feature-1782285580:

====================================
Feature: Synchronize hidden profiles
====================================

Description
===========

The profile synchronization (:bash:`academic:updateprofiles`,
:php:`\FGTCLB\AcademicPersons\Service\ProfileUpdateCommandService`) now
also keeps **hidden profiles** and profiles of **disabled frontend
users** up to date. Previously a frontend user that was disabled or whose
profile was hidden was excluded from the synchronization completely, so
the profile was never updated and no synchronization events were
dispatched for it.

The :php:`\FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository`
method :php:`findByFrontendUser()` gained an optional argument to include
hidden profiles:

* :php:`findByFrontendUser(int $frontendUserUid, bool $showHidden = false): QueryResultInterface`

The synchronization calls it with :php:`$showHidden = true`, while the
frontend display keeps the default (:php:`$showHidden = false`) and
therefore continues to respect the visibility.

The visibility itself is never changed by the synchronization. That stays
the responsibility of the
:php:`\FGTCLB\AcademicPersons\Profile\ProfileFactoryInterface`
implementation, so a manually hidden profile stays hidden while its data
is kept in sync.

The profile creation (:bash:`academic:createprofiles`) keeps skipping
frontend users that already have a profile, including a hidden one, so no
duplicate profiles are created for them. It now also creates a profile for a **disabled frontend
user** that does not have one yet:
:php:`\FGTCLB\AcademicPersons\Provider\FrontendUserProvider::getUsersWithoutProfileResult()`
returns disabled frontend users and
:php:`\FGTCLB\AcademicPersons\Profile\AbstractProfileFactory::createProfileForUser()`
resolves the frontend user ignoring its visibility.

..  note::

    This changes the behaviour of the profile synchronization and is
    therefore breaking. See :ref:`breaking-1782285582` for details and
    the migration.

Impact
======

Hidden profiles and profiles of disabled frontend users are no longer
silently excluded from the synchronization. To exclude a profile from
synchronization, use its :guilabel:`Disable profile sync` checkbox
(:sql:`skip_sync`) instead of hiding it or disabling the frontend user.

Affected Installations
======================

All installations that run :bash:`academic:createprofiles` or
:bash:`academic:updateprofiles`.

.. index:: CLI, Database, PHP, Frontend, ext:academic_persons
