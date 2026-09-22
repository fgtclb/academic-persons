..  _feature-profile-and-contract-query-events:

=========================================================
Feature: Narrow the profiles and contracts a plugin shows
=========================================================

Description
===========

:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent` and
:php:`\FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent` are PSR-14 events
dispatched immediately before a plugin query is executed. A listener adds
conditions to it, and the records that do not satisfy them are not shown.

The profile event covers every plugin that lists profiles — the list, the
list-and-detail, the card and the selected-profiles plugin — and the contract
event the selected-contracts plugin. Neither is dispatched for the detail view,
which resolves its profile through Extbase argument mapping.

A listener builds its conditions on the query the event carries and hands each
of them to :php:`addConstraint()`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ShowOnlyConsentingProfiles.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent;

    final class ShowOnlyConsentingProfiles
    {
        public function __invoke(ModifyProfileQueryEvent $event): void
        {
            $query = $event->getQuery();
            $event->addConstraint($query->equals('publicDisplayConsent', true));
        }
    }

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    services:
      MyVendor\MyExtension\EventListener\ShowOnlyConsentingProfiles:
        tags:
          - name: event.listener
            identifier: 'my-extension/show-only-consenting-profiles'
            event: FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent

:php:`TYPO3\CMS\Core\Attribute\AsEventListener` does not exist on TYPO3 v12, so
a listener that has to run on both supported versions is registered with the
tag rather than with the attribute.

:php:`getDemand()` of the profile event is the list demand behind a query, and
:php:`null` for the lookup of the uids an editor selected — a selected-profiles
plugin knows no demand. :php:`ModifyContractQueryEvent` has no demand at all,
for the same reason: a contract query is only ever such a lookup.

Impact
======

The conditions a listener adds are combined with the ones the extension builds
itself — the storage folders, the organisational unit and function type filters
of the content element, the letter filter, the hidden records setting and the
language handling — with a logical **AND**. A *constraint* therefore only ever
narrows a result, and it cannot remove a condition the extension put there.

They apply **before pagination**, so the number of pages and the profiles on
each page follow them. A list of five profiles that a listener reduces to two,
paginated two per page, is one page and not three.

Nothing changes in an installation that has no listener, and nothing in this
release is breaking: no repository signature moved, and both uid lookups keep
their signature and their callers.

**The ordering stays with the extension.** :php:`setOrderings()` is called on
the query after the event, so a listener that sets an ordering of its own is
overwritten without a notice. The order of a list is what the editor chose in
the content element.

..  warning::

    **A listener narrows every plugin of the extension alike.** This version
    line does not tell a listener which plugin is being rendered, which
    settings the content element carries or which request it belongs to, so a
    condition cannot be limited to one plugin or one content element. Version
    3.0 adds that context, and a listener written against this release runs
    there unchanged.

..  warning::

    **Use** :php:`addConstraint()`. A condition a listener sets with
    :php:`matching()` on the query is folded into the same logical AND rather
    than dropped — so it still narrows — but :php:`matching()` *replaces*, so
    with two such listeners only the last one to run survives.

    Everything else on the query object is **live**. The query settings
    (:php:`getQuerySettings()`), the limit and the offset are read when the
    query is parsed, after the event, so a listener that changes them really
    does change the query: switching :php:`setRespectStoragePage(false)` or
    :php:`setIgnoreEnableFields(true)` on widens the result past what the
    content element asked for. Nothing guards this.

    The constraint runs on every rendering of the plugin, so an expensive join
    is paid for on every page.

..  index:: Frontend, PHP-API, ext:academic_persons
