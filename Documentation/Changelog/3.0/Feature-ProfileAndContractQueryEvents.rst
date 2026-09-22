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
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class ShowOnlyConsentingProfiles
    {
        #[AsEventListener(identifier: 'my-extension/show-only-consenting-profiles')]
        public function __invoke(ModifyProfileQueryEvent $event): void
        {
            $query = $event->getQuery();
            $event->addConstraint($query->equals('publicDisplayConsent', true));
        }
    }

:php:`getPluginControllerActionContext()` says which plugin is asking. It carries the plugin name,
the settings of the content element, the request, the site and its language,
and the content object of the element, so a listener can act for one plugin
only, or read a setting an editor made. The plugin names are the ones the
plugins are registered with — :php:`List`, :php:`ListAndDetail`, :php:`Card`,
:php:`SelectedProfiles`, :php:`SelectedContracts` and :php:`Detail` — not the
content element types:

..  code-block:: php

    // Both of these render a profile list, and they are two plugins.
    if (!in_array($event->getPluginControllerActionContext()?->getPluginName(), ['List', 'ListAndDetail'], true)) {
        return;
    }

:php:`getDemand()` is the list demand behind a query, and :php:`null` for the
lookup of the uids an editor selected — a selected-profiles plugin knows no
demand. :php:`ModifyContractQueryEvent` has no demand at all, for the same
reason: a contract query is only ever such a lookup.

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

Nothing changes in an installation that has no listener.

**The ordering stays with the extension.** :php:`setOrderings()` is called on
the query after the event, so a listener that sets an ordering of its own is
overwritten without a notice. The order of a list is what the editor chose in
the content element; a listener that changed it would override that choice for
every element at once.

..  warning::

    **Use** :php:`addConstraint()`. A condition a listener sets with
    :php:`matching()` on the query is folded into the same logical AND rather
    than dropped — so it still narrows — but :php:`matching()` *replaces*, so
    with two such listeners only the last one to run survives.
    :php:`addConstraint()` collects, and is the only way that composes.

    Everything else on the query object is **live**. The query settings
    (:php:`getQuerySettings()`), the limit and the offset are read when the
    query is parsed, after the event, so a listener that changes them really
    does change the query: switching :php:`setRespectStoragePage(false)` or
    :php:`setIgnoreEnableFields(true)` on widens the result past what the
    content element asked for, and :php:`setLimit()` cuts it. Nothing guards
    this. Add constraints and leave the rest of the query alone.

    The constraint runs on every rendering of the plugin, so an expensive join
    is paid for on every page.

..  index:: Frontend, PHP-API, ext:academic_persons
