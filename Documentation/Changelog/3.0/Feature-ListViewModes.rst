.. _feature-list-view-modes:

==================================================
Feature: The list elements render their view modes
==================================================

Description
===========

The list, list-and-detail, selected profiles and selected contracts elements
offered a :guilabel:`View Mode Default` and a :guilabel:`View Mode Toggle` that
no template read. Both take effect now:

*   :guilabel:`View Mode Default` renders the tile grid - the item keeps its
    stored value ``list`` and is labelled :guilabel:`Tiles` - or a table. The
    table has one row per profile, one per contract in the selected contracts
    element, and the columns name, position, e-mail, phone and room.
*   :guilabel:`View Mode Toggle` offers the visitor a switch between the
    allowed modes. The active mode is marked as current, and the pagination and
    the letter navigation keep the chosen mode.
*   Two site settings, :typoscript:`plugin.tx_academicpersons.viewMode.allowed`
    (default ``list,table``) and
    :typoscript:`plugin.tx_academicpersons.table.columns` (default
    ``name,position,emailAddresses,phoneNumbers,room``), decide which modes
    exist and what the table shows. A requested mode outside the allowed ones,
    or requested while the switch is off, renders the default mode.
*   The route enhancers :file:`List.yaml` and :file:`ListAndDetail.yaml` give a
    mode other than the default a speaking URL,
    :file:`/view-mode/table`, also with a page or a letter behind it.

A project adds a mode without a template override: a partial
:file:`Profile/ViewMode/<Mode>.html`, the mode in the allowed modes, and an item
of :guilabel:`View Mode Default` in page TSconfig. Its links carry it as a query
argument until the site adds it to the map of the route enhancer.

See :ref:`configuration-view-modes`, :ref:`templates-view-modes`,
:ref:`templates-view-modes-own` and :ref:`configuration-route-enhancers-view-modes`.

Impact
======

A content element saved with the default :guilabel:`Tiles`, or before the field
existed, renders as before. One that an editor set to :guilabel:`Table` renders
a table from now on, and one with the toggle switched on shows the switch.

:file:`Partials/Profile/List/ItemList.html` and the selected profiles and
contracts templates render the items through :file:`Profile/ViewMode/<Mode>.html`
instead of :file:`Profile/List/Items.html`, and the list template renders the
switch. A project copy of :file:`ItemList.html` or of a selected template keeps
rendering the grid whatever the mode; a copy of the list template shows the
mode :file:`ItemList.html` renders, without a switch. Adopt the shipped files to
use the view modes. A project's own table or listener for these fields can be
removed.

The table honours the fields of the content element: a contract field left out
of :guilabel:`Show only selected Fields` is left out of the table as well.

A site that imports the route enhancers gets the three new routes of each
enhancer. The existing URLs do not change.

.. index:: Frontend, Fluid, FlexForm, TypoScript, ext:academic_persons
