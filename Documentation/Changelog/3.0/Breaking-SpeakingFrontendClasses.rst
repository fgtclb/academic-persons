..  _breaking-persons-speaking-frontend-classes:

==================================================
Breaking: Profile templates carry speaking classes
==================================================

Description
===========

The frontend templates of this extension carried the classes of the theme they
were developed with - cards, list groups, navigation pills, the pagination and
form classes, spacing and display utilities - next to block and element classes
of the extension, `academic-persons-item__name` or
`academic-persons-detail__contact-row` for example. They carry speaking classes
now, named after what an element is and shared by all academic extensions:
`ace-item`, `ace-list`, `ace-attribute`, `ace-label`, `ace-value`, `ace-link`
and so on. The outermost element of a plugin keeps the class of the plugin,
`academic-persons-list`, `academic-persons-card`, `academic-persons-profiles`,
`academic-persons-contracts` and `academic-persons-detail`. The classes of
Bootstrap that a site builds on stay on purpose where the templates render
them: the grid classes `row` and `col-*`, the button classes `btn` and `btn-*`
and `visually-hidden`. No other theme class is rendered any more, and the
extension ships no styles for the new classes, see
:ref:`breaking-public-profile-ships-no-stylesheet`.

The header partials take the class of the heading as the argument `class`,
see :ref:`breaking-persons-header-partials-take-a-class-argument`. The list no
longer hands the view mode partials the argument `class`.

Version 2 rendered the item as `academic-persons-item card flex-column-reverse`
with `card-body`, its heading with `card-title` and its image with
`card-img-top img-fluid`, the grid as `academic-persons-itemlist row`, and the
letter and page navigation with the `pagination`, `page-item` and `page-link`
classes of Bootstrap, the letter navigation with `alphabetical-pagination`. The
other block classes of the column *Before*, those of the item, the list and the
detail view, came with the development of 3.0, see
:ref:`important-profile-item-and-list-partials` and
:ref:`feature-configurable-public-profile`. They are listed as well, because
the development versions of 3.0 were installed and styled. The detail view of
version 2 is replaced by the configurable layout, see
:ref:`breaking-public-profile-detail-partials`.

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Before
        -   After
    *   -   Item grid
        -   `academic-persons-itemlist row` in version 2, then
            `academic-persons-grid` with the class `academic-persons-itemlist`
            handed in, and `academic-persons-grid__item` on each column
        -   `ace-itemlist` around the `row`, the columns keep their `col-*`
    *   -   Item
        -   `academic-persons-item card flex-column-reverse` with `card-body`
        -   `<article class="ace-item">` with `ace-item-content`
    *   -   Heading of an item
        -   `academic-persons-item__name card-title`
        -   `ace-title ace-name` in a :html:`<header class="ace-header">`
    *   -   Image of an item
        -   `academic-persons-item__image card-img-top img-fluid`
        -   `ace-image`, in an `ace-picture`
    *   -   Contract fields of an item
        -   `list-group list-group-flush` of `list-group-item` entries, the
            values of a field in `d-block`
        -   `ace-list ace-attributes` of `ace-list-item ace-attribute`
            entries with `ace-label` and `ace-value`, the values of a field
            `ace-list-item`, links `ace-link`
    *   -   Group heading of a grouped list
        -   `academic-persons-list__group-header`
        -   `ace-title ace-group-header` in an `ace-header`
    *   -   Empty state
        -   `<p class="academic-persons-empty-state">`
        -   `<span class="ace-empty">`
    *   -   Filter of the list
        -   `academic-persons-list__filter row g-3 align-items-end mb-4` with
            `form-label`, `form-select` and `btn btn-primary`
        -   `ace-form` with `ace-filters` around the `row`, every select in an
            `ace-filter ace-field ace-select-wrap` with `ace-label` and
            `ace-control ace-select`, the submit button
            `ace-control ace-submit btn btn-primary` in an `ace-actions`
    *   -   Letter navigation
        -   `academic-persons-list__alphabet-pagination alphabetical-pagination mb-4`
            with `pagination`, `page-item` and `page-link`
        -   `ace-navigation ace-alphabet-navigation` with `ace-list`,
            `ace-list-item` and `ace-link`, `active` and `disabled` on the
            entry
    *   -   Page navigation
        -   `academic-persons-list__pagination` with `pagination`,
            `page-item`, `current` and `page-link active`
        -   `ace-pagination` around an `f3-widget-paginator` of
            `ace-list-item` entries with an `ace-link`, `active` on the
            current entry
    *   -   View mode switch
        -   `academic-persons-view-mode-switch mb-4` with `nav nav-pills`,
            `nav-item` and `nav-link`
        -   `ace-navigation ace-view-mode-switch` with `ace-list`,
            `ace-list-item` and `ace-link`, `active` on the current mode
    *   -   Table view
        -   `academic-persons-table table-responsive` around a `table`, the
            values of a cell in `d-block`
        -   `ace-table-wrap` around an `ace-table` with `ace-table-header`
            and `ace-table-body`, the rows `ace-item`, the header cells
            `ace-label`, the cells `ace-value`, the values `ace-list-item`
    *   -   Layout of the detail view
        -   `container-fluid academic-persons-detail px-0`, a `row` with
            `academic-persons-detail__layout`, the columns
            `academic-persons-detail__aside` and `academic-persons-detail__main`,
            `academic-persons-detail__sticky-navigation` and
            `academic-persons-detail__mobile-navigation`
        -   `academic-persons-detail` with `ace-content ace-profile-layout`
            around the `row`, `ace-navigation ace-sidebar`,
            `ace-sticky-navigation`, `ace-main` and
            `ace-navigation ace-mobile-navigation`
    *   -   Headline of the detail view
        -   `academic-persons-detail__header`, `academic-persons-detail__headline`
            and `academic-persons-detail__headline-part`
        -   `ace-header`, `ace-title ace-profile-name` and `ace-name`
    *   -   Position line
        -   `academic-persons-detail__positions`, `academic-persons-detail__position`
            and `academic-persons-detail__position-part` with
            `academic-persons-detail__position-part--<field>`
        -   `ace-attributes ace-positions`, `ace-attribute ace-position-line`
            and `ace-value` with `ace-position`, `ace-function-type` or
            `ace-organisational-unit`
    *   -   Profile image of the detail view
        -   `academic-persons-detail__figure` around the image
            `academic-persons-detail__image img-fluid rounded-0`
        -   `ace-profile-image` around the image `ace-image`
    *   -   Contact block
        -   `academic-persons-detail__contact` with
            `academic-persons-detail__contact-heading`, `-contract`, `-row`,
            `-icon`, `-content`, `-line`, `-link` and `-type`, the office
            hours `academic-persons-detail__contact-row--office-hours` and
            `academic-persons-detail__contact-office-hours`
        -   `<section class="ace-contact">` with `ace-title`,
            `ace-item ace-contract` per contract, `ace-attribute` per line
            with `ace-icon` and `ace-value`, `ace-list-item`, `ace-link` and
            `ace-label`, the office hours `ace-attribute ace-office-hours`
            with their text in a `ce-bodytext`
    *   -   Subline
        -   `academic-persons-detail__subline`
        -   `ace-subtitle`
    *   -   Profile entries
        -   `academic-persons-detail__profile-entries`, `__accordion-item`,
            `__accordion-heading`, `__accordion-button`, `__accordion-label`,
            `__accordion-icon` with `__accordion-icon-plus` and
            `__accordion-icon-minus`, and `__accordion-panel`
        -   `ace-accordion`, `ace-accordion-item`, `ace-header`,
            `ace-toggle`, `ace-label`, `ace-icon` with `ace-open` and
            `ace-close`, and `ace-content` with a `ce-bodytext`
    *   -   Links
        -   `academic-persons-detail__links`, `__link-row`, `__link-label`
            and `__link`
        -   `ace-list ace-links`, `ace-list-item ace-attribute`, `ace-label`
            and `ace-link`
    *   -   Section navigation
        -   `academic-persons-detail__navigation` with
            `nav flex-column academic-persons-detail__navigation-list list-unstyled`,
            `nav-item academic-persons-detail__navigation-item`,
            `nav-link px-0 py-0 academic-persons-detail__navigation-link`,
            `__navigation-number` and `__navigation-label`
        -   `ace-navigation` with `ace-list`, `ace-list-item`, `ace-link`,
            `ace-count` and `ace-label`
    *   -   Timeline
        -   `academic-persons-detail__menu-section-data`,
            `__timeline-section`, `__timeline-heading`, `__timeline`,
            `__timeline-item`, `__timeline-date`, `__timeline-title`,
            `__timeline-link` and `__timeline-copy`
        -   `ace-content ace-timeline-sections`, `ace-section`,
            `ace-title`, `ace-list ace-timeline`, `ace-item ace-timeline-item`,
            `ace-label ace-date`, `ace-title`, `ace-link` and `ce-bodytext`

The rich text of a profile entry, of a timeline entry and the office hours are
in a `ce-bodytext`, the class the core templates of
:guilabel:`fluid_styled_content` give the text of a content element.

Impact
======

*   A site stylesheet that styles the lists, the card, the selected profiles
    and contracts or the detail view through the classes of the column
    *Before* no longer matches.
*   A theme stylesheet that styled the plugins through `card`, `list-group`,
    `nav-pills`, `pagination` or `table` no longer reaches them.
*   An override that renders a view mode partial with `class` renders it
    without that class, the partials do not read it.

Affected Installations
======================

Every installation that renders a profile list, card, selected profiles or
contracts, or the detail view of a profile, and every installation of
`EXT:academic_contacts4pages`, whose contacts are profile items of this
extension.

Migration
=========

#.  Move the selectors of the site stylesheet from the classes in the
    column *Before* to those in the column *After*. The chapter
    :ref:`Styling <styling>` shows how the markup of each plugin is built up.
    A site that styled the plugins through the classes of its Bootstrap theme
    alone gives the new classes the look it wants, for example with
    :css:`@extend` in SCSS or with rules of its own.
#.  A template override is compared with the shipped template of 3.0 and
    adopts its markup. An override keeps working as it is, but renders its
    own classes. It keeps the `data-academic-persons-*` attributes the
    profile script reads.
#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, ext:academic_persons
