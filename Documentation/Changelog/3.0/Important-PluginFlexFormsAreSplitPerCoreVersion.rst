.. _important-plugin-flexforms-are-split-per-core-version:

==========================================================
Important: FlexForm paths change for the plugin structures
==========================================================

Description
===========

The FlexForm data structures of the plugins have moved. Compared with the last
2.x release, the folder layout of
:file:`EXT:academic_persons/Configuration/FlexForms/` is now:

..  list-table::
    :header-rows: 1

    *   -   Structure
        -   In 2.3.4
        -   In 3.0.0
    *   -   :file:`List.xml`, :file:`Detail.xml`
        -   :file:`Core12/`, :file:`Core13/`
        -   :file:`Core13/`, :file:`Core14/`
    *   -   :file:`SelectedProfiles.xml`, :file:`SelectedContracts.xml`
        -   :file:`Core12/`, :file:`Core13/`
        -   directly in :file:`FlexForms/`

The :file:`Core12/` variants are gone with TYPO3 v12 support.
:file:`SelectedProfiles.xml` and :file:`SelectedContracts.xml` are identical on
every supported version and are no longer duplicated. :file:`List.xml` and
:file:`Detail.xml` keep their :file:`Core13/` path and gain a :file:`Core14/`
sibling.

Those two still need a variant per version because of
:php:`settings.pageTitleFormat`, the only field in this extension configured
with a :php:`valuePicker`. TYPO3 changed how it reads the items of that picker,
and it is the one item list core never made readable in both directions:

..  list-table::
    :header-rows: 1

    *   -   TYPO3
        -   Reads
        -   Given the other form
    *   -   v13
        -   the positional pair :php:`$item[0]` / :php:`$item[1]`
        -   :php:`Undefined array key 1`, then a :php:`TypeError` - the element
            renders nothing and the plugin cannot be opened
    *   -   v14
        -   :php:`$item['label']` / :php:`$item['value']`
        -   migrated on the fly, at the price of an :php:`E_USER_DEPRECATED`

FlexForm XML cannot carry a runtime switch, so the two forms need two files.
:file:`Configuration/TCA/Overrides/tt_content.php` selects the folder from the
running major version.

Impact
======

Nothing changes for a site that installs the extension and configures its
plugins in the backend. The plugin options are the same on both TYPO3 versions.

Affected Installations
======================

Installations that reference one of the four data structures by path - for
example an extension or site package that registers the same structure for a
content element of its own, or overrides it through
:php:`$GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']`.

References to :file:`Core12/…` and to :file:`Core13/SelectedProfiles.xml` or
:file:`Core13/SelectedContracts.xml` no longer resolve. References to
:file:`Core13/List.xml` and :file:`Core13/Detail.xml` keep working, but only
deliver the TYPO3 v13 variant.

Solution
========

Reference the two shared structures directly:

..  code-block:: text

    FILE:EXT:academic_persons/Configuration/FlexForms/SelectedProfiles.xml
    FILE:EXT:academic_persons/Configuration/FlexForms/SelectedContracts.xml

For the two split ones, select the variant that matches the running TYPO3
version, the way this extension does:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tt_content.php

    use TYPO3\CMS\Core\Information\Typo3Version;

    $flexFormPath = 'FILE:EXT:academic_persons/Configuration/FlexForms/'
        . ((new Typo3Version())->getMajorVersion() < 14 ? 'Core13' : 'Core14')
        . '/';

    // $flexFormPath . 'List.xml'
    // $flexFormPath . 'Detail.xml'

Once support for TYPO3 v13 is dropped, the :file:`Core13` variants go away and
the :file:`Core14` ones move back up one level.

.. index:: Backend, TCA, ext:academic_persons
