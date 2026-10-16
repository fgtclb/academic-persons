..  _developers-event-modify-profile-query-event:

=======================
ModifyProfileQueryEvent
=======================

..  _developers-query-events:

Narrowing what a plugin shows
=============================

:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent` and
:php:`\FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent` are dispatched
immediately before a plugin query is executed, and a listener adds conditions
to it. They are the supported way to make a plugin show fewer records than it
would, by a consent flag, a site the profile belongs to or an editorial state.

..  list-table::
    :header-rows: 1

    *   -   Event
        -   Dispatched for
    *   -   :php:`ModifyProfileQueryEvent`
        -   the profile query of the list, list-and-detail and card plugins,
            the letter query of the two list plugins (see
            :ref:`developers-letter-availability`), and the uid lookup of the
            selected-profiles plugin
    *   -   :php:`ModifyContractQueryEvent`
        -   the uid lookup of the selected-contracts plugin

When it is dispatched
=====================

:php:`ProfileRepository` dispatches the event once for every profile query it
runs for a plugin, after the query settings and the conditions of the plugin
are prepared and before they are applied:

*   **List and list-and-detail plugins**, list action: once for the profiles
    of the list, after :ref:`developers-event-modify-profile-demand-event`.
    While the content element shows the letter navigation and no profiles are
    selected by hand, once more for the letters, see
    :ref:`developers-letter-availability`.
*   **Card plugin**: once for the profiles the card shows, also after
    :php:`ModifyProfileDemandEvent`.
*   **Selected-profiles plugin**: once for the uid lookup of the selected
    profiles. No demand event precedes it.

The detail view is not covered, see the rules below.
:php:`ModifyPluginViewEvent` follows when the action renders, see
:ref:`developers-event-modify-plugin-view-event`.

Code that calls :php:`ProfileRepository::findByDemand()` or
:php:`ProfileRepository::findByUids()` outside the plugins dispatches the
event as well, without a plugin context.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   What it does
    *   -   :php:`getQuery()`
        -   The Extbase :php:`QueryInterface` of the profiles, to build the
            conditions on.
    *   -   :php:`addConstraint()`
        -   Adds a condition. Every listener may add any number of them.
    *   -   :php:`getConstraints()`
        -   The conditions the listeners added so far.
    *   -   :php:`getDemand()`
        -   The list demand the query is built from, after the listeners of
            :php:`ModifyProfileDemandEvent`. :php:`null` for the uid lookup
            of the selected-profiles plugin, which knows no demand.
    *   -   :php:`getPluginControllerActionContext()`
        -   The plugin the query is rendered for, see
            :ref:`developers-plugin-action-context`. :php:`null` where the
            query has no plugin behind it.

The context carries the plugin name, the settings of the content element, the
request, the site, the language and the content object.
:ref:`developers-event-modify-contract-query-event` has the same methods
apart from :php:`getDemand()`.

Example
=======

A site that lists only the profiles of persons who agreed to be published
keeps the agreement in a column of its own, here :sql:`public_display_consent`
on the profile table. The listener restricts the two list plugins to those
profiles and leaves the card and the selected profiles alone, which an editor
fills by hand:

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
            // The two list plugins only. The card plugin of the same page keeps everything.
            if (!in_array($event->getPluginControllerActionContext()?->getPluginName(), ['List', 'ListAndDetail'], true)) {
                return;
            }
            $query = $event->getQuery();
            $event->addConstraint($query->equals('publicDisplayConsent', true));
        }
    }

:php:`getPluginName()` is the name a plugin is **registered** with, not its
content element type. The six names are listed in
:ref:`developers-plugin-names`. The letter query of the list carries the same
context as the list query, so the listener narrows the letters in the same
way.

Rules and pitfalls
==================

The collected conditions are combined with a logical **AND** with the ones the
extension builds itself: the storage folders (of a list without a selection
by hand), the organisational unit and function type filters of the content
element, the letter filter, hidden records and the language handling. They are applied **before pagination**.
A *constraint* therefore only ever narrows a result, and it cannot drop a
condition the extension put there.

..  warning::

    That guarantee covers the constraints, and nothing else about the query.

    :php:`setOrderings()` is called on the query *after* the event, so a
    listener that sets an ordering is overwritten without a notice. The
    ordering belongs to the plugin: it is what the editor chose in the content
    element, and a listener that changed it would override that choice for
    every element at once.

    A condition set with :php:`matching()` instead of :php:`addConstraint()` is
    **folded into the same AND** rather than dropped, so it narrows like any
    other. Use :php:`addConstraint()` anyway: :php:`matching()` replaces what
    is on the query, so two listeners doing it leave only the last one's
    condition standing, while :php:`addConstraint()` collects.

    The **query settings, the limit and the offset are live**.
    :php:`getQuerySettings()` hands out the object the query is parsed from,
    and it is read after this event, so a listener that calls
    :php:`setRespectStoragePage(false)` or :php:`setIgnoreEnableFields(true)`
    on it widens the result past the content element, and a listener that
    calls :php:`setLimit()` cuts it. Nothing guards any of this. It is the same
    object because a listener needs it to build an expression in the first
    place. Add constraints and leave the rest of the query alone.

The detail view is not covered: neither event is dispatched for it. It resolves
its profile through Extbase argument mapping, and the one finder it does use,
for a hidden profile while the plugin's "show hidden records" is on, builds
its own query and never goes through the shared path these events sit in. So a
profile a listener hides from the lists is still reachable through its own
detail URL. A condition that has to hold there as well belongs in an access
check, not in these events.

A listener that answers the list query and the letter query differently makes
the letter navigation offer letters that lead to an empty list, or hide letters
that do not. Answer both calls alike.

A project that narrowed the plugins by subclassing or XCLASSing the
repositories moves that code here, see
:ref:`breaking-profile-and-contract-finder-signatures`.

Introduced in
=============

*   :ref:`feature-profile-and-contract-query-events` (2.4) introduced the
    event.
*   :ref:`feature-ace-715-academic-persons-query-event-context` added the
    plugin context in 3.0.
*   :ref:`breaking-profile-and-contract-finder-signatures` describes the
    finders that hand the context over.
*   :ref:`feature-letter-navigation-availability` dispatches it for the
    letters as well.
*   :ref:`important-1790607226` makes the list query see a letter switching
    the pagination off.
