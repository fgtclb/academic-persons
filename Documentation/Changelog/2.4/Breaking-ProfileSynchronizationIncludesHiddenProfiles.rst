.. _breaking-1782285582:

========================================================================
Breaking: Profile synchronization now includes hidden profiles and users
========================================================================

Description
===========

The profile synchronization (:bash:`academic:updateprofiles`) previously
skipped frontend users that were disabled or whose profile was hidden. Such
records were neither selected by
:php:`\FGTCLB\AcademicPersons\Provider\FrontendUserProvider::getUsersWithProfileResult()`
(the automatic :sql:`hidden` restriction excluded them) nor resolved by
:php:`\FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository::findByFrontendUser()`.

The synchronization now processes hidden profiles and disabled frontend
users as well and keeps their data up to date, without changing their
visibility.

The same applies to the profile creation (:bash:`academic:createprofiles`):
:php:`\FGTCLB\AcademicPersons\Provider\FrontendUserProvider::getUsersWithoutProfileResult()`
now also returns disabled frontend users and
:php:`\FGTCLB\AcademicPersons\Profile\AbstractProfileFactory::createProfileForUser()`
resolves the frontend user ignoring its visibility, so a profile is
created for a disabled frontend user as well.

Impact
======

A hidden profile or a profile of a disabled frontend user that was
relying on being skipped by the synchronization is now updated again on
the next synchronization run. Its data (name, contact records and so on)
is overwritten with the current frontend user data, while the
:sql:`hidden` state itself is kept untouched.

A disabled frontend user without a profile now also receives a newly
created profile on the next :bash:`academic:createprofiles` run.

Only the deleted state is still respected. Both the frontend user
visibility (:sql:`fe_users.disable`) and the profile :sql:`hidden` field
are ignored for the profile synchronization and creation. The start and
end time are ignored as well, see :ref:`important-ace-667-academic-persons`.

Affected Installations
======================

All installations that run :bash:`academic:createprofiles` or
:bash:`academic:updateprofiles` and have manually hidden profiles or
disabled frontend users.

Migration
=========

If a profile should be excluded from the synchronization, set its
:guilabel:`Disable profile sync` checkbox (:sql:`skip_sync`) instead of
hiding the profile or disabling the frontend user. The checkbox sits next
to the visibility of the profile record in the backend. Hiding a profile
now only controls its frontend visibility, not whether it is synchronized.

.. index:: CLI, Database, PHP, NotScanned, ext:academic_persons
