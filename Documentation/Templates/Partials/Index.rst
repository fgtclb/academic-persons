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
gained a class and the argument `activeListArguments`. The letter navigation
gained both as well, and `alphabetFilterLetters` next to them; a project copy
of either keeps working and renders the links it rendered before - see
:ref:`the letter navigation <templates-letter-navigation>` and
:ref:`what the navigation links carry <templates-navigation-links>` below.

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

..  _templates-contract-selection:

Which contracts an item renders
-------------------------------

:file:`Profile/Contract/Item.html` renders the one contract a caller passes as
`contract` - the selected contracts element and
`EXT:academic_contacts4pages` do. Otherwise it renders the contracts the
element's options select (see :ref:`configuration-contract-display`), through
the ViewHelper :html:`<persons:contracts>`. The detail view's
:file:`Profile/PublicProfile/Position.html` and
:file:`Profile/PublicProfile/Contact.html` use it with their block of
:file:`Settings.yaml` instead:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Partials/Profile/Contract/Item.html

    <html
        data-namespace-typo3-fluid="true"
        xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
        xmlns:persons="http://typo3.org/ns/FGTCLB/AcademicPersons/ViewHelpers"
    >
    <f:if condition="{contract}">
        <f:then>
            <p class="my-contract">{contract.position}</p>
        </f:then>
        <f:else>
            <f:for each="{persons:contracts(profile: profile, settings: settings)}" as="contract">
                <p class="my-contract">{contract.position}</p>
            </f:for>
        </f:else>
    </f:if>
    </html>

Keep the `contract` branch in a copy: without it, the selected contracts element
and `EXT:academic_contacts4pages` would show the profile's contracts instead of
the chosen one.

..  code-block:: html
    :caption: The detail view, one block

    <f:for each="{persons:contracts(profile: profile, detailBlock: publicProfile.details.contact)}" as="contract">
        ...
    </f:for>

`settings` are the plugin settings, `detailBlock` a block of
`publicProfile.details`; a passed block wins. Without either, every contract is
returned. The ViewHelper also limits the page cache lifetime while a validity
option applies, which is why a template should call it rather than filter
:html:`{profile.contracts}` itself: a copy that loops over
:html:`{profile.contracts}` keeps working, and ignores the options.

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
    *   -   :file:`Profile/ViewMode/<Mode>.html`
        -   The profiles, in the :ref:`view mode <templates-view-modes>` of the
            element - once per group of a grouped list
        -   depends on the mode
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

..  _templates-navigation-links:

What the navigation links carry
-------------------------------

The list action assigns `activeListArguments`: the choices of the visitor the
list is shown with, as the plugin accepted them - the page while it is not the
first, the letter while one is selected. A value of the request the plugin does
not accept from a visitor is never part of it, nor is a value the content
element sets, such as the sorting. The list template passes it to the letter
navigation, and :file:`Profile/List/ItemList.html` to the page navigation.

Both navigations build every link from it and change only what the link is
responsible for, through the view helper `persons:listArguments`:

..  code-block:: html
    :caption: A page link and a letter link

    <html
        xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
        xmlns:persons="http://typo3.org/ns/FGTCLB/AcademicPersons/ViewHelpers"
        data-namespace-typo3-fluid="true"
    >

    <f:link.action
        arguments="{demand: '{persons:listArguments(arguments: activeListArguments, overrides: {currentPage: page})}'}"
    >{page}</f:link.action>

    <f:link.action
        arguments="{demand: '{persons:listArguments(arguments: activeListArguments, overrides: {alphabetFilter: letter}, remove: \'currentPage\')}'}"
    >{letter}</f:link.action>

`overrides` sets values, `remove` drops the comma separated keys it names. A
letter link and :guilabel:`A-Z` drop the page, so a new letter starts on the
first page.

Besides page and letter, a visitor chooses the :ref:`view mode
<templates-view-modes>`, where the element offers the switch; a link carries it
while it differs from the default. It reaches `activeListArguments` like the
other two, and both navigations carry it without an edit. A project copy of
either partial, and a list template that does not pass `activeListArguments` on,
keeps the links it has and drops the view mode on the next click, until it
adopts the view helper as above.

:file:`Profile/List/Items.html`, the grid the view mode ``list`` renders,
carries the Bootstrap row and column classes, so overriding it changes the grid
of all four elements **of this extension** at once. It takes either `profiles`
or `contracts`: the selected contracts element passes the latter, because it
shows one item per selected contract, and each of those items renders that one
contract rather than every contract of its profile.

:file:`Profile/List/EmptyState.html` is rendered by all four as well. The
selected contracts element passes the label about contracts to it through its
`key` argument; every other caller takes the default.

..  note::

    The contacts element of `EXT:academic_contacts4pages` is **not** one of the
    four. It renders :file:`Profile/Item` through a partial of its own,
    :file:`Contacts/Item.html`, inside a grid of its own, and it has no empty
    state at all - a page without contacts renders nothing. Overriding
    :file:`Profile/List/Items.html` therefore does not change how its contacts
    are arranged; overriding :file:`Profile/Item.html` or one of the item
    partials does change how each of them looks, and a project that wants a
    card of its own for the contacts only overrides :file:`Contacts/Item.html`
    of that extension instead.

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

..  _templates-view-modes:

View modes
==========

The list, list-and-detail, selected profiles and selected contracts elements
render their profiles through the partial of their :ref:`view mode
<configuration-view-modes>`, :file:`Profile/ViewMode/<Mode>.html` - the mode with
an upper case first letter, :file:`ContactCards.html` for ``contactCards``. The
action assigns the mode as `viewMode` and the partial name
as `viewModePartial`; the list renders the partial from
:file:`Profile/List/ItemList.html`, the selected profiles and contracts from
their templates.

..  list-table::
    :header-rows: 1

    *   -   Partial
        -   Renders
        -   Class
    *   -   :file:`Profile/ViewMode/List.html`
        -   The mode ``list``, labelled :guilabel:`Tiles`: the grid of
            :file:`Profile/List/Items.html`
        -   those of the grid
    *   -   :file:`Profile/ViewMode/Table.html`
        -   The mode ``table``: a header row, then one row per profile - per
            contract in the selected contracts element - with one cell per
            entry of `settings.table.columns`
        -   `academic-persons-table`, next to Bootstrap's `table-responsive`
    *   -   :file:`Profile/ViewMode/Table/Cell.html`
        -   One cell of the table, by its column
        -   none
    *   -   :file:`Profile/ViewMode/Switch.html`
        -   The switch between the allowed modes, only when the content element
            offers it
        -   `academic-persons-view-mode-switch`

Every mode partial receives the same arguments: `profiles`, or `contracts` in
the selected contracts element, `settings`, `data`, `class` from the list and
`groupedProfiles` from a grouped list. `class` is the grid's own
`academic-persons-itemlist`; the table does not take it, so a stylesheet of the
grid does not reach the table.

The switch renders one link per allowed mode - nothing while fewer than two
modes are allowed - with :html:`rel="nofollow"`, and marks the active one with
:html:`aria-current="true"`. Its :html:`<nav>` is named by the label
`list.viewMode.navigation`, a mode by `list.viewMode.<mode>` of this extension -
or by its name, for a mode without a label. In the list elements a link keeps
`activeListArguments` and changes only the mode; the link to the default mode
carries no mode, and is the page itself when the visitor chose nothing else.

A project copy of :file:`Profile/List/ItemList.html`,
:file:`Templates/Profile/SelectedProfiles.html` or
:file:`Templates/Profile/SelectedContracts.html` keeps rendering the grid
whatever the mode; a copy of :file:`Templates/Profile/List.html` has no switch.

..  _templates-view-modes-own:

Adding a view mode
------------------

A view mode of your own needs no template override. Taking the mode ``contact``:

1.  Provide the partial :file:`Profile/ViewMode/Contact.html` in a partial path
    of the plugin, such as that of your site package.
2.  Allow the mode: the site setting
    :typoscript:`plugin.tx_academicpersons.viewMode.allowed` becomes
    ``list,table,contact``, see :ref:`configuration-view-modes`.
3.  Offer it to the editor as an item of :guilabel:`View Mode Default`, in page
    TSconfig, for each element that shall have it:

    ..  code-block:: typoscript
        :caption: EXT:my_sitepackage/Configuration/page.tsconfig

        TCEFORM.tt_content.pi_flexform {
          academicpersons_list.sDEF.settings\.viewMode\.default.addItems.contact = Contact cards
          academicpersons_listanddetail.sDEF.settings\.viewMode\.default.addItems.contact = Contact cards
        }

The switch offers the mode as soon as it is allowed; label it with
`list.viewMode.contact` in a language override of this extension. A mode that is
allowed without a partial fails with Fluid's error for a missing partial. Its
links carry it as a query argument until it is added to the route enhancer, see
:ref:`a view mode of your own in the URL
<configuration-route-enhancers-view-modes>`.

To add a column to the table, override :file:`Profile/ViewMode/Table/Cell.html`,
render your column there and keep the shipped ones; a column the partial does
not know renders an empty cell. Its header is the label `list.table.<column>`,
or the column's name without one.

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
