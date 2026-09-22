.. _important-profile-item-and-list-partials:

============================================================
Important: The profile item and list are built from partials
============================================================

Description
===========

:file:`Partials/Profile/Item.html` and
:file:`Partials/Profile/List/ItemList.html` were single blocks. A project that
wanted the academic title in a heading, a different image, its own detail link
or another empty state copied the whole file into its site package - and then
missed every correction made upstream.

Both are now entry points that delegate. Their arguments are unchanged, so an
existing copy of either file keeps rendering exactly as it did; it simply does
not profit from the split.

The item renders four partials:

..  list-table::
    :header-rows: 1

    *   -   Partial
        -   Renders
    *   -   :file:`Profile/Item/DetailLink.html`
        -   The address of the detail view, as text
    *   -   :file:`Profile/Item/Name.html`
        -   The name, as text
    *   -   :file:`Profile/Item/Contracts.html`
        -   The contract block
    *   -   :file:`Profile/Item/Image.html`
        -   The image, or the placeholder

The list renders four more:

..  list-table::
    :header-rows: 1

    *   -   Partial
        -   Renders
    *   -   :file:`Profile/List/ResultCount.html`
        -   Nothing. It is the override hook for a "showing x of y", and it is
            handed both numbers
    *   -   :file:`Profile/List/GroupHeader.html`
        -   The heading above one group of a grouped list
    *   -   :file:`Profile/List/Items.html`
        -   The grid, with its Bootstrap row and column classes
    *   -   :file:`Profile/List/EmptyState.html`
        -   The text shown when nothing was found

:file:`Profile/List/Items.html` and :file:`Profile/List/EmptyState.html` are
what the card, the selected profiles and the selected contracts element render
as well, so an override of either reaches every element of this extension at
once. The selected contracts element passes its contracts to the grid and the
label about contracts to the empty state.

The academic title is part of the name
--------------------------------------

A profile with a :guilabel:`Title` now shows it in front of the name in every
item - `Prof. Dr. Anna Beispiel` rather than `Anna Beispiel`. The detail view
has always rendered the title, so the item was the inconsistent view.

The name is also joined differently. It used to be written as
`{firstName} {middleName} {lastName}` with literal spaces, so a profile without
a middle name carried two of them in the markup. An empty part is now left out
with its separator.

New classes
-----------

Every partial that renders an element of its own got a stable class **next to**
the classes that were there before. No class was removed, so a stylesheet that
builds on `card`, `card-title`, `card-img-top` or `academic-persons-itemlist`
keeps working.

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Class
    *   -   The item heading
        -   `academic-persons-item__name`
    *   -   The item image
        -   `academic-persons-item__image`
    *   -   The group heading of a grouped list
        -   `academic-persons-list__group-header`
    *   -   The page navigation
        -   `academic-persons-list__pagination`
    *   -   The letter navigation
        -   `academic-persons-list__alphabet-pagination`
    *   -   The item grid, and each of its columns
        -   `academic-persons-grid`, `academic-persons-grid__item`
    *   -   The empty state
        -   `academic-persons-empty-state`

The last two are blocks of their own rather than parts of
`academic-persons-list`, because the grid and the empty state sit inside the
list, the card, the selected profiles and the selected contracts alike.

:file:`Profile/Item/DetailLink.html`, :file:`Profile/Item/Name.html`,
:file:`Profile/Item/Contracts.html` and :file:`Profile/List/ResultCount.html`
have no class of their own: the first two render text, the third renders the
contract partial unchanged, and the fourth renders nothing.

Impact
======

An installation that overrides no template renders three differences: the
academic title in the item heading where a profile has one, one space fewer in
the name of a profile without a middle name, and the new classes listed above
next to the existing ones.

An installation that copied :file:`Profile/Item.html` or
:file:`Profile/List/ItemList.html` renders exactly what it rendered before. Its
copy does not delegate, so overriding one of the new partials next to it has no
effect - drop the copy and keep the small override instead.

One variable of the item was renamed while the arguments stayed: the item used
to define `detailUri` before it rendered :file:`Profile/Contract/Item.html`,
which receives all of them, and now defines `detailLink` instead. Nothing
shipped read it; a project copy of the contract partial that did has to follow.

A project that renders profile items in a template of its own can now pass the
detail page explicitly:

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

The passed page wins over `plugin.tx_academicpersons.detailPid`.

Affected Installations
======================

Every installation that shows profiles through the list, list-and-detail, card,
selected-profiles or selected-contracts element, or through the contacts element
of `EXT:academic_contacts4pages`.

Two cases need a look:

#.  A project that added the academic title to the name in a copy of
    :file:`Profile/Item.html` now has two places doing it. Drop the copy.
#.  `EXT:academic_contacts4pages` resolves the partials through partial root
    paths of its own. A project override has to be registered for
    `plugin.tx_academiccontacts4pages.view.partialRootPath` as well, exactly as
    it always had to be for a copy of the item.

.. index:: Fluid, Frontend, Template, ext:academic_persons
