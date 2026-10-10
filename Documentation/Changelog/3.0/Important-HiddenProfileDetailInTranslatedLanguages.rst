.. _important-hidden-profile-detail-in-translated-languages:

=======================================================================
Important: The detail of a hidden profile works in translated languages
=======================================================================

Description
===========

With the option :guilabel:`Show hidden records`, the detail view of the
:guilabel:`Persons Detail` and the :guilabel:`Persons List and Detail` content
elements resolves the requested profile itself, hidden profiles included. A
link to a profile carries the uid of its default language record, and in a
translated site language that lookup found nothing, on TYPO3 v13 and v14 alike.
The detail view then resolved the profile as without the option: a visible
profile was still shown, a hidden one answered ``404``.

The lookup now finds the profile in every site language, as the detail view
finds a visible one.

Impact
======

In a translated site language, the detail view with
:guilabel:`Show hidden records` shows a hidden profile where it answered
``404``. A visible translation is shown on TYPO3 v13 and v14. A hidden
translation is shown on TYPO3 v14, TYPO3 v13 still shows the default language
record in its place. With the fallback types ``fallback`` and ``free``, a hidden
profile without a translation is shown in the default language, as a visible
one is.

Without the option nothing changes.

Affected Installations
======================

Every installation rendering the :guilabel:`Persons Detail` or the
:guilabel:`Persons List and Detail` content element with
:guilabel:`Show hidden records` on a site with more than one language.

.. index:: Frontend, ext:academic_persons
