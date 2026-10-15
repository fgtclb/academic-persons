..  _important-public-profile-label-changes:

==============================================
Important: Label changes of the public profile
==============================================

Description
===========

The rebuilt public profile detail view (see
:ref:`feature-configurable-public-profile`) changes two things about the
labels of :file:`Resources/Private/Language/locallang.xlf`. Both are relevant
for an installation that ships its own translation of this file or overrides
single units through
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` on TYPO3 v13,
:php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` on TYPO3 v14, or
:typoscript:`_LOCAL_LANG`.

**Three units are added for the detail view:** :xml:`detail.subline`,
:xml:`detail.contact` and
:xml:`detail.navigation`, the subline of the shipped layout, the heading of
the contact block and the accessible name of the section navigation.

**Two units are no longer rendered by the shipped templates:**
:xml:`detail.contracts` and :xml:`detail.additionalInformation`. They were the
headings of the two static sections the old detail template had. The rebuilt
view has no equivalent, because its blocks are the configured elements and
each of them carries its own heading. Both units are kept in the file for
installations that reference them from their own templates.

Impact
======

Nothing has to be changed: the added units ship with an English source and a
German target, and the two unused ones are still there.

An installation that comes from 2.3 also gets the corrected unit id
:xml:`contracts.emailAddresses` of the contract e-mail label, which 2.4
shipped, see :ref:`important-contract-email-label-unit-id`.

Affected Installations
======================

Installations that translate or override
:file:`EXT:academic_persons/Resources/Private/Language/locallang.xlf`.

..  index:: Frontend, Backend, ext:academic_persons
