.. _important-route-enhancers-limit-to-pages:

============================================================
Important: Route enhancers have to be limited to their pages
============================================================

Description
===========

The three route enhancers this extension ships below
:file:`Configuration/Routes/` describe the same two views, so three of their
routes are declared twice and are identical down to the mapper:

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

TYPO3 offers every enhancer of a site to every page of that site unless the
enhancer says otherwise, and it takes the first candidate route whose path
matches *and* whose aspects resolve. So a site that imports more than one of
the three files without saying where each applies gives all of those URLs to
the file it imported first.

Only resolving is ambiguous. Generating a URL is scoped to the plugin namespace
being linked, so the links keep looking right and nothing points at the
configuration.

Impact
======

On a site that imports :file:`ListAndDetail.yaml` before :file:`Detail.yaml`,
the page carrying the :guilabel:`Detail` plugin answers ``404`` for every link
the list plugins generate for it — the profile argument arrives in the
namespace of the other plugin, and :php:`ProfileController::detailAction()`
receives nothing.

The two list routes fail more quietly, and in whichever direction the import
order points: the page number or the letter arrives in the wrong namespace, and
the plugin renders the unfiltered first page with status ``200``.

Affected Installations
======================

Installations whose site configuration imports more than one of
:file:`List.yaml`, :file:`ListAndDetail.yaml` and :file:`Detail.yaml` — which is
what a site showing both the separate and the combined plugin needs, and what
the documentation of this extension recommended without further qualification
until now.

Installations that import a single one of the three files are not affected.

Solution
========

Limit each enhancer to the pages that carry its plugin. With that in place the
import order no longer matters:

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

The uids are those of the pages carrying the plugin in question, in the
**default language**: matching derives the page as :php:`l10n_parent ?: uid`,
so one list covers every translation of that page. Plain page uids work on
every TYPO3 version this extension supports.

.. index:: Configuration, ext:academic_persons
