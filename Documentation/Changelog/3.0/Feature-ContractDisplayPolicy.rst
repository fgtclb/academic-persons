.. _feature-contract-display-policy:

===============================================
Feature: Choose which contracts a profile shows
===============================================

Description
===========

Every view rendered every contract of a profile. Editors and integrators now
choose which of them:

*   The list and list-and-detail elements offer
    :guilabel:`Contracts per profile` - all of them or only the first -,
    :guilabel:`Only contracts of the selected organisational units and function
    types` and :guilabel:`Only contracts valid today`.
*   The card and selected-profiles elements offer :guilabel:`Contracts per
    profile` and :guilabel:`Only contracts valid today`. They select their
    profiles by hand and apply no unit or function type restriction.
*   The detail view is configured in :file:`Settings.yaml`, per block:
    :yaml:`profile.details.position` and :yaml:`profile.details.contact` take
    :yaml:`contracts` (``all`` or ``first``) and :yaml:`onlyValid`.

The options apply in a fixed order: the unit and function type filter, then
validity, then "first". "First" is therefore the first of the contracts left, in
the editor's order of the profile's contracts. A contract without a start or end
date is not limited on that side.

While "only valid today" applies, a page is cached no longer than until the next
midnight on which a contract that passes the unit and function type filter
starts or stops being valid, so the change is visible on that day rather than
with the next regular cache expiry - at most 24 hours away on TYPO3 v13, and a
year on TYPO3 v14 without :typoscript:`config.cache_period`. The shorter
lifetime is set on the page cache of the request that rendered the contracts;
without the option, the page keeps the lifetime it would have without it.

The selected-contracts element and the contacts element of
:guilabel:`EXT:academic_contacts4pages` render the contract that was chosen,
whatever the options say.

Templates select the contracts through the new ViewHelper
:html:`<persons:contracts>` of the namespace
``http://typo3.org/ns/FGTCLB/AcademicPersons/ViewHelpers``. See
:ref:`configuration-contract-display` and :ref:`templates-contract-selection`.

Impact
======

Nothing changes until an option is set: the defaults show every contract, as
before, and the shipped :file:`Settings.yaml` states them for both detail
blocks.

A project copy of :file:`Partials/Profile/Contract/Item.html`,
:file:`Partials/Profile/PublicProfile/Position.html` or
:file:`Partials/Profile/PublicProfile/Contact.html` that loops over
:html:`{profile.contracts}` ignores the options. Replace the loop with the
ViewHelper, as the shipped partials do, and a template filter of its own can go.

.. index:: Backend, FlexForm, Frontend, Fluid, ext:academic_persons
