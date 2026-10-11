.. _important-ace-828-academic-persons:

====================================================
Important: Changed profiles clear their cached pages
====================================================

Description
===========

The profile list tags its cached page with `profile_list_view`, the profile
detail with `profile_detail_view_<uid>`. The uid is the one of the profile in
the default language, for a translated profile as well. Until now these tags
were cleared only when a profile was saved in the backend. In three cases a
page kept showing the old state until its cache expired or was cleared by hand:

*   A profile created in the backend, a translation of a profile included,
    cleared no tag. A new profile was missing from the cached lists.
*   A translation saved in the backend cleared the detail tag of its own uid,
    which no page carries, instead of the one of its default language profile.
*   A record written through Extbase rather than through the DataHandler
    cleared no tag. That is every change made with the profile editor of
    `EXT:academic_persons_edit`, every profile written by
    :bash:`academic:createprofiles` and :bash:`academic:updateprofiles`, and
    every write of own code through the repositories of this extension.

A profile created or saved in the backend now clears the list tag and its
detail tag, and a translation clears the detail tag of its default language
profile as well. A listener of the Extbase persistence events clears the same
tags whenever a profile, a contract, a profile information, a physical address,
an email address, a phone number or a file reference of a profile is added,
changed or removed through Extbase. Other records are left alone.

Impact
======

Pages with the :guilabel:`Persons List`, :guilabel:`Persons Detail` or
:guilabel:`Persons List and Detail` plugin show a change as soon as it is
saved, wherever it was made.

The :guilabel:`Profile Card`, :guilabel:`Profiles: Selected Profiles` and
:guilabel:`Profiles: Selected Contracts` plugins set none of these tags, and
this change does not clear their pages.

A command or an import that writes many of these records through Extbase
clears the tags once per written record. The pages carrying them are rendered
again on their next request.

Affected Installations
======================

Every installation that renders the profile list or detail on cached pages.
Nothing has to be done on update. A scheduler task, a short cache lifetime or
a :typoscript:`TCEMAIN.clearCacheCmd` that was set up only to work around stale
profile lists or detail pages can be removed.

.. index:: Frontend, PHP-API, ext:academic_persons
