..  index:: Templates; Item and list partials
..  _templates-item-and-list-partials:

======================
Item and list partials
======================

The profile item and the profile list are built from small partials. An
integrator changes one aspect of them by putting one file of a few lines into
the configured partial root path, instead of copying a whole template and
maintaining it against every later correction.

The entry points stay :file:`Profile/Item.html`,
:file:`Profile/List/ItemList.html`, :file:`Profile/List/Pagination.html` and
:file:`Profile/List/AlphabetPagination.html`. The first two delegate to the
partials below, with unchanged arguments. The page navigation is a leaf that
only gained a class, and a project copy of it loses nothing. The letter
navigation gained an argument as well, `alphabetFilterLetters`; a project copy
of it keeps working and renders every letter as a link, as before - see
:ref:`the letter navigation <templates-letter-navigation>` below.

An existing copy of the item or the list body keeps working - and stops
profiting from the partials below, because a copy does not render them.

..  _templates-item-partials:

The item
========

:file:`Partials/Profile/Item.html` is rendered by the list, list-and-detail,
card, selected-profiles and selected-contracts elements, and by the contacts
element of `EXT:academic_contacts4pages`. It renders:

..  list-table::
    :header-rows: 1

    *   -   Partial
        -   Renders
        -   Class
    *   -   :file:`Profile/Item/DetailLink.html`
        -   The address of the detail view, as text
        -   none, it renders no element
    *   -   :file:`Profile/Item/Name.html`
        -   The academic title and the names, as text
        -   `academic-persons-item__name`, on the heading
    *   -   :file:`Profile/Item/Contracts.html`
        -   The contract block, through :file:`Profile/Contract/Item.html`
        -   none, the list element belongs to that partial
    *   -   :file:`Profile/Item/Image.html`
        -   The image, through the shared partial of `EXT:academic_base`
        -   `academic-persons-item__image`

..  _templates-item-partials-text:

Two of them render text, not markup
-----------------------------------

:file:`Profile/Item/Name.html` and :file:`Profile/Item/DetailLink.html` produce
a **value**. The item hands it to :file:`Profile/Header.html` or
:file:`Profile/SectionHeader.html`, which put it inside their heading element -
and escape it there, once, on the way out.

An override of either therefore renders the profile values raw:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Partials/Profile/Item/Name.html

    <html
        data-namespace-typo3-fluid="true"
        xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
    >
    <f:format.raw>{profile.lastName}</f:format.raw>, <f:format.raw>{profile.firstName}</f:format.raw>
    </html>

Without :html:`<f:format.raw>` the value is escaped twice, and a name such as
`O'Neill` reaches the browser as the markup `O&amp;#039;Neill`, which a visitor
reads as `O&#039;Neill`. Markup written in one of these two partials arrives as
literal text for the same reason - the heading element is what the header
partials own.

..  warning::

    These two partials render unescaped text, so they are only safe where
    something else escapes them: as an argument of a partial that writes them
    into an element, which is what :file:`Profile/Item.html` does. Never render
    one of them in an output position of a template - :html:`{f:render(partial:
    'Profile/Item/Name', ...)}` written into markup would emit the stored profile
    values unescaped.

Every other partial on this page renders markup and is written as any Fluid
partial is.

..  _templates-item-partials-detail-page:

Passing the detail page
-----------------------

:file:`Profile/Item/DetailLink.html` builds the address from
`plugin.tx_academicpersons.detailPid`, or stays on the current page for the
list-and-detail element, which shows the detail view itself.

A template that renders an item without those plugin settings passes the page
instead. A passed page wins over both branches, the list-and-detail one
included:

..  code-block:: html

    <f:render
        partial="Profile/Item"
        arguments="{
            profile: profile,
            settings: settings,
            data: data,
            detailPid: 42
        }"
    />

..  _templates-list-partials:

The list
========

:file:`Partials/Profile/List/ItemList.html` renders:

..  list-table::
    :header-rows: 1

    *   -   Partial
        -   Renders
        -   Class
    *   -   :file:`Profile/List/ResultCount.html`
        -   Nothing
        -   none, it renders no element
    *   -   :file:`Profile/List/GroupHeader.html`
        -   The heading above one group of a grouped list
        -   `academic-persons-list__group-header`, on the heading
    *   -   :file:`Profile/List/Items.html`
        -   The grid of items
        -   `academic-persons-grid`, `academic-persons-grid__item`
    *   -   :file:`Profile/List/Pagination.html`
        -   The page navigation
        -   `academic-persons-list__pagination`
    *   -   :file:`Profile/List/EmptyState.html`
        -   The text shown when nothing was found
        -   `academic-persons-empty-state`

..  _templates-letter-navigation:

:file:`Profile/List/AlphabetPagination.html` is the sixth, and the one
:file:`ItemList.html` does not render: the list template
:file:`Templates/Profile/List.html` renders it above the list body, when
`settings.alphabetPaginationEnabled` is switched on and no profiles are
selected by hand - a manual selection ignores the letter filter. It carries
`academic-persons-list__alphabet-pagination` next to the
`alphabetical-pagination` it had.

The list template passes it `alphabetFilterLetters` next to `demand`: every
letter from `a` to `z`, mapped to whether the list holds a profile under it
(see :ref:`developers-letter-availability`). The partial renders

*   a letter with profiles as a link, in a plain `li.page-item`;
*   a letter without profiles as `li.page-item.disabled` holding a
    `span.page-link` - no link - with a `visually-hidden` "no profiles" for
    assistive technology;
*   the selected letter as `li.page-item.active` with
    :html:`aria-current="page"`, and as a link back to the list without a letter
    - with a `visually-hidden` "show all profiles" - only when
    :ref:`the reset setting <configuration-letter-navigation>` is on;
*   :guilabel:`A-Z` as `li.page-item.active` with :html:`aria-current="page"`
    while no letter is selected.

The :html:`<nav>` is named by the label `list.alphabetFilter.navigation`. A
list template of a project that renders this partial without
`alphabetFilterLetters` gets every letter as a link, as before.

:file:`Profile/List/Items.html` carries the Bootstrap row and column classes, so
overriding it changes the grid of all four elements **of this extension** at
once. It takes either `profiles` or `contracts`: the selected contracts element
passes the latter, because it shows one item per selected contract, and each of
those items renders that one contract rather than every contract of its profile.

:file:`Profile/List/EmptyState.html` is rendered by all four as well. The
selected contracts element passes the label about contracts to it through its
`key` argument; every other caller takes the default.

..  note::

    The contacts element of `EXT:academic_contacts4pages` is **not** one of the
    four. It renders :file:`Profile/Item` directly, inside a grid of its own, and
    it has no empty state at all - a page without contacts renders nothing.
    Overriding :file:`Profile/List/Items.html` therefore does not change how its
    contacts are arranged; overriding :file:`Profile/Item.html` or one of the
    item partials does change how each of them looks.

Both classes are blocks of their own rather than parts of
`academic-persons-list`: the grid and the empty state sit inside four different
blocks, so naming them after one of them would be wrong in the other three. The
list adds its own `academic-persons-itemlist` to the grid, which is the class it
carried before.

..  _templates-list-partials-result-count:

The result count is an empty hook
---------------------------------

:file:`Profile/List/ResultCount.html` renders nothing on purpose - printing a
number by default would change every list that exists. It receives the number of
profiles rendered below it as `count`, the number the list found as `total` -
the same one unless the list paginates - those profiles as `profiles`, and the
`paginator` of a paginated list for its page number and page count:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Partials/Profile/List/ResultCount.html

    <html
        data-namespace-typo3-fluid="true"
        xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
    >
    <p class="academic-persons-result-count">{count} of {total}</p>
    </html>

`total` is counted in :file:`ItemList.html` rather than read from the paginator
on purpose: `getTotalAmountOfItems()` is protected on every core version this
extension supports, so Fluid cannot reach it and
`{paginator.totalAmountOfItems}` renders an empty string.

..  _templates-partials-contacts4pages:

Contacts for pages resolves the partials separately
===================================================

`EXT:academic_contacts4pages` renders :file:`Profile/Item` through partial root
paths of its own. An override registered for the persons plugins alone does not
reach it, so register the same path twice:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicpersons.view.partialRootPath = EXT:my_sitepackage/Resources/Private/Extensions/academic_persons/Partials/
    plugin.tx_academiccontacts4pages.view.partialRootPath = EXT:my_sitepackage/Resources/Private/Extensions/academic_persons/Partials/

That has always been true for a copy of the item; it is worth repeating here
because a small override makes it easy to forget one of the two lines.
