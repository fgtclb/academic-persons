.. _important-88886-strict-language-fallback-selected-profiles:

=========================================================
Important: Strict language fallback for selected profiles
=========================================================

Description
===========

When profiles are selected explicitly (by uid) in the
:guilabel:`List of profiles` field of the :guilabel:`Persons List` or
:guilabel:`Persons List and Detail` content element, a site language configured
with :yaml:`fallbackType: strict` does not hide profiles that are not
translated into the requested language. Such profiles are rendered in their
default language instead of being removed, even when the plugin option
:guilabel:`Fallback to default language for non translated profiles` is not
enabled.

This is **not** a defect of `EXT:academic_persons`. The extension already
resolves the correct language overlay type from the site configuration and only
relies on Extbase persistence to apply it. The behaviour is caused by a
long-standing Extbase regression that ignored the resolved language overlay type
and always overlaid single records with :php:`OVERLAYS_MIXED`, so untranslated
records were kept.

Core references (issue and both patches):

* Forge issue `#88886 <https://forge.typo3.org/issues/88886>`__:
  *"DataMapper: Consider languageOverlayMode hideNonTranslated ..."*
* Gerrit change `66694 <https://review.typo3.org/c/Packages/TYPO3.CMS/+/66694>`__:
  *"[BUGFIX] Respect language overlay type in Extbase"* (TYPO3 main line)
* Gerrit change `94935 <https://review.typo3.org/c/Packages/TYPO3.CMS/+/94935>`__:
  the TYPO3 **14.3** backport (same Change-Id)

The fix is released with TYPO3 **v14.3.6** and newer. It is **not** part of
TYPO3 **v12** or **v13** and, being a behavioural change, is not backported to
those LTS lines. This version of the extension supports TYPO3 v12 and v13, so
none of its supported core versions contain the fix out of the box.

Impact
======

On the supported TYPO3 **v12** and **v13** core versions the affected Extbase
code still overlays with :php:`OVERLAYS_MIXED`, so untranslated selected profiles
keep being shown in their default language when the site language uses
:yaml:`fallbackType: strict` and the plugin fallback option is not enabled.

Affected Installations
======================

Installations on TYPO3 **v12** or **v13** that use selected profiles in a list
plugin with a site language configured as :yaml:`fallbackType: strict` and expect
untranslated profiles to be hidden.

Solution
========

The corrected behaviour ships with TYPO3 **v14.3.6** and newer, which this
version of the extension does not support.

If the correct behaviour is required on TYPO3 **v12** or **v13**, apply the
core change as a composer patch against :composer:`typo3/cms-extbase` until
the installation is upgraded to a core version that contains the fix. Cleaned
patches (narrowed to :composer:`typo3/cms-extbase`, derived from Gerrit changes
66694 / 94935) are shipped with this extension and were verified to make the
strict-fallback behaviour correct on the respective core version:

TYPO3 v13.4 patch
-----------------

:file:`Documentation/Patches/extbase-88886-respect-language-overlay-type-v13.patch`

..  literalinclude:: ../../Patches/extbase-88886-respect-language-overlay-type-v13.patch
    :language: diff
    :caption: extbase-88886-respect-language-overlay-type-v13.patch (TYPO3 v13.4)

TYPO3 v12.4 patch
-----------------

:file:`Documentation/Patches/extbase-88886-respect-language-overlay-type-v12.patch`

..  literalinclude:: ../../Patches/extbase-88886-respect-language-overlay-type-v12.patch
    :language: diff
    :caption: extbase-88886-respect-language-overlay-type-v12.patch (TYPO3 v12.4)

Apply the matching patch with the composer plugin
`cweagans/composer-patches <https://github.com/cweagans/composer-patches>`__
(see its README for installation and usage). Copy the patch into the project
(for example into a :file:`patches/` directory) and reference it:

..  code-block:: json

    {
        "require": {
            "cweagans/composer-patches": "^1.7"
        },
        "extra": {
            "patches": {
                "typo3/cms-extbase": {
                    "Respect language overlay type in Extbase (forge #88886, review 66694)": "patches/extbase-88886-respect-language-overlay-type-v13.patch"
                }
            }
        }
    }

Both patches touch only :composer:`typo3/cms-extbase`
(:file:`Classes/Persistence/Generic/Storage/Typo3DbBackend.php` and
:file:`Classes/Persistence/Generic/Backend.php`). `EXT:academic_persons`
itself needs no change. Use the patch that matches the installed core version,
the composer example above names the one for TYPO3 v13.4.

.. index:: PHP, ext:academic_persons
