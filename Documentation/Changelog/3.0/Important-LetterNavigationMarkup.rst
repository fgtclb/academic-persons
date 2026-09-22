.. _important-letter-navigation-markup:

=========================================================================
Important: Letters without profiles are disabled in the letter navigation
=========================================================================

Description
===========

The letter navigation of the profile list and list-and-detail plugins linked
every letter from A to Z, so a visitor could land on a page that said no
profiles were found. The shipped partial
:file:`Partials/Profile/List/AlphabetPagination.html` now reads which letters
lead somewhere (see :ref:`feature-letter-navigation-availability`) and renders:

*   a letter with profiles as a link, as before;
*   a letter without profiles as :html:`li.page-item.disabled` with a
    :html:`span.page-link` and no link, plus a visually hidden "no profiles" for
    assistive technology;
*   the selected letter as :html:`li.page-item.active` with
    :html:`aria-current="page"`, not linked, as before;
*   :guilabel:`A-Z` as :html:`li.page-item.active` with
    :html:`aria-current="page"` while no letter is selected.

The :html:`<nav>` gets an :html:`aria-label` from the new label
`list.alphabetFilter.navigation`, and `list.alphabetFilter.noProfiles` is the
hidden text of a disabled letter, both in English and German.

Impact
======

A list with the letter navigation shows the letters without profiles greyed out
by Bootstrap's :html:`.disabled`, and they can neither be clicked nor reached
with the keyboard. :guilabel:`A-Z` is highlighted while no letter is selected.

A project stylesheet that styles :html:`.alphabetical-pagination .page-link` as
a link may need a rule for the new :html:`span.page-link` of a disabled letter.

A project that overrides :file:`Partials/Profile/List/AlphabetPagination.html`
keeps its markup and shows no availability until it reads
`alphabetFilterLetters`. A project that overrides
:file:`Templates/Profile/List.html` and renders the shipped partial with
`demand` alone gets every letter as a link, as before; to get the disabled
letters it passes `alphabetFilterLetters` along:

..  code-block:: html

    <f:render
        partial="Profile/List/AlphabetPagination"
        arguments="{demand: demand, alphabetFilterLetters: alphabetFilterLetters}"
    />

Affected Installations
======================

Every installation with a profile list or list-and-detail plugin that has the
letter navigation switched on.

.. index:: Frontend, Fluid, ext:academic_persons
