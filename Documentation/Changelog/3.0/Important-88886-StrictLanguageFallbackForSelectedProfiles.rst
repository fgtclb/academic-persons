.. _important-88886-strict-language-fallback-selected-profiles:

=========================================================
Important: Strict language fallback for selected profiles
=========================================================

Description
===========

When profiles are selected explicitly (by uid) for a list plugin
(:php:`academicpersons_list` / :php:`academicpersons_listanddetail` with the
FlexForm *"selected profiles"* option), a site language configured with
:php:`fallbackType: strict` did not hide profiles that are not translated into
the requested language. Such profiles were rendered in their default language
instead of being removed, unless the plugin option *"fallback for non
translated"* was enabled on purpose.

This is **not** an :php:`EXT:academic_persons` bug. The extension already
resolves the correct language overlay type from the site configuration and only
relies on Extbase persistence to apply it. The behaviour is caused by a
long-standing Extbase regression that ignored the resolved language overlay type
and always overlaid single records with :php:`OVERLAYS_MIXED`, so untranslated
records were kept.

Core references (issue and both patches):

* Forge issue `#88886 <https://forge.typo3.org/issues/88886>`__ —
  *"DataMapper: Consider languageOverlayMode hideNonTranslated ..."*
* Gerrit change `66694 <https://review.typo3.org/c/Packages/TYPO3.CMS/+/66694>`__
  — *"[BUGFIX] Respect language overlay type in Extbase"* (TYPO3 main line)
* Gerrit change `94935 <https://review.typo3.org/c/Packages/TYPO3.CMS/+/94935>`__
  — the TYPO3 **14.3** backport (same Change-Id)

The fix is released with TYPO3 **v14.3.6** and newer (and on the TYPO3 main
development line). It is **not** part of TYPO3 **v13** and, being a behavioural
change, is not backported to the v13.4 LTS.

Impact
======

On **TYPO3 v14.3.6 and newer** the behaviour is correct out of the box:
untranslated selected profiles are removed under :php:`fallbackType: strict`. No
configuration or code change is required in :php:`EXT:academic_persons`.

On **TYPO3 v13.4** (and on **TYPO3 v14.3.0 - v14.3.5**, before the fix shipped)
the affected Extbase code still overlays with :php:`OVERLAYS_MIXED`, so
untranslated selected profiles keep being shown in their default language when the
site language uses :php:`fallbackType: strict` and the plugin fallback option is
not enabled.

The two functional tests covering this behaviour
(:php:`AcademicPersonsListPluginTest` and :php:`AcademicPersonsListAndDetailPluginTest`,
test :php:`...WithFallbackTypeStrictWhenNotAllProfilesAreLocalized`) are therefore
skipped on TYPO3 below v14.3.6 and run only where the core fix is present.

Affected Installations
======================

Installations that use selected profiles in a list plugin with a site language
configured as :php:`fallbackType: strict` and expect untranslated profiles to be
hidden, running on **TYPO3 v13.4** or **TYPO3 v14.3.0 - v14.3.5**.

Solution
========

Upgrade to **TYPO3 v14.3.6** or newer, which contains the core fix.

If the correct behaviour is required before that, apply the core change as a
composer patch against :php:`typo3/cms-extbase` until it is part of the installed
core version. Cleaned patches (narrowed to :php:`typo3/cms-extbase`, derived from
Gerrit changes 66694 / 94935) are shipped with this extension:

TYPO3 v13.4 patch
-----------------

:file:`Documentation/Patches/extbase-88886-respect-language-overlay-type-v13.patch`

..  literalinclude:: ../../Patches/extbase-88886-respect-language-overlay-type-v13.patch
    :language: diff
    :caption: extbase-88886-respect-language-overlay-type-v13.patch (TYPO3 v13.4)

TYPO3 v14.3 patch (v14.3.0 - v14.3.5)
-------------------------------------

:file:`Documentation/Patches/extbase-88886-respect-language-overlay-type-v14.patch`

..  literalinclude:: ../../Patches/extbase-88886-respect-language-overlay-type-v14.patch
    :language: diff
    :caption: extbase-88886-respect-language-overlay-type-v14.patch (TYPO3 v14.3.0 - v14.3.5)

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

Both patches touch only :php:`typo3/cms-extbase`
(:file:`Classes/Persistence/Generic/Storage/Typo3DbBackend.php` and
:file:`Classes/Persistence/Generic/Backend.php`); :php:`EXT:academic_persons`
itself needs no change.

.. index:: PHP, ext:academic_persons
