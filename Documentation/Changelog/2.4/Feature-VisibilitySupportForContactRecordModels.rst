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

:php:`findByContractIncludingHidden()` orders the records by their
:sql:`sorting`, with :sql:`uid` ascending breaking ties between records that
share a :sql:`sorting` value.

These are used by the optional `EXT:academic_persons_edit` to let profile
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

All installations using `EXT:academic_persons`.

Migration
=========

No migration is required. A project that extends or replaces one of these
Extbase models has to provide the new :php:`hidden` property and its getter
and setter with the same signatures.

.. index:: PHP, Database, Frontend, ext:academic_persons
