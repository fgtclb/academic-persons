.. _feature-letter-navigation-availability:

=================================================================
Feature: The letter navigation knows which letters lead somewhere
=================================================================

Description
===========

The letter navigation of the profile list and list-and-detail plugins offered
all 26 letters, whether or not a single profile would be listed under them.
The list action now tells its template which letters lead to a list that is not
empty: the new template variable `alphabetFilterLetters` maps each letter from
`a` to `z` to a boolean.

It is computed under exactly the constraints of the list itself - storage
folders, the function type and organisational unit filters of the content
element, the hidden records setting, the language and its fallback, enable
fields and workspace, and what listeners of :php:`ModifyProfileDemandEvent` and
:php:`ModifyProfileQueryEvent` add - but without the letter a visitor selected,
so the active letter never narrows the others. It takes one database statement,
and it is computed only while the content element has the navigation switched on
and no profiles are selected by hand.

The repository method behind it is public:
:php:`ProfileRepository::findAlphabetFilterLetters()`.

Impact
======

A project template can read `alphabetFilterLetters` to render a letter without
profiles differently.

:php:`ModifyProfileDemandEvent` and :php:`ModifyProfileQueryEvent` are
dispatched twice for a list that shows the navigation - once for the list, once
for its letters. A listener that narrows the list narrows the letters as long
as it answers both calls alike.

The letters do not reflect a listener of :php:`ModifyListProfilesEvent` that
replaces the result, and in a workspace preview a profile deleted or hidden only
in the workspace still makes its letter available - the same precision as the
list's pagination count. See :ref:`developers-letter-availability`.

.. index:: Frontend, Fluid, PHP-API, ext:academic_persons
