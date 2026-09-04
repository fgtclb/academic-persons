..  _feature-configurable-public-profile:

====================================
Feature: Configurable public profile
====================================

Description
===========

The public profile detail view - the output of the :guilabel:`Persons Detail`
and :guilabel:`Persons List and Detail` content elements - is assembled from
the :yaml:`profile` map of :file:`Configuration/AcademicPersons/Settings.yaml`
instead of a fixed template. Its :yaml:`structure` lists name the elements of
the left and the right layout column in render order, and :yaml:`details`
says per element which profile properties, which contract data or which label
it shows. The shipped file renders the section navigation on the left and, on
the right, the headline, the contract positions, the image, the contact data,
a subline, the fold-out profile entries, the link properties and the timeline
sections; below the large breakpoint the navigation moves into the content
column, directly before the ``subline`` element - an override that drops
``subline`` from the right column therefore has no mobile navigation. See
:ref:`configuration-sections-profile`.

:file:`Resources/Private/Templates/Profile/Detail.html` receives the map as
``publicProfile`` and dispatches every configured element to a partial of its
own below :file:`Resources/Private/Partials/Profile/PublicProfile/`. An empty
property or relation renders nothing, and so does an element identifier the
template does not know.

Everything the old, static template rendered has an element: ``links`` carries
the ``website`` and ``publicationsLink`` properties with their companion title
properties, and the contact rows carry the type of each email address, phone
number and postal address again. What the rebuilt view has no equivalent for
are the two headings of its static sections, :guilabel:`Contracts` and
:guilabel:`Additional information` - every block is a configured element now
and carries its own heading. The label changes that follow from this are
listed in :ref:`important-public-profile-label-changes`.

The view renders no ``<main>``, no ``<aside>`` and no ``<h1>``: it is a content
element, a page may carry two of them, and the page template owns those. Its
headings start at ``<h2>`` for the headline and step down from there.

The view ships its own stylesheet and a small ES module for the fold-out
entries, the sticky navigation and the Bootstrap ScrollSpy, both loaded by the
template through the asset collector. The six control icons of the view are
registered in :file:`Configuration/Icons.php` as
``academic-persons-envelope``, ``academic-persons-phone``,
``academic-persons-address``, ``academic-persons-room``,
``academic-persons-detail-plus`` and ``academic-persons-detail-minus``, drawn in
``currentColor`` and inlined, so they take the text colour of the page. They
are `Bootstrap Icons <https://icons.getbootstrap.com/>`__ and carry their MIT
licence in :file:`Resources/Public/Icons/LICENSE-bootstrap-icons.txt`.

The colours of the view are custom properties declared on
``.academic-persons-detail`` and are the theming hook: redeclare them on that
class to change them. The stylesheet stays inside its own container, so a
theme that clips its content sections with ``overflow: hidden`` cuts off the
sticky navigation of the left column - see
:ref:`configuration-sections-profile-rendering` for the one rule that lifts
it.

Impact
======

A site package changes the layout of the public profile and the fields it
shows by shipping its own :yaml:`profile` map, without replacing the detail
template. The maps are merged on the top level, so an override repeats the
complete :yaml:`structure` and :yaml:`details` it wants - and the editable
fields the editing frontend reads from the same map. Flush the TYPO3 caches
after changing the file.

An installation that overrides :file:`Templates/Profile/Detail.html` keeps
rendering its own template; the ``publicProfile`` variable is available to it
from now on. **Such an override switches this feature off entirely**, and it
does so without any sign that it did: the page renders as it always did, the
:yaml:`profile.structure` and :yaml:`profile.details` maps are read by the new
partials only, and a change to either has no effect on that page. The timeline
properties such an override prints - ``{item.year}``, ``{item.yearStart}``,
``{item.yearEnd}`` - are unchanged, so its entries keep rendering; what it
loses is the layout, not the values.

The way back is to delete the override and configure the layout instead, or to
re-derive the override from the shipped :file:`Detail.html`, which is 45 lines
and dispatches to the eleven partials below
:file:`Resources/Private/Partials/Profile/PublicProfile/`. Those eleven are the
override surface now, and overriding one of them keeps the configured layout
intact. Which partials the detail view stopped rendering, and which of them was
deleted with it, is listed in
:ref:`breaking-public-profile-detail-partials`.

..  index:: Configuration, Frontend, Fluid, NotScanned, ext:academic_persons
