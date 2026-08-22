:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

This extension ships its frontend TypoScript and its backend page TSconfig in
two forms: as TYPO3 **site sets**, and as classic **static templates** plus
**page TSconfig files** that are selected on a page. Both forms read the very
same files, so they configure an installation identically.

Pick one of them per site and stay with it — see
:ref:`Do not combine both <one-mechanism-per-site>` for what happens otherwise.

..  note::

    Site sets arrived in TYPO3 v13.1. On **TYPO3 v12 they do not exist**: the
    set definitions this extension ships are never read there, and a
    :yaml:`dependencies` key in a site configuration is stored and ignored. On
    that version the static templates and the page TSconfig files below are the
    whole mechanism.

..  _configuration-components:

What the sets contain
=====================

This extension ships six content elements, so it ships six component sets and
one aggregate set that depends on all of them.

All six are driven by one Extbase plugin, so they share one TypoScript block,
:typoscript:`plugin.tx_academicpersons`. That block is shipped once, in
:file:`Configuration/TypoScript/Default/`, and every component includes it.
Which component sets a site names therefore decides which content elements the
backend offers, not how much TypoScript is loaded.

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-persons-list`
        -   The :guilabel:`Persons List` content element.
    *   -   `fgtclb/academic-persons-list-and-detail`
        -   The :guilabel:`Persons List and Detail` content element.
    *   -   `fgtclb/academic-persons-detail`
        -   The :guilabel:`Persons Detail` content element.
    *   -   `fgtclb/academic-persons-card`
        -   The :guilabel:`Contacts` content element, and the FlexForm
            restriction that hides the list, sorting and pagination fields for
            it.
    *   -   `fgtclb/academic-persons-selected-profiles`
        -   The :guilabel:`Profiles: Selected Profiles` content element.
    *   -   `fgtclb/academic-persons-selected-contracts`
        -   The :guilabel:`Profiles: Selected Contracts` content element.
    *   -   `fgtclb/academic-persons`
        -   Everything above. This is the set to use unless you deliberately
            want a subset.
    *   -   `fgtclb/academic-persons-default`
        -   The name this extension published before the sets were cut per
            component. It delivers exactly what `fgtclb/academic-persons`
            delivers, and is kept so that existing site configurations keep
            working.
    *   -   `fgtclb/academic-persons-standalone`
        -   Everything the aggregate delivers, plus a :typoscript:`page` object
            that renders content on a plain Bootstrap page. Meant for an
            installation without a site package of its own — an alternative to
            `fgtclb/academic-persons`, never an addition to it.

Every component set depends on `fgtclb/academic-base-ctype-group`, the set of
:guilabel:`EXT:academic_base` that labels the content element group all academic
extensions sort their elements into.

The site settings of this extension — the detail page, and the default grouping,
sorting and pagination of a profile list — are declared with the aggregate set.
A site that depends on a single component set still gets the shipped defaults,
but can only override them in :guilabel:`Site Settings` when it depends on
`fgtclb/academic-persons`.

..  _configuration-hidden-by-default:

The content elements are hidden by default
==========================================

:guilabel:`EXT:academic_persons` hides all six of its content elements for the
whole installation and brings them back per component. Whichever of the two
mechanisms below you use, it is what makes an element selectable in the backend
again — without one of them the content element is not offered, and existing
records keep rendering.

..  warning::

    This changed in version 2.4. Before it, all six elements were selectable on
    every page of every installation. Read
    :ref:`Breaking: Site sets and static templates have been restructured
    <breaking-site-sets-and-static-templates-restructured>` before upgrading:
    opening an existing record on a page that does not include the page
    TSconfig of its component can rewrite the type of that record.

..  _site-set:

Include the site set
====================

On TYPO3 v13, add the set to the :file:`config.yaml` of the site that should
offer the content elements:

..  code-block:: diff
    :caption: config/sites/my-site/config.yaml (diff)

     base: 'https://example.com/'
     rootPageId: 1
    +dependencies:
    +  - fgtclb/academic-persons

See also `TYPO3 Explained, Using a site set as dependency in a site
<https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`__.

..  _static-templates:

Include static templates
========================

For an installation that configures its frontend through :sql:`sys_template`
records — which on TYPO3 v12 is every installation — the same files are
registered as static templates and as selectable page TSconfig files.

..  tip::

    On TYPO3 v13 we recommend the site set — and if you use it, do not
    press the backend button :guilabel:`Create a root TypoScript record` on that
    site. The :sql:`sys_template` record it creates carries the flag
    :guilabel:`Clear` for constants and setup, and that flag discards everything
    the site sets contributed. An installation that is already in that state
    gets its configuration back by selecting the static templates below in that
    very record.

..  _static-typoscript:

Include static TypoScript
-------------------------

Edit the :sql:`sys_template` record of the site root and add the entry to
:guilabel:`Include static (from extensions)`:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Persons: All components (academic_persons)`
        -   Every component this extension ships, in one entry. This is the
            entry to use.
    *   -   :guilabel:`Academic Persons: Shared plugin settings (academic_persons)`
        -   The shared :typoscript:`plugin.tx_academicpersons` block on its own.
            This is what installations selected before version 2.4, then named
            :guilabel:`Academic Persons Settings`. The stored value did not
            change, so such a record keeps working untouched.
    *   -   :guilabel:`Academic Persons: Standalone page (academic_persons)`
        -   Everything :guilabel:`All components` delivers, plus the
            :typoscript:`page` object of the standalone flavour. Do not select
            it on a site that has a site package of its own.

There is one further entry per component —
:guilabel:`Academic Persons: Profile list`,
:guilabel:`Academic Persons: Profile list and detail`,
:guilabel:`Academic Persons: Profile detail`,
:guilabel:`Academic Persons: Profile card`,
:guilabel:`Academic Persons: Selected profiles` and
:guilabel:`Academic Persons: Selected contracts`. They exist so that the static
mechanism has the same shape as the sets. Because all six components share one
TypoScript block, each of them delivers the same thing, and selecting more than
one of them changes nothing.

..  _static-pagetsconfig:

Include static page TSconfig
----------------------------

This is the half that decides which content elements the backend offers. Edit
the page record of the site root, tab :guilabel:`Resources`, field
:guilabel:`Page TSconfig`, and add the entries for the content elements the page
tree should offer:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Persons: All components (academic_persons)`
        -   Every component this extension ships, in one entry.
    *   -   :guilabel:`Academic Persons: Profile list (academic_persons)`
        -   Makes the :guilabel:`Persons List` content element selectable, and
            configures its entry in the new content element wizard.
    *   -   :guilabel:`Academic Persons: Profile list and detail (academic_persons)`
        -   The same for :guilabel:`Persons List and Detail`.
    *   -   :guilabel:`Academic Persons: Profile detail (academic_persons)`
        -   The same for :guilabel:`Persons Detail`.
    *   -   :guilabel:`Academic Persons: Profile card (academic_persons)`
        -   The same for :guilabel:`Contacts`, plus the FlexForm restriction of
            that element.
    *   -   :guilabel:`Academic Persons: Selected profiles (academic_persons)`
        -   The same for :guilabel:`Profiles: Selected Profiles`.
    *   -   :guilabel:`Academic Persons: Selected contracts (academic_persons)`
        -   The same for :guilabel:`Profiles: Selected Contracts`.

The setting is inherited by every page below the one it is set on.

..  _one-mechanism-per-site:

Do not combine both
===================

This applies to TYPO3 v13 only, because v12 has no site sets to combine
anything with. A site that uses the site set **and** the static template reads
the shipped files twice. The site set is applied before the :sql:`sys_template` record, so
the second read happens after the site settings and after
:file:`config/sites/<site>/constants.typoscript` — and it resets every constant
the extension ships a default for back to that default.

Nothing else is damaged: the :guilabel:`Constants` and :guilabel:`Setup` fields
of the :sql:`sys_template` record, the page TSconfig of a page and the page
TSconfig files selected on a page are all applied afterwards and still win. Use
one mechanism per site and the question does not arise.

..  toctree::
   :maxdepth: 5
   :titlesonly:

   General/Index
   Validations/Index
   RouteEnhancers/Index
