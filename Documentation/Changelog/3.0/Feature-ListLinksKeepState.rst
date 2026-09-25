.. _feature-list-links-keep-state:

=========================================================
Feature: Navigation links keep the visitor's list choices
=========================================================

Description
===========

The list action of the list and list-and-detail plugins assigns the new template
variable `activeListArguments`: the choices of the visitor the list is shown
with, as the plugin accepted them - the page while it is not the first, the
letter while one is selected. A query parameter the plugin does not accept from
a visitor never becomes part of it, nor does a value the content element sets,
such as the sorting.

The page navigation :file:`Partials/Profile/List/Pagination.html` and the letter
navigation :file:`Partials/Profile/List/AlphabetPagination.html` build every
link from it through the new view helper `persons:listArguments`, and change
only what the link is responsible for: the page, or the letter, where a letter
link leads to the first page. The list template passes the variable to the
letter navigation, :file:`Partials/Profile/List/ItemList.html` to the page
navigation.

See :ref:`templates-navigation-links` and :ref:`developers-navigation-links`.

Impact
======

The links are the ones they were before: page and letter are still the only
values a visitor sets. A later value - a view mode, a filter - is carried by
both navigations without an edit of theirs.

A project that copied :file:`Pagination.html` or :file:`AlphabetPagination.html`,
or a list template or :file:`ItemList.html` that renders them without
`activeListArguments`, keeps its links, and will drop such a value on the next
click. Adopt the view helper and pass the variable on, as the shipped files do.

.. index:: Frontend, Fluid, ext:academic_persons
