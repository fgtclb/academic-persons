..  _developers-event-modify-profile-demand-event:

========================
ModifyProfileDemandEvent
========================

:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileDemandEvent` hands a listener
a copy of the demand a profile list is about to be queried with, before the
query is built from it. A listener changes that copy or replaces it, and the
query, the letters of the letter navigation and the page cache lifetime
follow the change.

When it is dispatched
=====================

:php:`ProfileRepository` dispatches the event before it builds a query from a
demand:

*   **List and list-and-detail plugins**, list action: once for the profiles
    of the list, on a copy of the demand of the request after the settings of
    the content element and the filters of the visitor were adopted.
    :ref:`developers-event-modify-profile-query-event` follows.
*   Once more, on a copy of that demand, while the content element shows only
    contracts valid today, for the next day on which the list changes. That
    day caps the page cache lifetime. A demand with profiles selected by hand
    is not asked.
*   Once more, on a copy of that demand without the letter, while the content
    element shows the letter navigation and no profiles are selected by hand,
    for the letters that lead to a list that is not empty, see
    :ref:`developers-letter-availability`.
*   **Card plugin**: once for the profiles of the card, on a copy of a demand
    of its own that carries the selection of the card, the language fallback
    and the hidden records setting, and nothing else.

Every call receives a copy of the demand the plugin built, never one a
listener changed on an earlier call of the same rendering.

The detail, selected-profiles and selected-contracts plugins do not dispatch
it. Neither does the filter form of the list, which only redirects to the list
it asks for.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   What it does
    *   -   :php:`getDemand()`
        -   A copy of the demand, typed :php:`DemandInterface`, which the
            listener may change in place. The plugins pass a
            :php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileDemand`.
    *   -   :php:`setDemand()`
        -   Replaces the demand the query is built from. Not needed for a
            change made in place.

What the query reads from the demand: the storage folders, the profiles
selected by hand, the grouping, the sort field and its direction, the letter,
the function types and organisational units of the content element, the two
filters of the visitor, the language fallback, the hidden records setting and
whether only contracts valid today count. A sort field or a grouping that is
not one of the allowed values of the extension configuration is ignored.

The event carries no plugin context. A listener that has to tell the plugins
apart reads the demand, a selection by hand for example, or uses
:ref:`developers-event-modify-profile-query-event`, which carries the context.

Example
=======

A site wants every list for which an editor chose :guilabel:`No sorting` to
come out by last name rather than in the order the profiles were created:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/SortUnsortedListsByLastName.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPersons\Event\ModifyProfileDemandEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class SortUnsortedListsByLastName
    {
        #[AsEventListener(identifier: 'my-extension/sort-unsorted-lists-by-last-name')]
        public function __invoke(ModifyProfileDemandEvent $event): void
        {
            $demand = $event->getDemand();
            // A selection by hand keeps the order of the selection anyway.
            if ($demand->getSortBy() !== '' || $demand->getProfileList() !== '') {
                return;
            }
            // The demand is a copy, so it is changed in place.
            $demand->setSortBy('lastName');
            $demand->setSortByDirection('asc');
        }
    }

Rules and pitfalls
==================

*   A changed demand reaches the repository only, whether the listener
    changed it in place or replaced it. It decides which profiles are
    queried, which letters are offered and how long the page is cached. The
    active letter, the pagination, the page, the order of a selection by
    hand, the view mode, the links of the navigation and the demand the
    template receives keep following the demand of the request, see
    :ref:`developers-navigation-links`.
*   A selection by hand the listener sets decides the query only. The list
    renders its profiles in the order of their uids, and offers the letter
    navigation while the content element enables it, every letter leading to
    the same profiles. A list is ordered by hand only by a selection of the
    content element.
*   Answer all calls for one list alike. A listener that narrows the list but
    not the letters makes the navigation offer letters that lead to an empty
    list.
*   The visitor filters arrive checked: a function type or organisational
    unit the content element does not offer is `0` by then. A value the
    listener sets itself is applied as it is.

Introduced in
=============

The event is part of the extension since its first release. Later changes:

*   :ref:`important-ace-439-academic-persons` (2.4) normalises the sort
    direction a listener sets.
*   :ref:`important-ace-920-academic-persons` (2.4) hands the listeners of
    the list query a copy, so a change in place reaches the query only. The
    two further calls of 3.0 work on copies from the start.
*   :ref:`breaking-removed-profile-view-events` made it the place to change
    which profiles a list queries, instead of the removed list event.
*   :ref:`feature-letter-navigation-availability` dispatches it for the
    letters as well.
*   :ref:`feature-1790802602` added the two visitor filters to the demand.
*   :ref:`important-1790607226` describes when a letter switches the
    pagination off.
