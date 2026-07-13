.. _breaking-1782285582:

========================================================================
Breaking: Profile synchronization now includes hidden profiles and users
========================================================================

Description
===========

The profile synchronization (profile update command) previously skipped
frontend users that were disabled or whose profile was hidden. Such
records were neither selected by
:php:`\FGTCLB\AcademicPersons\Provider\FrontendUserProvider::getUsersWithProfileResult()`
(the automatic `hidden` restriction excluded them) nor resolved by
:php:`\FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository::findByFrontendUser()`.

The synchronization now processes hidden profiles and disabled frontend
users as well and keeps their data up to date, without changing their
visibility.

The same applies to the profile creation (profile create command):
:php:`\FGTCLB\AcademicPersons\Provider\FrontendUserProvider::getUsersWithoutProfileResult()`
now also returns disabled frontend users and
:php:`\FGTCLB\AcademicPersons\Profile\AbstractProfileFactory::createProfileForUser()`
resolves the frontend user ignoring its visibility, so a profile is
created for a disabled frontend user as well.

Impact
======

A hidden profile or a profile of a disabled frontend user that was
relying on being skipped by the synchronization is now updated again on
the next synchronization run. Its data (name, contact records, ...) is
overwritten with the current frontend user data, while the `hidden`
state itself is kept untouched.

A disabled frontend user without a profile now also receives a newly
created profile on the next profile create run.

Only the deleted state is still respected; both the frontend user
visibility (`fe_users.disable`) and the profile `hidden` field are
ignored for the profile synchronization and creation.

Affected Installations
======================

All installations using the profile synchronization
(`EXT:academic_persons` create/update profile commands) together with
manually hidden profiles or disabled frontend users.

Migration
=========

If a profile should be excluded from the synchronization, use the
dedicated `skip_sync` flag of the profile instead of hiding the profile
or disabling the frontend user. Hiding a profile now only controls its
frontend visibility, not whether it is synchronized.

.. index:: CLI, Database, PHP, NotScanned
