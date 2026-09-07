..  _feature-hide-contracts-and-profile-information:

========================================================
Feature: Contracts and profile information can be hidden
========================================================

Description
===========

The two document record models gained access to the ``hidden`` enable field
that already exists on their database tables, exactly as the contact record
models did in 2.4:

*   :php:`\FGTCLB\AcademicPersons\Domain\Model\Contract`
*   :php:`\FGTCLB\AcademicPersons\Domain\Model\ProfileInformation`

Each of them now provides :php:`getHidden(): bool` and
:php:`setHidden(bool $hidden): self`.

To list hidden records the two repositories provide a query that ignores the
``disabled`` enable field and nothing else:

*   :php:`ContractRepository::findByProfileIncludingHidden(Profile $profile)`
*   :php:`ProfileInformationRepository::findByProfileAndTypeIncludingHidden(Profile $profile, string $type)`

Both order by ``sorting`` with ``uid`` breaking ties. Every other query, and
every relation of :php:`Profile`, keeps respecting the enable fields, so the
public views hide a hidden record as before.

The document section vocabulary of :yaml:`documentSections.<section>.actions`
gains ``hide``. It is listed first in the shipped settings of every section,
is offered only when listed, and is withdrawn by :yaml:`readonly: true` with
every other mutating action. `EXT:academic_persons_edit` renders it as the
visibility switch of a row.

Impact
======

No database change: the ``hidden`` columns and their TCA existed already. A
site that overrides the :yaml:`actions` of a section keeps its list - ``hide``
is not implied - and adds ``hide`` where the switch is wanted.

Affected Installations
======================

Installations that configure :yaml:`documentSections` or read the two models
in PHP.

..  index:: PHP-API, YAML, ext:academic_persons, NotScanned
