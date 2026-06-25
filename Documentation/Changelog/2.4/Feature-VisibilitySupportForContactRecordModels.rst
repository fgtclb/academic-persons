.. include:: /Includes.rst.txt

.. _feature-1782285578:

================================================================
Feature: Visibility (`hidden`) support for contact record models
================================================================

Description
===========

The contact record models gained access to the `hidden` enable field
that already exists on their database tables:

* :php:`\FGTCLB\AcademicPersons\Domain\Model\Address`
* :php:`\FGTCLB\AcademicPersons\Domain\Model\Email`
* :php:`\FGTCLB\AcademicPersons\Domain\Model\PhoneNumber`

Each of them now provides:

* :php:`getHidden(): bool`
* :php:`setHidden(bool $hidden): self`

To work with hidden records the matching repositories
(:php:`AddressRepository`, :php:`EmailRepository`,
:php:`PhoneNumberRepository`) provide two new methods that ignore the
`disabled` enable field:

* :php:`findByContractIncludingHidden(int $contractUid): QueryResultInterface`
* :php:`findByUidIncludingHidden(int $uid): ?<Model>`

These are used by `EXT:academic_persons_edit` (optional) to let profile
owners show and hide their contact records in the frontend, while the
public profile display keeps excluding hidden records via the default
Extbase enable field handling.

Impact
======

The profile synchronization keeps matching and updating existing
contact records even when they are hidden, and no longer resets their
visibility. Integrators can use the new model accessors and repository
methods to handle contact record visibility programmatically.

Affected Installations
======================

All installations using the `EXT:academic_persons` extension starting
with version 2.4.

Migration
=========

No migration is required, except you extended and replaced the extbase
model in projects - then you need to adopt the newly added properties
and setter/getter methods with the same signatures.

.. index:: PHP, Database, Frontend
