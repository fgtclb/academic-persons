..  _feature-phone-link-target-prefix:

========================================
Feature: A prefix for phone link targets
========================================

Description
===========

The site setting :typoscript:`plugin.tx_academicpersons.phoneNumbers.telPrefix`
is prepended to the :html:`tel:` target of every phone number this extension
renders — in the list, list and detail, card, selected profiles and selected
contracts elements, in the profile detail view, and in the contacts of a page
that :composer:`fgtclb/academic-contacts4pages` renders through the same
partials. The spaces of prefix and number alike are removed from the target,
so the result is dialable.

The visible link text is never touched: it stays the number as an editor
stored it, without the prefix.

The setting is empty by default, and with an empty prefix the target is the
stored number without its spaces.

..  code-block:: typoscript
    :caption: Constants

    plugin.tx_academicpersons.phoneNumbers.telPrefix = +49 6241 509

With that prefix, a phone number stored as ``123`` renders as

..  code-block:: html

    <a href="tel:+496241509123">123</a>

Impact
======

The prefix is meant for an installation that stores phone numbers as
extensions only, where every stored number is missing the same leading part.
It is applied unconditionally: an installation that mixes full numbers and
extensions would prefix its full numbers as well, and leaves the setting
empty.

The setting is declared with the site set `fgtclb/academic-persons` and as a
constant of the shared static template, with the same default in both — a site
configures it in :guilabel:`Site Settings` or in the :guilabel:`Constants` of
its TypoScript record, whichever mechanism it uses.

..  index:: Frontend, TypoScript, ext:academic_persons, ext:academic_contacts4pages
