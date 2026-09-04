..  _important-public-profile-label-changes:

==============================================
Important: Label changes of the public profile
==============================================

Description
===========

The rebuilt public profile detail view (see
:ref:`feature-configurable-public-profile`) changes three things about the
labels of :file:`Resources/Private/Language/locallang.xlf`. All three are
relevant for an installation that ships its own translation of this file or
overrides single units through
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']`.

**A misspelled unit id is corrected.** The English file declared the unit

..  code-block:: xml

    <trans-unit id="contracts.emailAdresses">

with one ``d``, while the German file always declared
:xml:`contracts.emailAddresses`. The German target was therefore unreachable
and the English source was never rendered either: the label is looked up as
:php:`contracts.emailAddresses` by
:file:`Resources/Private/Partials/Profile/Contract/Field.html`, which
translates :html:`contracts.{fieldName}` for the fields a list or card plugin
was configured to show. The id is now spelled
:xml:`contracts.emailAddresses` in both files. **An override keyed on the old
id stops taking effect** and has to be re-keyed.

**Three units are added for the detail view:** :xml:`detail.subline`,
:xml:`detail.contact` and
:xml:`detail.navigation` - the subline of the shipped layout, the heading of
the contact block and the accessible name of the section navigation.

**Two units are no longer rendered by the shipped templates:**
:xml:`detail.contracts` and :xml:`detail.additionalInformation`. They were the
headings of the two static sections the old detail template had; the rebuilt
view has no equivalent, because its blocks are the configured elements and
each of them carries its own heading. Both units are kept in the file for
installations that reference them from their own templates.

Impact
======

Re-key an XLF override of :xml:`contracts.emailAdresses` to
:xml:`contracts.emailAddresses`. Nothing else has to be changed: the added
units ship with an English source and a German target, and the two unused ones
are still there.

Affected Installations
======================

Installations that translate or override
:file:`EXT:academic_persons/Resources/Private/Language/locallang.xlf`.

..  index:: Frontend, Backend, ext:academic_persons
