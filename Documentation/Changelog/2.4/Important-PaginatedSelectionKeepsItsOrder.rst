.. _important-paginated-selection-keeps-its-order:

================================================
Important: A paginated selection keeps its order
================================================

Description
===========

The profile list and list-and-detail plugins let an editor pick the profiles by
hand (:guilabel:`List of profiles`) and switch :guilabel:`Pagination` on at the
same time. Such a selection is an ordered list, but the query that fetches it
matches :sql:`uid IN (...)`, which does not preserve that order -
:php:`ProfileController::listAction()` restored it afterwards, in PHP.

With pagination switched on, the pages were cut out of the *query* result
instead, in database order, while the restored order only reached a view
variable the template does not render in that case. So the visible order was
the database's. The paginated statement also carried no :sql:`ORDER BY` at all,
which let two pages show the same profile, or none show it, on PostgreSQL.

The selection is now sorted before it is paginated, and the pages are cut out of
the sorted list. The selection branch of
:php:`ProfileRepository::resolveDemandForQuery()` orders by :sql:`uid` ascending
as well now, like every other branch: that result is what listeners of
:php:`ModifyListProfilesEvent` receive, and it has to be the same list twice.

Impact
======

A list plugin with a manual selection **and** pagination renders its pages in
the order the editor arranged them, from the first page on, and every selected
profile appears on exactly one page on every supported database.

Nothing changes for a list without a manual selection, for a selection without
pagination, or for the card, selected-profiles and selected-contracts plugins,
which sort a selection and do not paginate.

Affected Installations
======================

Every installation with a profile list or list-and-detail plugin that combines a
manual selection with pagination. An installation that worked around the defect
by arranging its selection in database order sees its pages change.

.. index:: Frontend, PHP-API, ext:academic_persons
