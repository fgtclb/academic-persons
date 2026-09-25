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

The site settings of this extension — the detail page, the default grouping,
sorting and pagination of a profile list, the selected letter of the letter
navigation, the image placeholder and the phone link prefix below — are
declared with the aggregate set. A site that depends on
a single component set still gets the shipped defaults, but can only override
them in :guilabel:`Site Settings` when it depends on `fgtclb/academic-persons`.

..  _configuration-phone-link-prefix:

The prefix of a phone link target
=================================

Every phone number this extension renders is a link, and the number is written
into its :html:`tel:` target without the spaces it is stored with. An
installation that stores phone numbers as extensions only — the part that is
the same for every number left out — has no dialable target that way, and this
setting is what completes it.

..  confval:: plugin.tx_academicpersons.phoneNumbers.telPrefix
    :name: plugin-tx-academicpersons-phonenumbers-telprefix
    :type: string
    :Default: (empty)

    Prepended to the :html:`tel:` target of every phone number, in the list,
    card and selection elements, in the profile detail view and in the contacts
    of a page. The spaces of prefix and number alike are removed from the
    target. The visible link text is not touched: it keeps the number as it is
    stored, without the prefix.

    The prefix is applied unconditionally, so an installation that stores full
    numbers, or a mixture of full numbers and extensions, leaves it empty.

The setting is declared for the site set and as a constant of the shared static
template, with the same default in both — see
:ref:`Do not combine both <one-mechanism-per-site>`.

..  _configuration-letter-navigation:

The selected letter of the letter navigation
============================================

The list and list-and-detail elements can show a letter navigation above the
list (:guilabel:`Alphabetical Pagination` in the content element). A letter
without profiles in that list is shown disabled and is not a link, and the
navigation is left out for a list of profiles selected by hand. What the
selected letter does is a setting:

..  confval:: plugin.tx_academicpersons.alphabet.activeLetterResets
    :name: plugin-tx-academicpersons-alphabet-activeletterresets
    :type: boolean
    :Default: 0

    Off, the selected letter is marked as the current one and is not a link.
    On, it is still marked as the current one, and links back to the list
    without a letter — the target of :guilabel:`A-Z`. A visually hidden "show
    all profiles" tells assistive technology where the link leads.

The setting is declared for the site set and as a constant of the shared static
template, with the same default in both.

..  _configuration-view-modes:

View modes
==========

The list, list-and-detail, selected profiles and selected contracts elements
render their profiles in a view mode. Two fields of the content element choose
it:

:guilabel:`View Mode Default`
    The mode the element renders: :guilabel:`Tiles`, the grid every list
    rendered so far, or :guilabel:`Table`. The stored value of the tiles is
    still ``list``, so a content element saved before needs no migration, and
    one saved without the field renders the tiles as well.

:guilabel:`View Mode Toggle`
    Offers the visitor a switch between the allowed modes above the list - when
    at least two are allowed. The active mode is marked as current. Off, a mode
    the request asks for is ignored and the default renders.

The card element shares the fields with the list, hides both, and renders tiles
whatever it stores.

Two settings decide which modes exist and what the table shows:

..  confval:: plugin.tx_academicpersons.viewMode.allowed
    :name: plugin-tx-academicpersons-viewmode-allowed
    :type: string
    :Default: list,table

    The modes the elements may render, comma separated. Neither the default of
    a content element nor the request of a visitor renders a mode outside this
    list: such a default falls back to the tiles - or to the first allowed mode
    where the tiles are not allowed - and such a request to the default. A mode
    is a plain name, a lowercase letter followed by letters and digits; an entry
    of any other shape is ignored.

..  confval:: plugin.tx_academicpersons.table.columns
    :name: plugin-tx-academicpersons-table-columns
    :type: string
    :Default: name,position,emailAddresses,phoneNumbers,room

    The columns of the table, comma separated, in this order. The shipped
    columns are ``name``, ``position``, ``organisationalUnit``,
    ``emailAddresses``, ``phoneNumbers`` and ``room``. The name links to the
    detail view; the contract columns show the value of every contract the
    element shows for the profile, one per line.

    The fields of the content element (:guilabel:`Show only selected Fields`)
    apply to the table as they apply to the tiles: while the element names
    fields there, a contract column whose field it does not name -
    ``contracts.room`` for ``room`` - is left out. The name, and a column of
    your own, stay.

Both settings are declared for the site set and as constants of the shared
static template, with the same default in both. A mode of your own is a partial
and an entry in the allowed modes, see
:ref:`adding a view mode <templates-view-modes-own>`; its URL is described in
:ref:`the route enhancers <configuration-route-enhancers-view-modes>`.

..  _configuration-contract-display:

Which contracts a profile shows
===============================

A profile has one or more contracts, sorted by the editor. Every view shows all
of them unless it is told otherwise.

**The list and list-and-detail elements** offer three fields in their plugin
options, on the sheet :guilabel:`Settings` below :guilabel:`Function Types`:

:guilabel:`Contracts per profile`
    All contracts (the default), or only the first one.

:guilabel:`Only contracts of the selected organisational units and function types`
    Leaves out the contracts of a profile in other units or with other function
    types than the element is restricted to. Without such a restriction the
    option has no effect. Without the option, the restriction selects the
    profiles and every contract of a selected profile is shown, as before.

:guilabel:`Only contracts valid today`
    Leaves out the contracts that have ended or not started yet.

**The card and selected-profiles elements** offer :guilabel:`Contracts per
profile` and :guilabel:`Only contracts valid today`. They select their profiles
by hand and apply no unit or function type restriction, so there is nothing for
the contracts to match. The card hides the field in its form; a value stored
while the element was a list stays in effect after a switch to the card, as a
detail page stored while it was a list does.

**The detail view** - of the detail and the list-and-detail elements - is
configured in :file:`Settings.yaml`, once for the installation rather than per
content element. Its two blocks rendered from the contracts take a key each for
the same two choices; see :ref:`configuration-sections-profile-contracts`:

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
:guilabel:`Valid from` through the last day of :guilabel:`Valid to`; an empty
date does not limit it on that side, and no setting changes that. "Today" is the
date the page is rendered for, in the time zone of the installation - a date
simulated in the frontend preview of a backend user counts.

..  _configuration-contract-display-cache:

The page cache follows the validity
-----------------------------------

A cached page would keep showing a contract that ended yesterday until its cache
entry expires. On TYPO3 v13 a page with profiles expires after 24 hours at the
latest, whatever :typoscript:`config.cache_period` says, because every record
Extbase loads limits the page to that; on TYPO3 v14 a page without
:typoscript:`config.cache_period` is cached for a year. So while "only contracts
valid today" applies, a page is cached no longer than until the next midnight on
which a contract that passes the unit and function type filter ends - the day
after its :guilabel:`Valid to` - or starts. The limit is set only on the cache
entry of the page that rendered those contracts, only when that date comes
before the regular expiry, and never when no validity option applies.

..  _configuration-contract-select-storage-scope:

Restrict the backend contract selects
=====================================

Two backend fields let an editor pick a contract: the :guilabel:`Contract` of a
page contact record of :guilabel:`EXT:academic_contacts4pages`, and
:guilabel:`Selected contracts` of the :guilabel:`Profiles: Selected Contracts`
content element. Both offer every contract of the installation — in an
installation with more than one site, that is every site's contracts, each
labelled with a person's name.

Page TSconfig restricts a field to the pages the contracts of that page tree are
stored on. Page TSconfig is inherited down the page tree, so each site
configures its own folders on its root page.

The setting is opt-in, and without it nothing changes. Respecting the Extbase
storage page instead is deliberately not done: a page tree without an explicitly
configured storage page would get an empty select and no error.

..  confval:: itemsProcFunc.storagePids
    :name: contract-select-storage-pids
    :type: string, a comma-separated list of page uids
    :Default: (empty)

    The pages a contract has to be stored on to be offered. Empty — the default
    — offers every contract of the installation.

..  confval:: itemsProcFunc.recursive
    :name: contract-select-recursive
    :type: integer
    :Default: 0

    How many levels below each listed page are included. The default ``0`` uses
    the listed pages themselves. A hidden folder is included as well, because a
    storage folder is regularly hidden.

Set them on the page record of the site root, tab :guilabel:`Resources`, field
:guilabel:`Page TSconfig`. The path of the FlexForm field is the longer one: it
carries the data structure identifier and the sheet, and the dot in the
element's name is escaped.

..  code-block:: typoscript
    :caption: Page TSconfig of a site root

    # The "Contract" field of a page contact record.
    TCEFORM.tx_academiccontacts4pages_domain_model_contact.contract.itemsProcFunc {
        storagePids = 42,84
        recursive = 1
    }

    # The "Selected contracts" field of the content element.
    TCEFORM.tt_content.pi_flexform.academicpersons_selectedcontracts.sDEF.settings\.selectedContracts.itemsProcFunc {
        storagePids = 42,84
        recursive = 1
    }

A contract the record already references stays selectable even when it is stored
outside the listed pages. Without that, opening and saving the record would drop
the relation, because a select offers no other source for its value.

The restriction is applied before
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` is
dispatched, so a listener of that event still has the last word.

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

Add the set to the :file:`config.yaml` of the site that should offer the content
elements:

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

For an installation that still configures its frontend through
:sql:`sys_template` records, the same files are registered as static templates
and as selectable page TSconfig files.

..  tip::

    On TYPO3 v13 and v14 we recommend the site set — and if you use it, do not
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

A site that uses the site set **and** the static template reads the shipped
files twice. The site set is applied before the :sql:`sys_template` record, so
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
   Sections/Index
   Validations/Index
   FrontendUserSync/Index
   RouteEnhancers/Index
