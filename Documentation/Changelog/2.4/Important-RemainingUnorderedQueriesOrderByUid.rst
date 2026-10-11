.. _important-remaining-unordered-queries-order-by-uid:

=======================================================
Important: Remaining unordered queries now order by uid
=======================================================

Description
===========

Besides the profile queries of
:ref:`important-unordered-queries-order-by-uid`, five more query paths returned
rows in whatever order the database yielded:

*   :php:`ContractRepository::findAll()`, which builds the contract select
    items in the backend (TCA :php:`itemsProcFunc` and FlexForm) where no
    storage pages restrict them
*   :php:`ContractRepository::findByUids()`, which resolves the contracts of
    the "selected contracts" plugin
*   :php:`ProfileRepository::findByUids()`, which resolves the profiles of the
    "selected profiles" plugin
*   :php:`ProfileRepository::findByFrontendUser()`, which resolves the profiles
    of a frontend user for the frontend editing of
    `EXT:academic_persons_edit`
*   :php:`ProfileRepository::findByDemand()` with a non-empty demanded
    ordering, the :guilabel:`Sort By` option of the list plugin, which carried
    no tiebreaker. Profiles equal in it (two people sharing a last name) had
    no defined relative order.

The first four now order by :sql:`uid` ascending. The demanded ordering keeps
winning and gets :sql:`uid` ascending appended as a tiebreaker.

Impact
======

No visible change is expected on SQLite, MySQL and MariaDB: :sql:`uid` ascending
is the order they return in practice, and it is guaranteed now rather than
coincidental. PostgreSQL promises no order without one, so an installation on it
may see such a list change once.

For the two uid selection methods the order of the editor's selection is
deliberately **not** reproduced. :php:`in()` does not preserve it, and it was
never delivered before. Honouring the selection order would be a behaviour
change beyond making the lists reproducible.

Affected Installations
======================

Every installation of this extension.

.. index:: Frontend, Backend, PHP-API, ext:academic_persons
