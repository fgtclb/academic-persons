..  _important-contract-email-label-unit-id:

=====================================================
Important: The contract e-mail label shows in English
=====================================================

Description
===========

:file:`Resources/Private/Partials/Profile/Contract/Field.html` translates
:xml:`contracts.<field>` for every contract field an editor ticks in
:guilabel:`Show fields`. The English
:file:`Resources/Private/Language/locallang.xlf` declared the unit of the
e-mail addresses with one ``d``, as :xml:`contracts.emailAdresses`, so in
English the e-mail row of a list, card, selected profiles or selected
contracts element and of the contacts element of
:composer:`fgtclb/academic-contacts4pages` rendered an empty label in front of
the addresses. The German file always declared
:xml:`contracts.emailAddresses`.

The English unit id is now :xml:`contracts.emailAddresses` as well, and the
row reads :guilabel:`E-Mail` in English.

Impact
======

A translation of :file:`locallang.xlf` into a further language that copied
the misspelled id has to rename its unit to :xml:`contracts.emailAddresses`,
otherwise the English label is shown in its place. A label override is only
affected when it was keyed on the misspelled id, which no shipped template
ever read.

Affected Installations
======================

Installations that display the e-mail addresses of a contract in a profile
list, card or selection element, or in a contacts element, in English or in a
language of their own translation.

..  index:: Frontend, ext:academic_persons
