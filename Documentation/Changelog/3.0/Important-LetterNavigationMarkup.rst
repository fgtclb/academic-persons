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

*   A letter with profiles as a link, as before.
*   A letter without profiles as :html:`li.ace-list-item.disabled` with a
    :html:`span.ace-link` and no link, plus a visually hidden "no profiles" for
    assistive technology.
*   The selected letter as :html:`li.ace-list-item.active` with
    :html:`aria-current="page"`, not linked unless the reset setting is on,
    see :ref:`configuration-letter-navigation`.
*   :guilabel:`A-Z` as :html:`li.ace-list-item.active` with
    :html:`aria-current="page"` while no letter is selected.

The classes are those of 3.0, see
:ref:`breaking-persons-speaking-frontend-classes`.

The :html:`<nav>` gets an :html:`aria-label` from the new label
`list.alphabetFilter.navigation`, and `list.alphabetFilter.noProfiles` is the
hidden text of a disabled letter, both in English and German.

Impact
======

A list with the letter navigation renders the letters without profiles with
the class :html:`disabled`, and they can neither be clicked nor reached with the
keyboard. :guilabel:`A-Z` carries :html:`active` while no letter is selected.
The site stylesheet greys out the one and highlights the other.

A project stylesheet that styles the links of the letter navigation,
:html:`.ace-alphabet-navigation .ace-link`, reaches the :html:`span.ace-link` of
a disabled letter as well, and may need a rule that sets it apart.

A project that overrides :file:`Partials/Profile/List/AlphabetPagination.html`
keeps its markup and shows no availability until it reads
`alphabetFilterLetters`. A project that overrides
:file:`Templates/Profile/List.html` and renders the shipped partial with
`demand` alone gets every letter as a link, as before; to get the disabled
letters it passes `alphabetFilterLetters` along - and `activeListArguments`,
which the links carry (see :ref:`feature-list-links-keep-state`):

..  code-block:: html

    <f:render
        partial="Profile/List/AlphabetPagination"
        arguments="{demand: demand, alphabetFilterLetters: alphabetFilterLetters, activeListArguments: activeListArguments}"
    />

Affected Installations
======================

Every installation with a profile list or list-and-detail plugin that has the
letter navigation switched on.

.. index:: Frontend, Fluid, ext:academic_persons
