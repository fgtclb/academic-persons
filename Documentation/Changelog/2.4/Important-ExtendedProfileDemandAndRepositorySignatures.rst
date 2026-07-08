.. include:: /Includes.rst.txt

.. _important-ace-250-academic-persons:

==========================================================================
Important: Extended `ProfileDemand`, `findByUids()` and demand handling
==========================================================================

Description
===========

To support the new "Show hidden records" plugin option, the person demand
and repository layer gained the following additions. All of them are
non-breaking (new optional parameter / new transport flag with defaults):

* :php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileDemand` has a new
  :php:`showHiddenRecords` transport flag with
  :php:`getShowHiddenRecords(): bool` and
  :php:`setShowHiddenRecords(bool): ProfileDemand` accessors
  (default :php:`false`). It mirrors the existing transport-only
  properties (:php:`storagePages`, :php:`fallbackForNonTranslated`) and is
  likewise announced as a future addition to
  :php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\DemandInterface` (the
  interface carries the commented signatures and the repository guards the
  call with :php:`method_exists()`).
* :php:`\FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository::findByDemand()`
  honours :php:`ProfileDemand::getShowHiddenRecords()` via
  :php:`applyDemandSettings()`.
* :php:`\FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository::findByUids()`
  and
  :php:`\FGTCLB\AcademicPersons\Domain\Repository\ContractRepository::findByUids()`
  gained an appended optional :php:`bool $showHidden = false` parameter.
* :php:`\FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository::findByUidIncludingHidden(int $uid): ?Profile`
  is a new method that resolves a single profile by uid including hidden
  (disabled) records. It is used by the detail view.
* :php:`\FGTCLB\AcademicPersons\Controller\ProfileController` reads
  :php:`$this->settings['showHiddenRecords']` in
  :php:`adoptSettings()`, :php:`cardAction()`,
  :php:`selectedProfilesAction()` and :php:`selectedContractsAction()` and
  threads it into the repositories. The new
  :php:`initializeDetailAction()` re-resolves the `profile` argument via
  :php:`findByUidIncludingHidden()` when the option is enabled, because the
  default Extbase argument mapping respects enable fields.

When the flag/parameter is set, the query ignores only the `disabled`
(`hidden`) enable field via the Extbase query settings; the `deleted`,
`starttime`/`endtime` and `fe_group` restrictions stay in effect.

Impact
======

The change is non-breaking: the new demand flag defaults to :php:`false`,
the new :php:`findByUids()` parameter has a default value, and no existing
method signature changed in an incompatible way. Projects that build a
:php:`ProfileDemand` or call :php:`findByUids()` themselves can opt in via
:php:`setShowHiddenRecords(true)` respectively the new parameter.

Affected Installations
======================

Only installations that extend or replace the :php:`ProfileDemand` DTO,
the :php:`ProfileRepository`, the :php:`ContractRepository` or the
:php:`ProfileController` need to take the additions into account. All
other installations are unaffected.

.. index:: PHP, ext:academic_persons
