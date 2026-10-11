.. _important-ace-341-academic-persons:

=================================================================
Important: Selected profiles are translated in free fallback mode
=================================================================

Description
===========

The :guilabel:`Persons List`, :guilabel:`Persons List and Detail` and
:guilabel:`Profile Card` content elements can show a hand-picked set of
profiles, chosen in :guilabel:`List of profiles`. The backend stores such a
selection as uids of the default language records, so the query that fetches it
does not restrict the language, otherwise nothing would match on a translated
page.

On a site language with :yaml:`fallbackType: free`, TYPO3 switches the overlay
of records off. The default language profiles the query returned were therefore
rendered as they were, and a German page listed the English profiles.

The query now switches the overlay on for such a selection before it drops the
language restriction, the way the :guilabel:`Profiles: Selected Profiles` and
:guilabel:`Profiles: Selected Contracts` content elements already did. A
selected profile is rendered in the language of the page.

Impact
======

On a site language with :yaml:`fallbackType: free`, the content elements above
show the translation of every selected profile that has one. TYPO3 v12 and v13
behave alike.

A selected profile without a translation is still shown in the default
language. Extbase on TYPO3 v12 and v13 keeps such records whatever the overlay
type says, the same core defect that keeps them on a site language with
:yaml:`fallbackType: strict`, see
:ref:`important-88886-strict-language-fallback-selected-profiles`. The other
fallback types are not affected by this change.

Affected Installations
======================

Multi-language installations with a site language that uses
:yaml:`fallbackType: free` and a :guilabel:`Persons List`,
:guilabel:`Persons List and Detail` or :guilabel:`Profile Card` element with a
selection in :guilabel:`List of profiles`.

.. index:: Frontend, ext:academic_persons
