.. _important-contact-find-all-orders-by-uid:

===================================================================
Important: findAll of contact and profile information orders by uid
===================================================================

Description
===========

The :php:`findAll()` methods of :php:`AddressRepository`,
:php:`EmailRepository`, :php:`PhoneNumberRepository` and
:php:`ProfileInformationRepository` executed without an ordering, so their
result came in whatever order the database yielded — on PostgreSQL not
necessarily the same list twice. They now order by :sql:`uid` ascending.

The tables are manually sortable, but their :sql:`sorting` is kept per contract,
or per profile and type, so it does not order records of different parents
meaningfully. The methods returning the records of one contract or profile keep
ordering by :sql:`sorting`.

Impact
======

No visible change is expected: nothing in the extensions calls these methods,
and :sql:`uid` ascending is the order SQLite, MySQL and MariaDB return in
practice; PostgreSQL promises none. Code that calls them gets that order
guaranteed now rather than by coincidence.

Affected Installations
======================

Installations with own code calling one of these :php:`findAll()` methods.

.. index:: PHP-API, ext:academic_persons
