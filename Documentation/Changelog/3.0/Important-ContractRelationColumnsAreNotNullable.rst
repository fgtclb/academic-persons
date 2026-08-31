.. _important-1788195400:

=====================================================
Important: Contract relation columns are NOT NULL now
=====================================================

Description
===========

The ``organisational_unit``, ``function_type`` and ``location`` columns
of ``tx_academicpersons_domain_model_contract`` were nullable integer columns,
and their TCA select fields used an empty string (respectively ``null``) as the
value of their "please select" item. PostgreSQL rejects an empty string as an
integer value, so on that DBMS saving a contract with one of these selects empty
failed — and so did every DataHandler ``localize`` of a contract whose source
row held ``NULL`` in one of them, which the reworked translation
synchronisation runs into. MySQL, MariaDB and SQLite coerce the empty string to
``0`` silently, which is why the defect only surfaced on PostgreSQL.

Both sides now follow the core convention for optional single-value relation
selects: the empty item value is ``0``, the field default is ``0``, and the
three columns are ``int(11) unsigned DEFAULT '0' NOT NULL``.

Impact
======

The database analyzer will suggest altering the three columns. On installations
where existing rows hold ``NULL`` in one of them — rows created before this
version and never saved since — the ``ALTER TABLE`` fails on PostgreSQL and on
MySQL in strict mode until those values are updated to ``0``:

..  code-block:: sql

    UPDATE tx_academicpersons_domain_model_contract
        SET organisational_unit = 0 WHERE organisational_unit IS NULL;
    UPDATE tx_academicpersons_domain_model_contract
        SET function_type = 0 WHERE function_type IS NULL;
    UPDATE tx_academicpersons_domain_model_contract
        SET location = 0 WHERE location IS NULL;

An empty relation is stored as ``0`` from now on; Extbase resolves both
``NULL`` and ``0`` to an unset relation, so rendering is unaffected.

Affected Installations
======================

Every installation — the schema change applies everywhere. Functionally broken
before this change: PostgreSQL installations only.

.. index:: Database, TCA, ext:academic_persons
