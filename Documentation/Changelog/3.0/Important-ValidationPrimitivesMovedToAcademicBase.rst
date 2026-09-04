..  _important-validation-primitives-moved-to-academic-base:

===========================================================
Important: Validation primitives moved to EXT:academic_base
===========================================================

Description
===========

The value objects and the ViewHelper behind
:file:`Configuration/AcademicPersons/Settings.yaml` moved to
`EXT:academic_base`, together with the loading, the flag normalisation and the
TCA merge that :php:`AcademicPersonsSettingsFactory` and
:php:`AcademicPersonsSettings` carried inline:

..  list-table::
    :header-rows: 1

    *   -   Before
        -   Now
    *   -   :php:`FGTCLB\AcademicPersons\Settings\Validation`
        -   :php:`FGTCLB\AcademicBase\Settings\Validation`
    *   -   :php:`FGTCLB\AcademicPersons\Settings\ValidationSet`
        -   :php:`FGTCLB\AcademicBase\Settings\ValidationSet`
    *   -   :php:`FGTCLB\AcademicPersons\ViewHelpers\ValidationEnsureViewHelper`
        -   :php:`FGTCLB\AcademicBase\ViewHelpers\ValidationEnsureViewHelper`
    *   -   :php:`AcademicPersonsSettings::getValidationTcaTableConfig()`
        -   :php:`FGTCLB\AcademicBase\Settings\TcaValidationMerger::merge()`,
            applied by five of the six TCA files of this extension; the
            profile information table merges a :php:`types` fragment built
            from the same value objects instead

All of them are :php:`@internal`. **No class aliases are registered** for the
old names: the classes never were public API, and no extension of this set
referenced them from outside `EXT:academic_persons` and
`EXT:academic_persons_edit`, both of which are adapted.

Nothing changes for the YAML file itself - its location, its sets, its flags,
and the package walk and top-level merge of an installation's override are
all as before.

The normalised result is still cached in the core cache, but under the new
identifier ``AcademicPersons_Settings_v3`` instead of
``AcademicPersons_Settings``. The cache entry is a PHP statement naming the
classes of the settings graph, so an entry written by an earlier version
references the removed :php:`FGTCLB\AcademicPersons\Settings\ValidationSet`
and would fail to load before any check could reject it. The new identifier
leaves such an entry untouched and unused; it disappears with the next cache
flush.

Impact
======

Code that type hints or instantiates the old classes fails with a class not
found error and has to import the `EXT:academic_base` names.

A project that overrides one of the form partials of
`EXT:academic_persons_edit` and declares the ViewHelper namespace itself has
to point it at the new location, or the editing form no longer renders:

..  code-block:: html

    xmlns:p="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"

The ``p:validationEnsure`` calls in the template stay as they are. The five
partials are listed in the `EXT:academic_persons_edit` changelog entry
"Adapted frontend editing Fluid files"
(https://docs.typo3.org/p/fgtclb/academic-persons-edit/main/en-us/Changelog/3.0/Breaking-AdaptedFrontendEditingFluidFiles.html).

No cache flush is required for the settings themselves - see the cache
identifier above - but flushing all caches after the update is harmless and
removes the orphaned entry.

.. index:: PHP-API, TCA, Fluid, ext:academic_persons
