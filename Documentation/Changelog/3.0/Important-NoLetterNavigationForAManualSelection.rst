.. _important-no-letter-navigation-for-a-manual-selection:

======================================================
Important: No letter navigation for a manual selection
======================================================

Description
===========

The profile list and list-and-detail plugins let an editor pick the profiles by
hand (:guilabel:`List of profiles`) and switch the letter navigation on at the
same time. A manual selection ignores the letter filter - the list shows the
selected profiles and nothing else - so every letter of the navigation led to
the same, complete selection.

The list template :file:`Templates/Profile/List.html` now renders the letter
navigation only when no profiles are selected by hand.

Impact
======

A list plugin with a manual selection renders no letter navigation, whether or
not :guilabel:`Alphabetical Pagination` is switched on in the content element. The
selected profiles are rendered as before.

Nothing changes for a list without a manual selection.

Affected Installations
======================

Every installation with a profile list or list-and-detail plugin that combines a
manual selection with the letter navigation. A project that overrides
:file:`Templates/Profile/List.html` keeps its own condition; to get the new
behaviour it adds :html:`&& !{demand.profileList}` to the condition around the
:file:`Profile/List/AlphabetPagination` partial.

.. index:: Frontend, Fluid, ext:academic_persons
