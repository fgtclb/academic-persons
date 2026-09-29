.. _feature-frontend-user-relation-mapping:

==============================================================
Feature: Synchronise the organisational unit and function type
==============================================================

Description
===========

The synchronisation of :bash:`academic:createprofiles` and
:bash:`academic:updateprofiles` never set the organisational unit or the
function type of the imported contract. Projects that take both from their
directory or HR data needed a profile factory of their own.

The :yaml:`contract` map of :yaml:`frontendUserSync` in
:file:`Configuration/AcademicPersons/Settings.yaml` gains two relations,
:yaml:`organisationalUnit` and :yaml:`functionType`:

*   :yaml:`column` names the :sql:`fe_users` column holding the value, and
    :yaml:`matchBy` the field of the record it is compared with: the unique
    name or the unit name of an organisational unit, the name of a function
    type.
*   :yaml:`create: true` creates a missing record on the page
    :yaml:`storagePid`. Creation is off by default, and switching it on
    without a storage page is a mistake in the map.
*   The value has to match exactly, on every database. Hidden records match,
    deleted records, drafts of a workspace and translations do not. Of several
    matching records the one with the lowest uid is assigned.
*   An empty column clears a mapped relation, and so does a value that matches
    nothing while creation is off. A relation that is not mapped keeps what an
    editor chose.

The employee type is not synchronised. See
:ref:`configuration-frontend-user-sync-relations`.

Impact
======

Nothing changes for an installation without a map of its own: the shipped map
leaves both relations unmapped.

A site package that maps a relation owns it from then on: the next run of
:bash:`academic:updateprofiles` overwrites an organisational unit or function
type an editor chose on an imported contract, and clears it where the column is
empty.

.. index:: CLI, ext:academic_persons
