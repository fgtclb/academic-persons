..  _integrator-person-list:

===========
Person List
===========

..  image:: /Images/Icons/persons.svg
    :width: 48px
    :alt: Icon of the profile list

:CType:
    ``academicpersons_list``

:Plugin:
    `AcademicPersons`, plugin `List`, actions
    :php:`ProfileController::listAction()` and
    :php:`ProfileController::filterAction()`

:Set:
    `fgtclb/academic-persons-list`, or the aggregate set
    `fgtclb/academic-persons`

:Templates:
    :file:`Templates/Profile/List.html`, see :ref:`templates`

The directory of a faculty, an institute or a whole institution: the profiles
of one or more storage folders, optionally restricted to organisational units
or function types, grouped by letter, paginated and filtered by the visitor.
It is also the element for a short list of hand-picked profiles that should
behave like a directory, with pagination and view modes.

The names link to the detail view of a profile on another page, which holds a
:ref:`Person Details <integrator-person-details>` element. A site that wants the
list and the profile on one page uses :ref:`Person List and Details
<integrator-person-list-and-details>` instead. The partials that render the
list are described in :ref:`templates-list-partials`, its classes in
:ref:`styling-list`, its URLs in :ref:`configuration-route-enhancers`.

..  figure:: /Images/Screenshots/person-list-default.png
    :alt: Profile list with two filter selects and a filter button, the links Tiles and Table, and letter headings above profile tiles with name, position, function type, unit, e-mail and phone
    :class: with-border with-shadow

    A profile list with the visitor filters, the view mode switch and tiles
    grouped by the first letter of the last name.

..  _integrator-person-list-configuration:

Settings
========

The tab :guilabel:`Configuration` holds the plugin options, in this order, and
below them the :guilabel:`Startingpoint`.

..  confval:: PageTitle display format
    :name: person-list-page-title-format
    :type: text, with a value picker
    :Default: empty

    Has no effect in this element. The page title is set by the element that
    shows the profile, so set the format on the :ref:`Person Details
    <integrator-person-details>` element of the detail page. The field exists
    here because the list shares its options with the list-and-detail element.

..  confval:: Detail Page
    :name: person-list-detail-pid
    :type: page
    :Default: empty

    The page the names link to. It has to hold a :guilabel:`Person Details`
    element. Empty uses the site setting
    :typoscript:`plugin.tx_academicpersons.detailPid`, and while that is ``0``
    as well, the names link to the page of the list itself, with the arguments
    of the detail view. That only works when a :guilabel:`Person Details`
    element sits on the same page.

..  confval:: Link the names to the detail view
    :name: person-list-detail-link
    :type: select
    :Default: Use the site setting

    :guilabel:`Use the site setting` follows
    :ref:`the site setting <configuration-detail-link>`, which links unless the
    site sets it to :guilabel:`No link`. :guilabel:`Link to the detail view`
    and :guilabel:`No link` decide for this element. A chosen value wins over
    the site setting in both directions: a site without detail pages links one
    list that has them, and a site with detail pages shows one list without
    links. :guilabel:`No link` shows the names as text, in the table view mode
    as well.

..  confval:: Group By (is given priority in sorting if active)
    :name: person-list-group-by
    :type: select
    :Default: No grouping

    :guilabel:`First Letter of Last Name` or :guilabel:`First Letter of First
    Name` puts a heading with the letter above each group of profiles, as in a
    telephone directory. The groups are always in ascending order, and
    :guilabel:`Sort By` orders the profiles within a group. The items come from
    the extension configuration `demand.allowedGroupByValues`.

    The list is grouped page by page: in a paginated list, a group that
    continues on the next page gets its heading there again. A list of
    :guilabel:`List of profiles` is grouped in the order the selection meets
    the letters, so leave grouping off for a selection.

..  confval:: Sort By (sorting of activated grouping has priority)
    :name: person-list-sort-by
    :type: select
    :Default: No sorting

    :guilabel:`Last Name` or :guilabel:`First Name`. :guilabel:`No sorting`
    lists the profiles in the order they were created. The items come from the
    extension configuration `demand.allowedSortByValues`. A list of
    :guilabel:`List of profiles` ignores the field and keeps the order of the
    selection.

..  confval:: Sort By Direction
    :name: person-list-sort-by-direction
    :type: select
    :Default: Ascending

    :guilabel:`Ascending` or :guilabel:`Descending`, for :guilabel:`Sort By`
    only. It does not reverse the groups.

..  confval:: Pagination
    :name: person-list-pagination
    :type: checkbox
    :Default: off

    Splits the list into pages of :guilabel:`Results per Page` profiles, with a
    page navigation below the list. While the visitor has selected a letter of
    the letter navigation, the list is not paginated and shows every profile of
    that letter.

..  confval:: Alphabetical Pagination
    :name: person-list-alphabetical-pagination
    :type: checkbox
    :Default: off

    Shows the letter navigation above the list: :guilabel:`A-Z` and one entry
    per letter, which narrows the list to the profiles whose last name starts
    with it. A letter without profiles in this list is shown disabled and is
    not a link. What the selected letter does is a site setting, see
    :ref:`configuration-letter-navigation`. The navigation is left out for a
    list of :guilabel:`List of profiles`.

..  figure:: /Images/Screenshots/person-list-letter-navigation.png
    :alt: Row of links A-Z and A to Z, some letters greyed out, above the group headings of the first letters
    :class: with-border with-shadow

    The letter navigation: letters without profiles are disabled.

..  confval:: Results per Page
    :name: person-list-results-per-page
    :type: text
    :Default: empty

    The number of profiles on one page. Empty or ``0`` uses the site setting
    :typoscript:`plugin.tx_academicpersons.pagination.resultsPerPage`, ``10``
    by default. A negative number here, or a site setting below ``1``, shows
    ``10`` profiles per page.

..  confval:: Number of Links
    :name: person-list-number-of-links
    :type: text
    :Default: empty

    How many page numbers the page navigation links at most. It only applies
    while :composer:`georgringer/numbered-pagination` is installed. Without it,
    the navigation links every page. Empty or ``0`` uses the site setting
    :typoscript:`plugin.tx_academicpersons.pagination.numberOfLinks`, ``5`` by
    default. A negative number here, or a site setting below ``1``, links ``5``
    pages.

..  figure:: /Images/Screenshots/person-list-pagination.png
    :alt: Page navigation on the second page with the links first and previous and the page numbers 1 and 2
    :class: with-border with-shadow

    The page navigation of a paginated list, on its second page.

..  confval:: List of profiles
    :name: person-list-list-of-profiles
    :type: records
    :Default: empty

    Profiles picked by hand. The list then shows exactly these profiles, in the
    order they are picked. :guilabel:`Sort By`, the filters for visitors, the
    letter navigation and the restriction to :guilabel:`Organisational Units`
    and :guilabel:`Function Types` no longer decide which profiles are listed.
    Pagination, view modes, the contract options, :guilabel:`Show hidden
    records` and the language fallback still apply.

    The :guilabel:`Startingpoint` applies as well: while it is set, a picked
    profile stored outside of it is not shown. For a selection that needs
    neither pagination nor a letter navigation,
    :ref:`Selected Person’s Profiles <integrator-selected-profiles>` is the
    simpler element.

..  confval:: View Mode Toggle
    :name: person-list-view-mode-toggle
    :type: checkbox
    :Default: off

    Offers the visitor a switch between the allowed view modes above the list,
    when at least two are allowed. The active mode is marked as current, and
    the pagination and the letter navigation keep the chosen mode. Off, a mode
    the request asks for is ignored and :guilabel:`View Mode Default` renders.

    Which modes exist is the site setting
    :ref:`plugin.tx_academicpersons.viewMode.allowed
    <configuration-view-modes>`.

..  confval:: View Mode Default
    :name: person-list-view-mode-default
    :type: select
    :Default: Tiles

    The mode the list renders: :guilabel:`Tiles`, a grid of profile items, or
    :guilabel:`Table`, one row per profile with the columns of the site setting
    :ref:`plugin.tx_academicpersons.table.columns <configuration-view-modes>`.
    In a grouped list, every group is a table of its own. The stored value of
    the tiles is ``list``, so an element saved before the field existed, or
    without it, renders the tiles. A default the site does not
    allow falls back to the tiles, or to the first allowed mode where the tiles
    are not allowed. A mode of a project is offered here through page TSconfig,
    see :ref:`templates-view-modes-own`.

..  figure:: /Images/Screenshots/person-list-table.png
    :alt: Table with columns name, position, e-mail, phone and room for each letter group, below the view mode switch
    :class: with-border with-shadow

    The table view mode, one table per letter group, with the columns of the
    site setting :typoscript:`plugin.tx_academicpersons.table.columns`.

..  confval:: Fallback to default language for non translated profiles
    :name: person-list-fallback-for-non-translated
    :type: checkbox
    :Default: on

    On a translated page, also lists the profiles that are not translated into
    its language, in the default language. Off, the fallback type of the site
    language decides, and a language with :yaml:`fallbackType: strict` lists
    only the translated profiles. For a list of :guilabel:`List of profiles`
    on TYPO3 v13 see
    :ref:`important-88886-strict-language-fallback-selected-profiles`.

..  confval:: Show only selected Fields
    :name: person-list-show-fields
    :type: select, several values
    :Default: empty

    The fields each profile item shows: the :guilabel:`Image` of the profile,
    and the :guilabel:`Position`, :guilabel:`Function Type`,
    :guilabel:`Organisational Unit`, :guilabel:`Room`, :guilabel:`Office
    hours`, :guilabel:`Addresses`, :guilabel:`Email Addresses`,
    :guilabel:`Phone Numbers` and :guilabel:`Location / Campus` of each shown
    contract, in the order of the right-hand list. The name is always shown.
    Empty shows the image and every contract field except the function type
    and the organisational unit, which appear only when they are selected.

    The table view mode follows the selection: a contract column whose field is
    not selected, ``contracts.room`` for the column ``room``, is left out. A
    listener of :php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent`
    can add items, which a template of the project then renders.

..  confval:: Organisational Units
    :name: person-list-organisational-units
    :type: select, several values
    :Default: empty

    Restricts the list to the profiles with a contract in one of the selected
    units, for the directory of one faculty or institute. A contract in a unit
    below a selected one does not count. Empty restricts nothing. The selected
    units are also the choices of the
    :ref:`filter for visitors <configuration-visitor-filters>`.

    With :guilabel:`Function Types` selected as well, both have to match the
    same contract: a person who is a professor in one unit and a lecturer in
    another is not listed among the professors of the second one. Without
    :guilabel:`Only contracts of the selected organisational units and function
    types`, the restriction selects the profiles, and every contract of a
    listed profile is shown.

..  confval:: Function Types
    :name: person-list-function-types
    :type: select, several values
    :Default: empty

    The same restriction for function types, for a list of the professors or
    of the staff of a unit.

..  confval:: Visitors may filter by function type
    :name: person-list-filter-function-type
    :type: checkbox
    :Default: off

    Shows a choice of function types above the list, see
    :ref:`configuration-visitor-filters`.

..  confval:: Visitors may filter by organisational unit
    :name: person-list-filter-organisational-unit
    :type: checkbox
    :Default: off

    The same for organisational units.

..  confval:: Contracts per profile
    :name: person-list-contracts-display
    :type: select
    :Default: All contracts

    :guilabel:`All contracts`, or :guilabel:`Only the first contract` that the
    two options below leave, in the order the editor gave the contracts of the
    profile. See :ref:`configuration-contract-display`.

..  confval:: Only contracts of the selected organisational units and function types
    :name: person-list-contracts-match-filter
    :type: checkbox
    :Default: off

    Leaves out the contracts of a listed profile in other units or with other
    function types than the list is restricted to. Without such a restriction
    it has no effect. See :ref:`configuration-contract-display`.

..  confval:: Only contracts valid today
    :name: person-list-contracts-only-valid
    :type: checkbox
    :Default: off

    Leaves out the contracts that have ended or not started yet, and selects
    profiles only through a contract that is valid today. See
    :ref:`configuration-contract-display` and
    :ref:`configuration-contract-display-cache`.

..  confval:: Show hidden records
    :name: person-list-show-hidden-records
    :type: checkbox
    :Default: off

    Lists the profiles whose :guilabel:`Visible` switch is off as well, for an
    internal directory on a page restricted to a frontend user group. Switch it
    on in the detail element as well, or the names of hidden profiles lead to
    the error page. See :ref:`configuration-hidden-profiles`.

..  confval:: Startingpoint
    :name: person-list-startingpoint
    :type: pages
    :Default: empty

    The folders the profiles are stored in. Their subfolders are not included,
    so select every folder. Empty lists the profiles of the whole installation,
    of every site.

..  _configuration-visitor-filters:

Filters for visitors
====================

A visitor can narrow the list and list-and-detail elements to one function type
or one organisational unit, where the element allows it. Two fields of the
content element, :guilabel:`Visitors may filter by function type` and
:guilabel:`Visitors may filter by organisational unit`, switch the filters on.
Both are off by default, and a list that does not switch them on shows what it
showed before:

:guilabel:`Visitors may filter by function type`
    The list takes a function type from the request and shows only the profiles
    with a contract of that type.

:guilabel:`Visitors may filter by organisational unit`
    The same for an organisational unit. The unit has to match exactly, a
    contract in a unit below it does not count.

..  figure:: /Images/Screenshots/person-list-visitor-filters.png
    :alt: Two selects, function type set to Professor and organisational unit, and a filter button above the filtered profiles
    :class: with-border with-shadow

    The filters for visitors, with a function type chosen.

With both filters set, the function type and the unit have to be on the same
contract: a person who is a professor in one unit and a lecturer in another is
not listed among the professors of the second one. A profile with several
matching contracts is listed once, and the pagination and the letter
navigation count only the profiles the filter leaves.

The options of a filter are the function types or units the element is
restricted to in :guilabel:`Function Types` and :guilabel:`Organisational
Units`, or all of them while it is restricted to none, ordered by name. A
visitor can therefore never see more than the editor chose. The list ignores a
value that is not one of the options: a number of a record that does not exist
or is hidden, a record outside the restriction, anything that is not a whole
number, and any value while the field is off. It then renders as if no filter
had been given. A manual selection of profiles ignores both filters.

The request carries the uid of the record in the default language, as the
demand arguments `functionTypeFilter` and `organisationalUnitFilter` of the
plugin. The list renders a form above the profiles with one choice per
filter, and its submission leads to the URL of the filtered list, see
:ref:`configuration-route-enhancers-filters`. The list template receives the
options as `filterOptions`, see :ref:`templates-visitor-filters`. The page and
letter links keep an active filter.

The filters select profiles, and do not change which contracts of a profile are
shown. :guilabel:`Only contracts of the selected organisational units and
function types` applies the restriction of the element, not the choice of the
visitor. While :guilabel:`Only contracts valid today` is on, a filter finds a
profile only through a contract valid today, see
:ref:`configuration-contract-display`.

The card element shares the fields with the list and hides both.

..  _configuration-contract-display:

Which contracts a profile shows
===============================

A profile has one or more contracts, sorted by the editor. Every view shows all
of them unless it is told otherwise.

**The list and list-and-detail elements** offer three fields in their plugin
options, below the :ref:`filters for visitors
<configuration-visitor-filters>`:

:guilabel:`Contracts per profile`
    All contracts (the default), or only the first one.

:guilabel:`Only contracts of the selected organisational units and function types`
    Leaves out the contracts of a profile in other units or with other function
    types than the element is restricted to. Without such a restriction the
    option has no effect. Without the option, the restriction selects the
    profiles and every contract of a selected profile is shown, as before.

:guilabel:`Only contracts valid today`
    Leaves out the contracts that have ended or not started yet. While the
    element is restricted to organisational units or function types, or a
    visitor filters it, the option also decides which contracts select a
    profile: only one valid today does. A profile whose only matching contract
    has ended is not listed, rather than listed without that contract, and the
    pagination and the letter navigation count the same profiles. A list
    without such conditions selects nobody by a contract and still lists a
    profile whose contracts have all ended, without a contract.

**The card and selected-profiles elements** offer :guilabel:`Contracts per
profile` and :guilabel:`Only contracts valid today`. They select their profiles
by hand and apply no unit or function type restriction, so there is nothing for
the contracts to match. The card hides the field in its form. A value stored
while the element was a list stays in effect after a switch to the card, as a
detail page stored while it was a list does.

**The detail view**, of the detail and the list-and-detail elements, is
configured in :file:`Settings.yaml`, once for the installation rather than per
content element. Its two blocks rendered from the contracts take a key each for
the same two choices, see :ref:`configuration-sections-profile-contracts`:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicPersons/Settings.yaml

    profile:
      details:
        position:
          onlyValid: true
        contact:
          contracts: first

**The selected-contracts element** and the contacts element of
:guilabel:`EXT:academic_contacts4pages` show the contract that was chosen,
whatever the options say, even one that has ended.

The options apply in a fixed order: the unit and function type filter, then
validity, then "first". "Only the first" is the first of the contracts left, in
the editor's order of the profile's contracts, so a list restricted to one unit
shows each profile's first contract in that unit.

Validity is a matter of days. A contract is valid from the first day of
:guilabel:`Valid from` through the last day of :guilabel:`Valid to`. An empty
date does not limit it on that side, and no setting changes that. "Today" is the
date the page is rendered for, in the time zone of the installation. A date
simulated in the frontend preview of a backend user counts.

..  _configuration-contract-display-cache:

The page cache follows the validity
-----------------------------------

A cached page would keep showing a contract that ended yesterday until its cache
entry expires. On TYPO3 v13 a page with profiles expires after 24 hours at the
latest, whatever :typoscript:`config.cache_period` says, because every record
Extbase loads limits the page to that. On TYPO3 v14 a page without
:typoscript:`config.cache_period` is cached for a year. So while "only contracts
valid today" applies, a page is cached no longer than until the next midnight on
which a contract that passes the unit and function type filter ends, the day
after its :guilabel:`Valid to`, or starts. While the option also decides
which contracts select the profiles of a list, the same holds for every
contract that meets the restriction and the visitor filters of the list, so a
profile whose contract starts tomorrow is listed tomorrow, though nothing of it
was rendered today. Those contracts are read from the storage pages of the
list. The limit is set only on the cache entry of the page that rendered those
contracts or the list, only when that date comes before the regular expiry, and
never when no validity option applies.
