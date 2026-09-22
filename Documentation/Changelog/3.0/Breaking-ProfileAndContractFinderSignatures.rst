..  _breaking-profile-and-contract-finder-signatures:

=======================================================
Breaking: The plugins call different repository finders
=======================================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

The plugins hand the context they render in to the repositories, so that a
listener of :ref:`the new query events <feature-profile-and-contract-query-events>`
knows which plugin asked. Three finders are involved:

..  list-table::
    :header-rows: 1

    *   -   Method
        -   3.0
    *   -   :php:`ProfileRepository::findByDemand()`
        -   takes an optional trailing
            :php:`?PluginControllerActionContextInterface $context = null`
    *   -   :php:`ProfileRepository::findByUids()`
        -   unchanged, and no longer called by the selected-profiles plugin
    *   -   :php:`ContractRepository::findByUids()`
        -   unchanged, and no longer called by the selected-contracts plugin

The two uid lookups keep their signature deliberately. Their callers now use
:php:`findByUidsWithContext()` next to them, which differs in nothing but the
context it passes on; :php:`findByUids()` delegates to it without one.

The context is
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
of :guilabel:`academic_base`, not the interface of the same name this extension
ships. The latter is the former minus :php:`getContentObjectRenderer()` and is
the one that goes away in a later major version, so new API is typed against
the shared one. The events of the controller actions are untouched.

Impact
======

**A subclass or XCLASS that overrides** :php:`ProfileRepository::findByDemand()`
**is a fatal error until it adds the parameter.** PHP refuses an override with
a narrower signature, so the installation breaks on the first request after the
update rather than silently.

**An override of either** :php:`findByUids()` **keeps loading and stops being
asked.** The method is still there and still does what the override makes it
do, but the selected-profiles and selected-contracts plugins do not go through
it any more. Nothing reports this: the plugins simply render what the
repository would have returned without the override.

Callers of the three finders that do not override them are unaffected — the
new parameter has a default, and the two uid lookups did not change at all.

Affected Installations
======================

Installations whose own code extends :php:`ProfileRepository` or
:php:`ContractRepository`, by a subclass registered as an Extbase repository or
by an XCLASS. That is the shape the customizations of 2.x used to narrow what
the plugins show, and it is what the query events replace.

Migration
=========

#.  Add the parameter to an override of :php:`findByDemand()`:

    ..  code-block:: php

        public function findByDemand(
            DemandInterface $demand,
            ?PluginControllerActionContextInterface $context = null,
        ): QueryResultInterface {

#.  Move what the override does to a listener of
    :php:`ModifyProfileQueryEvent` or :php:`ModifyContractQueryEvent`, and drop
    the override. A condition an override added to the query is a constraint
    the listener adds, and it then applies to every plugin instead of only the
    ones that happen to call the overridden method — pagination included.

#.  An override of :php:`findByUids()` has no other way back into the
    plugins. Either move it to a listener, or accept that it only serves the
    callers of that method.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_persons
