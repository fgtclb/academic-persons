.. _important-organisational-unit-sorts-its-contracts:

================================================================
Important: An organisational unit sorts its contracts on its own
================================================================

Description
===========

A contract is an inline child of two records at once: of its profile, and of
its organisational unit. Both relations declared :php:`foreign_sortby` as the
same :sql:`sorting` column.

:php:`RelationHandler::writeForeignField()` numbers the children of the record
being saved 1..n in the order of its form, so saving an organisational unit
renumbered its contracts *across every profile that owns one of them*. An
editor who had arranged the contracts on a profile saw that arrangement
replaced by the order of an unrelated unit form - in the backend and, since a
profile renders its contracts in :sql:`sorting` order, in the frontend as well.

The organisational unit relation now has a sort column of its own,
:sql:`tx_academicpersons_domain_model_contract.organisational_unit_sorting`,
and orders by it. The profile keeps :sql:`sorting`, so nothing that renders
today changes.

Impact
======

Saving an organisational unit no longer changes the order of any profile, and a
profile no longer changes the order a unit form shows. Both arrangements are
kept side by side.

A contract that joins an organisational unit afterwards is appended to the end
of that unit's list, whether it was created in the unit form, given its unit in
the contract form on a profile, copied, localized, or created and edited in the
profile editing frontend of :composer:`fgtclb/academic-persons-edit`. Changing
the unit moves it to the end of the new one; clearing the unit removes it from
the list altogether.

The up/down sorting of the profile editing frontend is unaffected: it reorders
the contracts of a *profile* and writes :sql:`sorting`, which the profile
relation owns. The arrangement of an organisational unit is not touched by it.

The new column is added by :file:`ext_tables.sql`: **run the database analyzer
once after updating**. Every contract that existed before carries :sql:`0` in
it, so the upgrade wizard *Seed the contract sort order of academic
organisational units* fills it with the order the unit forms show today - the
current :sql:`sorting` of each contract, with :sql:`uid` settling ties. Run it
once; it reports nothing to do afterwards. Until it has run, a contract saved in the backend keeps no position of its own:
the wizard is the one that knows the order the organisational units show today, so a contract
whose list is still unseeded is left to it. Running the wizard again is
harmless: it
appends what has no position yet and never renumbers what has one, so an
arrangement made in a unit form is not reset by it.

Affected Installations
======================

Every installation of this extension that assigns contracts to organisational
units.

.. index:: Backend, Database, TCA, ext:academic_persons
