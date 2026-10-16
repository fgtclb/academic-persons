..  _integrator-person-list-and-details:

=======================
Person List and Details
=======================

..  image:: /Images/Icons/persons.svg
    :width: 48px
    :alt: Icon of the profile list and detail

:CType:
    ``academicpersons_listanddetail``

:Plugin:
    `AcademicPersons`, plugin `ListAndDetail`, actions
    :php:`ProfileController::listAction()`,
    :php:`ProfileController::detailAction()` and
    :php:`ProfileController::filterAction()`

:Set:
    `fgtclb/academic-persons-list-and-detail`, or the aggregate set
    `fgtclb/academic-persons`

:Templates:
    :file:`Templates/Profile/List.html` and
    :file:`Templates/Profile/Detail.html`, see :ref:`templates`

The list of the :ref:`Person List <integrator-person-list>` element and the
detail view of the :ref:`Person Details <integrator-person-details>` element in
one content element. The names link to the page of the element itself, which
then shows the profile instead of the list. It is the right choice for a small
directory that does not need a detail page of its own, or for a site that wants
the profile under the URL of its list, see
:ref:`configuration-route-enhancers`.

The detail view renders the public profile configured in
:file:`Settings.yaml`, see :ref:`configuration-sections`, and loads the script
of the profile, see :ref:`configuration-javascript`. A request with a profile
that does not exist, or with a hidden one while :guilabel:`Show hidden records`
is off, ends with the "page not found" handling of the site.

..  figure:: /Images/Screenshots/person-list-and-details-detail.png
    :alt: Profile of Alina Sorge with section navigation, name, positions, portrait and contact block
    :class: with-border with-shadow

    The list-and-detail element after a click on a name: the profile
    replaces the list on the same page.

..  _integrator-person-list-and-details-configuration:

Settings
========

The element has the options of the :guilabel:`Person List`, in the same order,
and below them the :guilabel:`Startingpoint`. It adds :guilabel:`PageTitle
display format` for its detail view, and leaves out :guilabel:`Detail Page` and
:guilabel:`Link the names to the detail view`: the names always link to the
page of the element, which shows the detail view itself. Where an option
behaves exactly as in the list, its description is on the page of the list.

..  confval:: PageTitle display format
    :name: person-list-and-details-page-title-format
    :type: text, with a value picker
    :Default: empty

    The page title of the detail view. Works as in the detail element, see
    :confval:`PageTitle display format <person-details-page-title-format>`.

..  confval:: Group By (is given priority in sorting if active)
    :name: person-list-and-details-group-by
    :type: select
    :Default: No grouping

    See :confval:`Group By <person-list-group-by>`.

..  confval:: Sort By (sorting of activated grouping has priority)
    :name: person-list-and-details-sort-by
    :type: select
    :Default: No sorting

    See :confval:`Sort By <person-list-sort-by>`.

..  confval:: Sort By Direction
    :name: person-list-and-details-sort-by-direction
    :type: select
    :Default: Ascending

    See :confval:`Sort By Direction <person-list-sort-by-direction>`.

..  confval:: Pagination
    :name: person-list-and-details-pagination
    :type: checkbox
    :Default: off

    See :confval:`Pagination <person-list-pagination>`.

..  confval:: Alphabetical Pagination
    :name: person-list-and-details-alphabetical-pagination
    :type: checkbox
    :Default: off

    See :confval:`Alphabetical Pagination
    <person-list-alphabetical-pagination>`.

..  confval:: Results per Page
    :name: person-list-and-details-results-per-page
    :type: text
    :Default: empty

    See :confval:`Results per Page <person-list-results-per-page>`.

..  confval:: Number of Links
    :name: person-list-and-details-number-of-links
    :type: text
    :Default: empty

    See :confval:`Number of Links <person-list-number-of-links>`.

..  confval:: List of profiles
    :name: person-list-and-details-list-of-profiles
    :type: records
    :Default: empty

    See :confval:`List of profiles <person-list-list-of-profiles>`.

..  confval:: View Mode Toggle
    :name: person-list-and-details-view-mode-toggle
    :type: checkbox
    :Default: off

    See :confval:`View Mode Toggle <person-list-view-mode-toggle>`. It applies
    to the list, the detail view has no view modes.

..  confval:: View Mode Default
    :name: person-list-and-details-view-mode-default
    :type: select
    :Default: Tiles

    See :confval:`View Mode Default <person-list-view-mode-default>`.

..  confval:: Fallback to default language for non translated profiles
    :name: person-list-and-details-fallback-for-non-translated
    :type: checkbox
    :Default: on

    See :confval:`Fallback to default language for non translated profiles
    <person-list-fallback-for-non-translated>`. It applies to the list.

..  confval:: Show only selected Fields
    :name: person-list-and-details-show-fields
    :type: select, several values
    :Default: empty

    See :confval:`Show only selected Fields <person-list-show-fields>`. It
    applies to the list, what the detail view shows is configured in
    :ref:`configuration-sections`.

..  confval:: Organisational Units
    :name: person-list-and-details-organisational-units
    :type: select, several values
    :Default: empty

    See :confval:`Organisational Units <person-list-organisational-units>`.

..  confval:: Function Types
    :name: person-list-and-details-function-types
    :type: select, several values
    :Default: empty

    See :confval:`Function Types <person-list-function-types>`.

..  confval:: Visitors may filter by function type
    :name: person-list-and-details-filter-function-type
    :type: checkbox
    :Default: off

    See :ref:`configuration-visitor-filters`.

..  confval:: Visitors may filter by organisational unit
    :name: person-list-and-details-filter-organisational-unit
    :type: checkbox
    :Default: off

    See :ref:`configuration-visitor-filters`.

..  confval:: Contracts per profile
    :name: person-list-and-details-contracts-display
    :type: select
    :Default: All contracts

    The contracts of the list, see :ref:`configuration-contract-display`. The
    detail view takes its choice from :file:`Settings.yaml`.

..  confval:: Only contracts of the selected organisational units and function types
    :name: person-list-and-details-contracts-match-filter
    :type: checkbox
    :Default: off

    See :ref:`configuration-contract-display`.

..  confval:: Only contracts valid today
    :name: person-list-and-details-contracts-only-valid
    :type: checkbox
    :Default: off

    The contracts of the list, see :ref:`configuration-contract-display`. The
    detail view takes its choice from :file:`Settings.yaml`.

..  confval:: Show hidden records
    :name: person-list-and-details-show-hidden-records
    :type: checkbox
    :Default: off

    Lists the hidden profiles and shows their detail view, for an internal
    directory. See :ref:`configuration-hidden-profiles`.

..  confval:: Startingpoint
    :name: person-list-and-details-startingpoint
    :type: pages
    :Default: empty

    See :confval:`Startingpoint <person-list-startingpoint>`. It applies to the
    list without :guilabel:`List of profiles`. The detail view shows the
    profile the link names, wherever it is stored.
