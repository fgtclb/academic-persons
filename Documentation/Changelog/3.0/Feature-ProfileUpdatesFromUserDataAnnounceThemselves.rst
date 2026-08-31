.. _feature-profile-updates-from-user-data-announce-themselves:

===========================================================
Feature: Profile updates from user data announce themselves
===========================================================

Description
===========

:php:`AbstractProfileFactory::updateProfileForUser()` — the path behind the
:bash:`academic:updateprofiles` command — persisted its changes without
dispatching :php:`AfterProfileUpdateEvent`; only the profile creation did. A
profile updated from its frontend user record therefore changed without its
translations being synchronised and without its slug being regenerated, while
the same change made through the frontend editing plugins did both.

The update path now dispatches the event per profile the update ran through,
after :php:`persistAll()`, carrying the persisted default language profile —
the same contract as the creation path and the frontend editing flow, which
both announce a profile even when every value already matched.

The :sql:`skip_sync` flag gates the whole update per profile now: a profile
carrying it is neither data-updated nor announced. Previously the flag was
only evaluated per frontend user, so a user carrying a second, synchronisable
profile had the :sql:`skip_sync` profile updated through that side door.

Impact
======

An :bash:`academic:updateprofiles` run now triggers the registered listeners
for every synchronisable profile of the selected users. With :php:`academic_persons_edit` installed that
means: profile slugs are regenerated, and — with
``profile.allowedLanguages`` configured — the profile translations are
created or synchronised. Installations relying on the command *not* touching
slugs or translations should review their listener configuration before
updating.

Affected Installations
======================

Every installation using the :bash:`academic:updateprofiles` command, and any
installation with a frontend user connected to several profiles of which some
carry :sql:`skip_sync`.

.. index:: CLI, PHP-API, ext:academic_persons
