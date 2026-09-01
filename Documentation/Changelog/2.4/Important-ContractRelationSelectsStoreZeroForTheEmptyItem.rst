.. _important-ace-488-academic-persons:

===============================================================
Important: Contract relation selects store 0 for the empty item
===============================================================

Description
===========

The TCA selects of ``organisational_unit`` and ``function_type`` on
``tx_academicpersons_domain_model_contract`` used an empty string as the value
of their "please select" item and declared no field default. The DataHandler's
select evaluation turns that into an empty string, which PostgreSQL rejects as
integer input (``SQLSTATE[22P02]``) while MySQL, MariaDB and SQLite coerce it
to ``0`` silently. On PostgreSQL, saving a contract in the backend with one of
these selects empty therefore failed, and so did copying or localising a
contract whose row held ``NULL`` in one of them (found on ``main`` as ACE-489,
verified present on this line). The ``location`` select used ``null`` for both
the item value and the default, which the DataHandler persisted as a valid
``NULL``; it is aligned for consistency.

The three selects now follow the core convention for optional single-value
relation selects: the empty item value is ``0`` and the field default is ``0``,
so the DataHandler persists an integer ``0`` for an empty relation.

The columns themselves stay **nullable** on this line, unlike on ``main``
(3.0), where they became ``NOT NULL DEFAULT '0'``: this line supports TYPO3
v12, whose Extbase persistence writes ``NULL`` into a nullable column for a
detached single relation (v13 writes ``0``), so a ``NOT NULL`` column would
break every frontend editing save of a contract without a location on v12 -
on every DBMS.

Impact
======

No schema change and no manual data update. An empty relation is stored as
``0`` by the backend and by Extbase on v13, and as ``NULL`` by Extbase on v12;
Extbase resolves both to an unset relation, so rendering is unaffected.

Rows saved before this change with an empty relation still hold ``NULL``; the
DataHandler repairs them on the way — a copy or localisation of such a contract
evaluates the copied ``NULL`` to the new default ``0`` instead of the empty
string, on both core versions of this line.

Affected Installations
======================

Every installation - the TCA change applies everywhere. Functionally broken
before this change: PostgreSQL installations only.

.. index:: Database, TCA, ext:academic_persons
