..  index:: ! Styling
..  _styling:

=======
Styling
=======

The plugins of this extension render their markup with the class system of the
academic extensions and ship no styling for it. The system, the classes of
Bootstrap it keeps and how a site package styles the markup are described once,
in the chapter `Styling of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Styling/Index.html>`__.
This chapter shows how the markup of this extension is built up.

The opt-in set `fgtclb/academic-persons-standalone` is the one part of the
extension that loads a stylesheet: its page object, meant for an installation
without a site package of its own, loads the stylesheet and the script of
Bootstrap 5 from jsDelivr and renders the plugins on a plain Bootstrap page. It
does not style the markup described here.

..  contents::
    :local:
    :depth: 1

..  _styling-outermost-element:

The outermost elements
======================

..  list-table::
    :header-rows: 1
    :widths: 30 35 35

    *   -   Plugin
        -   Content element type
        -   Outermost element
    *   -   Profile list
        -   `academicpersons_list`
        -   `academic-persons-list`
    *   -   Profile detail
        -   `academicpersons_detail`
        -   `academic-persons-detail`
    *   -   Profile list and detail
        -   `academicpersons_listanddetail`
        -   `academic-persons-list`, and `academic-persons-detail` on the
            detail view
    *   -   Profile card
        -   `academicpersons_card`
        -   `academic-persons-card`
    *   -   Selected profiles
        -   `academicpersons_selectedprofiles`
        -   `academic-persons-profiles`
    *   -   Selected contracts
        -   `academicpersons_selectedcontracts`
        -   `academic-persons-contracts`

..  _styling-list:

The profile list
================

..  code-block:: html

    <div class="academic-persons-list">
        <!-- the header of the content element, when the plugin renders it -->
        <form class="ace-form" …>
            <div class="ace-filters">
                <div class="row">
                    <div class="col-auto">
                        <div class="ace-filter ace-field ace-select-wrap">
                            <label class="ace-label" for="…">Function</label>
                            <select class="ace-control ace-select" …>…</select>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="ace-actions">
                            <input type="submit" class="ace-control ace-submit btn btn-primary" value="Filter" />
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <nav class="ace-navigation ace-alphabet-navigation" aria-label="…">
            <ul class="ace-list">
                <li class="ace-list-item active"><a class="ace-link" aria-current="page" …>A-Z</a></li>
                <li class="ace-list-item"><a class="ace-link" …><span>B</span></a></li>
                <li class="ace-list-item disabled">
                    <span class="ace-link">C<span class="visually-hidden"> - no profiles</span></span>
                </li>
            </ul>
        </nav>
        <nav class="ace-navigation ace-view-mode-switch" aria-label="…">
            <ul class="ace-list">
                <li class="ace-list-item"><a class="ace-link active" aria-current="true" …>List</a></li>
                <li class="ace-list-item"><a class="ace-link" …>Table</a></li>
            </ul>
        </nav>
        <div class="ace-itemlist">
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                    <article class="ace-item">…</article>
                </div>
            </div>
        </div>
        <nav class="ace-pagination" aria-label="…">
            <ul class="f3-widget-paginator">
                <li class="ace-list-item active" aria-current="page"><span class="ace-link">2</span></li>
                <li class="ace-list-item next"><a class="ace-link" …><span>Next</span></a></li>
            </ul>
        </nav>
    </div>

*   The filter, the alphabet navigation, the view mode switch and the
    pagination are each only rendered when the content element configures
    them.
*   With a grouping, every group starts with its heading, a
    :html:`<header class="ace-header">` around an `ace-title ace-group-header`,
    followed by the profiles of the group in the view mode of the list.
*   The pagination entries carry `first`, `previous`, `next` and `last`, the
    current page `active`, the gaps `disabled`. Unlike the paginations of the
    job and partner lists, which name their entries `page-item` and
    `page-link`, the entries here are `ace-list-item` with an `ace-link`, in a
    :html:`<nav class="ace-pagination">`.
*   The submit button of the filter carries `ace-control ace-submit` next to
    the Bootstrap button classes `btn btn-primary`. The submit buttons of the
    job form and the program plugins carry the Bootstrap classes only.
*   A list without profiles renders the message in a
    :html:`<span class="ace-empty">`.

..  _styling-item:

A profile
---------

The list view, the card, the selected profiles and the selected contracts
render a profile the same way, and so does the contact list of
:guilabel:`EXT:academic_contacts4pages`:

..  code-block:: html

    <article class="ace-item">
        <div class="ace-item-content">
            <header class="ace-header">
                <h2 class="ace-title ace-name"><a href="…">Dr. Jane Doe</a></h2>
            </header>
            <ul class="ace-list ace-attributes">
                <li class="ace-list-item ace-attribute">
                    <b class="ace-label">Email:</b>
                    <div class="ace-value"><a class="ace-link" href="mailto:…">…</a></div>
                </li>
            </ul>
        </div>
        <!-- the image of the profile, see "An image" in academic_base -->
    </article>

*   The attributes are the fields of the contract the content element shows.
    Several email addresses or phone numbers are links in one `ace-value`, the
    addresses an `ace-list-item` each.
*   The image follows the text and shows the placeholder of the profile when
    there is no image, see :ref:`templates-profile-image`.

..  _styling-table:

The table view
--------------

..  code-block:: html

    <div class="ace-table-wrap">
        <table class="ace-table">
            <thead class="ace-table-header">
                <tr><th class="ace-label" scope="col">Name</th>…</tr>
            </thead>
            <tbody class="ace-table-body">
                <tr class="ace-item">
                    <th class="ace-label" scope="row"><a class="ace-link" href="…">Dr. Jane Doe</a></th>
                    <td class="ace-value"><span class="ace-list-item">…</span></td>
                </tr>
            </tbody>
        </table>
    </div>

A cell holds an `ace-list-item` per contract that has a value for its column.
The head and body of the table are `ace-table-header` and `ace-table-body`.
The table view of :guilabel:`EXT:academic_bite_jobs` names them `ace-head` and
`ace-body`, the profile list of :guilabel:`EXT:academic_persons_edit`
`ace-header` and `ace-content`.

..  _styling-detail:

The profile detail
==================

The detail view arranges the elements the content element configures in two
columns, a navigation column and the content:

..  code-block:: html

    <div class="academic-persons-detail" data-academic-persons-detail>
        <div class="ace-content ace-profile-layout">
            <div class="row">
                <div class="col-12 col-lg-4">
                    <div class="ace-navigation ace-sidebar">
                        <div class="ace-sticky-navigation" data-academic-persons-sticky-navigation>
                            <!-- the elements of the left column -->
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="ace-main">
                        <!-- the elements of the right column -->
                        <!-- before the subline, the elements of the left column once more,
                             for a narrow viewport -->
                        <div class="ace-navigation ace-mobile-navigation">…</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

The elements are:

..  list-table::
    :header-rows: 1
    :widths: 25 75

    *   -   Element
        -   Markup
    *   -   Headline
        -   :html:`<header class="ace-header">` around an
            :html:`<h2 class="ace-title ace-profile-name">` holding one
            `ace-name` per part of the name.
    *   -   Position
        -   An `ace-attributes ace-positions` of one
            `ace-attribute ace-position-line` per contract, holding an
            `ace-value` per part, `ace-position`, `ace-function-type` or
            `ace-organisational-unit`.
    *   -   Profile image
        -   An `ace-profile-image` around the image.
    *   -   Contact
        -   A :html:`<section class="ace-contact">` with an `ace-title`, and
            an `ace-item ace-contract` per contract. Each email, phone,
            address, room and office hours line is an `ace-attribute` with an
            `ace-icon` and an `ace-value`, the office hours an
            `ace-attribute ace-office-hours` with their text in a
            `ce-bodytext`.
    *   -   Subline
        -   An :html:`<h3 class="ace-subtitle">`.
    *   -   Profile entries
        -   An `ace-accordion` of one :html:`<section
            class="ace-accordion-item">` per entry: a heading `ace-header`
            holding a button `ace-toggle` with an `ace-label` and an
            `ace-icon`, which holds an `ace-open` and an `ace-close` glyph, and
            the text in an `ace-content` with a `ce-bodytext`.
    *   -   Links
        -   An `ace-list ace-links` of `ace-list-item ace-attribute` entries,
            each with an `ace-label` and an `ace-link`.
    *   -   Menu sections
        -   A :html:`<nav class="ace-navigation">` with an
            :html:`<ol class="ace-list">` of entries, each an `ace-link` with an
            empty `ace-count` for a number and an `ace-label`.
    *   -   Menu section data
        -   An `ace-content ace-timeline-sections` of one
            :html:`<section class="ace-section">` per menu section, with an
            `ace-title` and an `ace-list ace-timeline` of
            `ace-item ace-timeline-item` entries: the years in an
            `ace-label ace-date`, the title in an `ace-title`, the text in a
            `ce-bodytext`.

The profile script works with the `data-academic-persons-*` attributes and
writes these states:

*   :html:`aria-expanded` on the `ace-toggle` of a profile entry, and
    :html:`hidden` on its `ace-content` while it is closed.
*   `active` and :html:`aria-current` on the link of the menu section that is
    in view, where the site loads the scroll spy of Bootstrap.
*   The offset of the sticky navigation below a sticky page header, as its
    :css:`top` and as the custom property
    `--academic-persons-detail-scroll-offset` on the outermost element.

The script is switched off per site with `plugin.tx_academicpersons.assets.js`,
see :ref:`configuration-javascript`. The entries of a profile then stay
closed, with their :html:`hidden` attribute, unless the project brings a
script of its own. The lists load no script.

..  _styling-example:

An example
==========

Rules in the stylesheet of the site package, scoped by the outermost element:

..  code-block:: css

    .academic-persons-list .ace-alphabet-navigation .ace-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        list-style: none;
        padding-left: 0;
    }

    .academic-persons-list .ace-list-item.disabled .ace-link {
        opacity: 0.5;
    }

    .academic-persons-detail .ace-navigation .ace-link.active {
        font-weight: 600;
    }

    .academic-persons-detail .ace-accordion-item .ace-toggle[aria-expanded="true"] .ace-open,
    .academic-persons-detail .ace-accordion-item .ace-toggle[aria-expanded="false"] .ace-close {
        display: none;
    }

The development instances style this markup with the partials
`_academic-persons.scss
<https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-persons.scss>`__,
the profile detail, and `_academic-persons-list.scss
<https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-persons-list.scss>`__,
the lists and cards, of the site package `academics_dev_site`, a complete
example to copy from.

To change the markup itself, override the partials as described in
:ref:`templates`.
