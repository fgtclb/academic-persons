.. _important-1788195400:

=====================================================
Important: Contract relation columns are NOT NULL now
=====================================================

Description
===========

Version 2.4 made the selects of the ``organisational_unit``, ``function_type``
and ``location`` columns of ``tx_academicpersons_domain_model_contract`` store
``0`` for their empty item, see :ref:`important-ace-488-academic-persons`. It
kept the columns nullable, because Extbase on TYPO3 v12 writes ``NULL`` into a
nullable column for a detached single relation.

This version supports TYPO3 v13 and v14 only, where Extbase writes ``0``. The
three columns are ``int(11) unsigned DEFAULT '0' NOT NULL`` now, the core
convention for an optional single-value relation.

Impact
======

The database analyzer suggests altering the three columns. Rows saved with an
empty relation before version 2.4, or by Extbase on TYPO3 v12, hold ``NULL`` in
one of them. On installations with such rows the ``ALTER TABLE`` fails on
PostgreSQL and on MySQL in strict mode until those values are updated to ``0``:

..  code-block:: sql

    UPDATE tx_academicpersons_domain_model_contract
        SET organisational_unit = 0 WHERE organisational_unit IS NULL;
    UPDATE tx_academicpersons_domain_model_contract
        SET function_type = 0 WHERE function_type IS NULL;
    UPDATE tx_academicpersons_domain_model_contract
        SET location = 0 WHERE location IS NULL;

Extbase resolves both ``NULL`` and ``0`` to an unset relation, so rendering is
unaffected.

Affected Installations
======================

Every installation of this extension, with the database update. Only those
with rows holding ``NULL`` have to run the statements above first.

.. index:: Database, TCA, ext:academic_persons
