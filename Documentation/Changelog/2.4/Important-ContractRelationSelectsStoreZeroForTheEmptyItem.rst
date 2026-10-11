.. _important-ace-488-academic-persons:

===============================================================
Important: Contract relation selects store 0 for the empty item
===============================================================

Description
===========

The TCA selects of :sql:`organisational_unit` and :sql:`function_type` on
:sql:`tx_academicpersons_domain_model_contract` used an empty string as the
value of their "please select" item and declared no field default. The
DataHandler's select evaluation turns that into an empty string, which
PostgreSQL rejects as integer input (``SQLSTATE[22P02]``) while MySQL, MariaDB
and SQLite coerce it to ``0`` silently. On PostgreSQL, saving a contract in the
backend with one of these selects empty therefore failed, and so did copying or
localizing a contract whose row held ``NULL`` in one of them. The
:sql:`location` select used ``null`` for both the item value and the default,
which the DataHandler persisted as a valid ``NULL``. It is aligned for
consistency.

The three selects now follow the core convention for optional single-value
relation selects: the empty item value is ``0`` and the field default is ``0``,
so the DataHandler persists an integer ``0`` for an empty relation.

The columns themselves stay **nullable**. Extbase on TYPO3 v12 writes ``NULL``
into a nullable column for a detached single relation, while TYPO3 v13 writes
``0``. A ``NOT NULL`` column would therefore break every save of a contract
without a location in the frontend editing on TYPO3 v12, on every database.

Impact
======

No schema change and no manual data update. An empty relation is stored as
``0`` by the backend and by Extbase on TYPO3 v13, and as ``NULL`` by Extbase on
TYPO3 v12. Extbase resolves both to an unset relation, so rendering is
unaffected.

Rows saved before this change with an empty relation still hold ``NULL``. The
DataHandler repairs them on the way: a copy or localization of such a contract
evaluates the copied ``NULL`` to the new default ``0`` instead of the empty
string, on TYPO3 v12 and v13.

Affected Installations
======================

Every installation, the TCA change applies everywhere. Saving, copying and
localizing failed before this change on PostgreSQL only.

.. index:: Database, TCA, ext:academic_persons
