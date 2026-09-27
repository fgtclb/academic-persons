.. _feature-managed-fields-read-only-in-the-backend:

==========================================================
Feature: Lock the fields a synchronisation owns per record
==========================================================

Description
===========

The only way to keep editors from changing a synchronised value in the backend
was the :yaml:`readonly` flag of the validations. It locks a field on every
record of a table, so it also locked the e-mail address or phone number an
editor had added by hand.

The new top-level map :yaml:`managedFields` of
:file:`Configuration/AcademicPersons/Settings.yaml` names, per record type,
the fields a synchronisation or an import owns: :yaml:`profile`,
:yaml:`contracts`, :yaml:`emailAddresses`, :yaml:`phoneNumbers` and
:yaml:`physicalAddresses`, each a list of field names as the rest of the file
uses them.

The backend record form renders a managed field read-only on a record that
carries an import identifier, is a record of the default language, and whose
profile is not excluded with :guilabel:`Skip synchronisation`. The field shows
a note naming the import identifier. Records an editor added, every record of
an excluded profile and translations stay editable. See
:ref:`configuration-managed-fields`.

Impact
======

Nothing changes for an installation that does not name a field: the shipped
lists are empty.

The lock applies to the backend form and to the profile editor of
:guilabel:`EXT:academic_persons_edit`, which reads the same map. The
synchronisation, an import and a script keep writing the fields.

A name that matches no field of its record type makes every person record form
fail with the exception ``1790536034``, which names it.

.. index:: Backend, TCA, ext:academic_persons
