..  _breaking-site-sets-and-static-templates-restructured:

===============================================================
Breaking: Site sets and static templates have been restructured
===============================================================

Description
===========

The TypoScript and the page TSconfig of this extension were shipped twice: the
static template read :file:`Configuration/TypoScript/Default/`, and the two site
sets :yaml:`fgtclb/academic-persons-default` and
:yaml:`fgtclb/academic-persons-standalone` shipped their own
:file:`constants.typoscript` and :file:`setup.typoscript`, each of them a single
:typoscript:`@import` of that folder. The page TSconfig existed as one flat file,
:file:`Configuration/TSconfig/page.tsconfig`, and was not selectable on a page at
all.

Both mechanisms now read one physical copy of every file, and both of them
deliver the extension per content element instead of as one block:

*   :file:`Configuration/TypoScript/Default/` still holds the shared
    :typoscript:`plugin.tx_academicpersons` block — all six content elements are
    driven by one Extbase plugin, so there is one copy of it and every component
    folder names it in its own :file:`include_static_file.txt`.
*   :file:`Configuration/TypoScript/<Component>/` is what the static template
    registers *and* what the component set points its :yaml:`typoscript` key at.
*   :file:`Configuration/TSconfig/<Component>/page.tsconfig` holds the page
    TSconfig of one content element and is what the page field
    :guilabel:`Page TSconfig` offers *and* what the set points its :yaml:`pagets`
    key at.
*   :file:`Configuration/TypoScript/Full/` and
    :file:`Configuration/TSconfig/Full/page.tsconfig` are the aggregates for
    installations that do not use site sets.

The content elements are now **hidden by default**. The always-included
:file:`Configuration/page.tsconfig` removes all six from the selectable content
element types, and the page TSconfig of a component adds its own back — so an
element is offered where it is wanted instead of on every page of every
installation. The TCA registration itself did not move, so the frontend renders
existing records exactly as before. Editing such a record in the backend is a
different matter — read the warning below before upgrading.

Two defaults changed as well. :typoscript:`plugin.tx_academicpersons.demand.groupBy`
and :typoscript:`plugin.tx_academicpersons.demand.sortBy` were empty in
:file:`constants.typoscript` while the site sets declared `lastNameAlpha` and
`title` for the very same paths, so the two mechanisms configured a site
differently. Both now carry the same value everywhere.

:typoscript:`demand.sortBy` is additionally corrected to `lastName`. The value
the site sets shipped, `title`, is not one the extension accepts: the
extension configuration :typoscript:`demand.allowedSortByValues` allows
`firstName` and `lastName` only, and
:php:`ProfileRepository::getOrderingsFromDemand()` drops anything else. A site
on a site set therefore asked for an ordering it never got.

Impact
======

The :sql:`sys_template` records of this extension keep working. Both values that
installations store today — `EXT:academic_persons/Configuration/TypoScript/Default`
and `EXT:academic_persons/Configuration/TypoScript/Standalone` — still resolve,
and both still deliver the plugin configuration. Only the labels shown next to
them in the record changed.

A site that has no site set and did not set
:typoscript:`plugin.tx_academicpersons.demand.groupBy` itself now groups a
profile list by the first letter of the last name, because that is the default
the site sets already applied. Set the constant to an empty value to keep an
ungrouped list.

A site **on a site set** that did not set
:typoscript:`plugin.tx_academicpersons.demand.sortBy` itself now sorts a
profile list by last name. It asked for `title` before and got no ordering at
all, because the value was rejected. Set the setting to `firstName` for the
other accepted ordering; there is no supported way back to "no ordering"
through this setting.

A site package that imported one of the removed files by path fails to resolve
it. :typoscript:`@import` of a missing file is silent, so this shows up as
missing configuration rather than as an error message.

None of the six content elements is offered in the backend until the page
TSconfig of its component is included, through the site set or through the page
field :guilabel:`Page TSconfig`. This affects every installation: before this
change the extension hid nothing.

..  warning::

    Do not open an existing record of one of these content elements in the
    backend form on a page that does not include the page TSconfig of its
    component. An item removed through
    :typoscript:`TCEFORM.tt_content.CType.removeItems` is excluded from the
    :guilabel:`[ invalid value ]` fallback TYPO3 otherwise adds for a stored
    value it does not know, and the stored value is dropped from the form data
    as well. The field :guilabel:`Type` therefore comes up with nothing
    selected, and **saving the record writes whatever the browser preselected
    into** :sql:`CType` — the record silently becomes another content element.
    The frontend keeps rendering it correctly until that happens.

    Include the page TSconfig of the components on every page tree that holds
    such records, and do it before editing them.

The sets :yaml:`fgtclb/academic-persons-default` and
:yaml:`fgtclb/academic-persons-standalone` keep their names and keep delivering
everything, so a site configuration that depends on either needs no change.

Affected Installations
======================

Every installation that uses one of the content elements of this extension, plus
installations that import one of the shipped files from an own site package.

Migration
=========

Add the page TSconfig entry, which did not exist before, in the page record of
the site root, tab :guilabel:`Resources`, field :guilabel:`Page TSconfig`:
:guilabel:`Academic Persons: All components (academic_persons)`, stored as
`EXT:academic_persons/Configuration/TSconfig/Full/page.tsconfig`. Without it the
content elements are not selectable any more, and existing records of them lose
their :sql:`CType` when they are saved from the backend form.

Sites that use a site set instead need no migration — but they must not use both
mechanisms at once, see the :guilabel:`Configuration` chapter.

The static template entries in the :sql:`sys_template` record need no migration
either. Their labels changed, their stored values did not:

..  list-table::
    :header-rows: 1

    *   -   Stored value
        -   Old label
        -   New label
    *   -   `EXT:academic_persons/Configuration/TypoScript/Default`
        -   :guilabel:`Academic Persons Settings (academic_persons)`
        -   :guilabel:`Academic Persons: Shared plugin settings (academic_persons)`
    *   -   `EXT:academic_persons/Configuration/TypoScript/Standalone`
        -   :guilabel:`Academic Persons Standalone (academic_persons)`
        -   :guilabel:`Academic Persons: Standalone page (academic_persons)`

Adjust every :typoscript:`@import` in an own site package:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_persons/Configuration/TypoScript/constants.typoscript`
        -   `EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript`
    *   -   `EXT:academic_persons/Configuration/TypoScript/setup.typoscript`
        -   `EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript`
    *   -   `EXT:academic_persons/Configuration/TypoScript/Standalone/constants.typoscript`
        -   `EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript`
    *   -   `EXT:academic_persons/Configuration/TypoScript/Standalone/setup.typoscript`
        -   `EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript`
            and, for the :typoscript:`page` object,
            `EXT:academic_persons/Configuration/TypoScript/StandalonePage/setup.typoscript`
    *   -   `EXT:academic_persons/Configuration/TSconfig/page.tsconfig`
        -   `EXT:academic_persons/Configuration/TSconfig/Full/page.tsconfig`

The first two were shipped as :typoscript:`@deprecated` one line forwards to
:file:`Configuration/TypoScript/Default/` and are removed with this change.

A site configuration may name the new component sets instead of the aggregate:

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-persons`
        -   New name of the aggregate, delivers through the component sets
            below.
    *   -   `fgtclb/academic-persons-default`
        -   Unchanged in name, now an alias of `fgtclb/academic-persons`.
    *   -   `fgtclb/academic-persons-standalone`
        -   Unchanged in name, now the aggregate plus the :typoscript:`page`
            object.
    *   -   `fgtclb/academic-persons-list`
        -   The :guilabel:`Persons List` content element only.
    *   -   `fgtclb/academic-persons-list-and-detail`
        -   The :guilabel:`Persons List and Detail` content element only.
    *   -   `fgtclb/academic-persons-detail`
        -   The :guilabel:`Persons Detail` content element only.
    *   -   `fgtclb/academic-persons-card`
        -   The :guilabel:`Contacts` content element only.
    *   -   `fgtclb/academic-persons-selected-profiles`
        -   The :guilabel:`Profiles: Selected Profiles` content element only.
    *   -   `fgtclb/academic-persons-selected-contracts`
        -   The :guilabel:`Profiles: Selected Contracts` content element only.

..  index:: TypoScript, TSConfig, Backend, ext:academic_persons
