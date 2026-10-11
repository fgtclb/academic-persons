.. _important-88886-academic-persons-fixed-on-v14:

============================================================================
Important: Strict language fallback for selected profiles works on TYPO3 v14
============================================================================

Description
===========

Under :yaml:`fallbackType: strict`, profiles selected by uid that are not
translated into the requested language are shown in their default language
instead of being hidden. The cause is the Extbase defect `#88886
<https://forge.typo3.org/issues/88886>`__, see
:ref:`important-88886-strict-language-fallback-selected-profiles`.

The core fix is part of TYPO3 v14.3.6 and newer (Gerrit change `94935
<https://review.typo3.org/c/Packages/TYPO3.CMS/+/94935>`__). This version of
the extension requires TYPO3 v14.3.7 or newer on v14, so every supported v14
installation hides such profiles without a patch.

TYPO3 v13.4 does not contain the fix. There the patch for v13.4 that the 2.4
entry ships and describes still applies.

Impact
======

On TYPO3 v14, untranslated selected profiles are hidden under
:yaml:`fallbackType: strict`, unless the plugin option
:guilabel:`Fallback to default language for non translated profiles` is
enabled. An installation that applied the patch on TYPO3 v13 drops it from its
:file:`composer.json` when it moves to TYPO3 v14.

Affected Installations
======================

Installations that use selected profiles in a list plugin with a site language
configured as :yaml:`fallbackType: strict`.

.. index:: PHP, ext:academic_persons
