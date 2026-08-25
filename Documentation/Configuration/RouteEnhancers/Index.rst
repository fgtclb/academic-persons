..  index:: Configuration; Route enhancers
..  _configuration-route-enhancers:

===============
Route enhancers
===============

This extension ships three ready made route enhancers below
:file:`Configuration/Routes/`. TYPO3 does not read those files on its own —
they are fragments that have to be imported from the configuration of the site
which shows the plugins.

Each file covers exactly one plugin, so which of them you import follows from
which plugins the site actually uses.

What the files enhance
----------------------

:file:`Detail.yaml`
    Enhancer :yaml:`ProfileDetailPlugin` for the plugin :yaml:`Detail`,
    argument namespace :php:`tx_academicpersons_detail`. One route,
    :yaml:`/{profile_name}`, for the :php:`detail` action, mapping the argument
    :yaml:`profile`. The path segment is resolved by a
    :yaml:`PersistedAliasMapper` on the table
    :sql:`tx_academicpersons_domain_model_profile` over the field
    :sql:`slug`.

:file:`List.yaml`
    Enhancer :yaml:`ProfileListPlugin` for the plugin :yaml:`List`, argument
    namespace :php:`tx_academicpersons_list`. Two routes for the :php:`list`
    action: :yaml:`{localized_page}-{page}` for :yaml:`demand/currentPage` and
    :yaml:`/{letter}` for :yaml:`demand/alphabetFilter`. The page number is
    limited to a :yaml:`StaticRangeMapper` from 1 to 1000, the letter to a
    :yaml:`StaticRangeMapper` from ``a`` to ``z``, and the word in front of
    the page number is translated by a :yaml:`LocaleModifier` — ``page`` by
    default, ``seite`` for German.

:file:`ListAndDetail.yaml`
    Enhancer :yaml:`ProfileListAndDetailPlugin` for the plugin
    :yaml:`ListAndDetail`, argument namespace
    :php:`tx_academicpersons_listanddetail`. It is the union of the two above:
    the detail route, the pagination route and the letter route, with the same
    aspects, because that plugin renders both the list and the detail view.

Which file to import
--------------------

Which of them you import follows from which plugins the site actually uses:

*   A site that puts the :guilabel:`List` plugin on one page and the
    :guilabel:`Detail` plugin on another — the usual setup, where the list
    links to the detail page through the plugin setting
    :typoscript:`detailPid` — imports :file:`List.yaml` **and**
    :file:`Detail.yaml`.
*   A site that puts the single :guilabel:`ListAndDetail` plugin on one page
    imports :file:`ListAndDetail.yaml` only.
*   A site that uses both variants imports all three, and then has to bound
    each of them to its own pages — see the next section.

The remaining plugins of this extension — :yaml:`SelectedProfiles`,
:yaml:`SelectedContracts` and :yaml:`Card` — take no frontend arguments, so no
enhancer is shipped for them.

Limiting an enhancer to its pages
---------------------------------

An enhancer is offered to **every** page of the site unless it says otherwise,
and TYPO3 takes the first candidate route whose path matches *and* whose
aspects resolve. Two enhancers are not kept apart by belonging to different
plugins, nor by carrying different keys — neither is what the matcher looks at.

The three files of this extension describe the same two views, so their routes
overlap by construction:

..  list-table::
    :header-rows: 1

    *   -   Route
        -   Declared in
    *   -   :yaml:`/{profile_name}`
        -   :file:`Detail.yaml` and :file:`ListAndDetail.yaml`
    *   -   :yaml:`{localized_page}-{page}`
        -   :file:`List.yaml` and :file:`ListAndDetail.yaml`
    *   -   :yaml:`/{letter}`
        -   :file:`List.yaml` and :file:`ListAndDetail.yaml`

Each pair is identical down to the mapper, so importing more than one file
without saying where it applies means the file imported first takes those URLs
on every page of the site. The plugin on the other page then never receives its
argument: the dedicated detail page answers ``404``, and the combined
plugin renders the unfiltered list where a letter was asked for.

Only resolving is ambiguous. Generating a URL is scoped to the plugin namespace
being linked, so the links keep looking right, which is why this surfaces as a
broken page rather than as a broken link.

:yaml:`limitToPages` is the answer, and with it in place the import order no
longer matters:

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    imports:
      - resource: 'EXT:academic_persons/Configuration/Routes/List.yaml'
      - resource: 'EXT:academic_persons/Configuration/Routes/ListAndDetail.yaml'
      - resource: 'EXT:academic_persons/Configuration/Routes/Detail.yaml'

    routeEnhancers:
      ProfileListPlugin:
        limitToPages: [12, 13]
      ProfileListAndDetailPlugin:
        limitToPages: [14]
      ProfileDetailPlugin:
        limitToPages: [15]

The uids are those of the pages carrying the plugin in question, and they are
the uids of the **default language**: matching derives the page as
:php:`l10n_parent ?: uid`, so one list covers every translation of that page.
Plain page uids work on every TYPO3 version this extension supports.

A site that imports a single file needs no limitation for this extension, but
adding it is still worth the two lines. What keeps a route path of the same
shape from another extension apart is only that its mapper rejects the value —
a slug that happens to exist in both tables is enough to make the two compete.

What the URLs look like
-----------------------

Assuming the list plugin sits on a page with the slug :file:`/persons` and the
detail plugin on :file:`/persons/profile`, the URLs change as follows.

..  code-block:: text
    :caption: Without the enhancers

    /persons?tx_academicpersons_list%5Bdemand%5D%5BcurrentPage%5D=2
    /persons?tx_academicpersons_list%5Bdemand%5D%5BalphabetFilter%5D=m
    /persons/profile?tx_academicpersons_detail%5Bprofile%5D=42

..  code-block:: text
    :caption: With the enhancers imported

    /persons/page-2
    /persons/m
    /persons/profile/jane-doe

Caveats
-------

*   The detail route needs a slug. :yaml:`PersistedAliasMapper` resolves the
    path segment against the :sql:`slug` field of the profile record, so a
    profile whose slug is empty cannot be reached through the enhanced URL. The
    slug is generated by the TCA :php:`slug` field, which means it is filled
    when the record is saved in the backend. Profiles created by
    :bash:`academicpersons:createprofiles` are persisted through the Extbase
    persistence manager and therefore never pass the :php:`DataHandler`, so
    those records — and records that predate the field — start out with an
    empty slug and have to be saved once in the backend before the enhanced
    URL resolves.
*   The two list routes are alternatives, not a combination. A link that
    carries a page number *and* a letter matches the pagination route, and the
    letter stays behind as a query argument —
    :file:`/persons/page-2?tx_academicpersons_list[demand][alphabetFilter]=m`.
*   Only the mapped value ranges are put into the path. A page number above
    1000, and the empty filter value that the :guilabel:`A-Z` reset link of the
    alphabet pagination submits, are outside the mapped ranges, so those links
    keep their query argument.
*   The :yaml:`localeMap` of the :yaml:`LocaleModifier` is matched against the
    locale of the site language, with the underscores replaced by hyphens and
    anchored at the start. The shipped map lists :yaml:`en_EN.*` and
    :yaml:`de_DE.*`, which means a German language configured as :yaml:`de-DE`
    is translated to ``seite`` while a plain :yaml:`de` is not. Adjust the map
    to the locales your site actually uses.
*   Unlike the program list of :guilabel:`academic_programs`, the pagination
    and the alphabet filter of this extension are rendered as links, not as a
    form, so their own requests do carry the arguments in the URL and are
    enhanced.
