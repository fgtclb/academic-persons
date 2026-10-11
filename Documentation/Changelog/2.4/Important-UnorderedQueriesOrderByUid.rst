.. _important-unordered-queries-order-by-uid:

=====================================================
Important: Unordered profile queries now order by uid
=====================================================

Description
===========

Four query paths of this extension executed without any ordering, so the order
of their result was whatever the database happened to yield:

*   :php:`ProfileRepository::findAll()`
*   :php:`ProfileRepository::findByDemand()` whenever the demand yields no
    ordering, which is what :guilabel:`No sorting` in the
    :guilabel:`Sort By` option of the list plugin means
*   :php:`LocationRepository::findAll()`,
    :php:`FunctionTypeRepository::findAll()` and
    :php:`OrganisationalUnitRepository::findAll()`, which fill the location,
    function type and organisational unit selects of the contract form in
    `EXT:academic_persons_edit`

All four now order by :sql:`uid` ascending when nothing else asks for an order.

Impact
======

No visible change is expected on SQLite, MySQL and MariaDB. They returned
insertion order for these queries in practice, and :sql:`uid` ascending is that
same order, guaranteed now rather than coincidental. PostgreSQL promised no
order at all.

What changes is that the order is reproducible. It previously depended on the
database, its version and which indexes existed, and could therefore change
under an installation without any content change. An index an installation
adds itself, for example over :sql:`t3ver_oid`, can give the PostgreSQL planner
another way to read these tables and reverse the result of exactly these
queries.

The TCA :php:`default_sortby` of the location, function type and organisational
unit tables still does **not** apply to their :php:`findAll()`. That was never
the case and is not changed here. It orders the record lists of the backend,
not the selects of the frontend edit form.

Affected Installations
======================

Every installation of this extension. An installation that already sets a
sorting on the list plugin is unaffected either way, because an explicit
ordering always won.

.. index:: Frontend, PHP-API, ext:academic_persons
