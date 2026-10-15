..  _developers-event-after-profile-mapped-from-frontend-user-event:

=======================================
AfterProfileMappedFromFrontendUserEvent
=======================================

:php:`\FGTCLB\AcademicPersons\Event\AfterProfileMappedFromFrontendUserEvent`
hands a listener the profile the frontend user synchronisation has just mapped
from the data of a frontend user, before it is saved. A listener translates a
value of the source into one the profile offers, or derives a value from
others. What it sets on the profile and its contracts is saved with it.

When it is dispatched
=====================

:php:`AbstractProfileFactory` dispatches the event, under the same conditions
as :ref:`developers-event-before-profile-mapped-from-frontend-user-event`:

*   :bash:`academic:createprofiles`: once per frontend user, after the new
    profile was mapped and attached to the frontend user. Not when a listener
    skipped the frontend user, and not when the factory has no profile for it.
*   :bash:`academic:updateprofiles`: once per synchronised profile of the
    frontend user, after the profile was mapped. Not for a skipped profile.

It is step 4 of the run described in :ref:`developers-frontend-user-sync-events`.
The profile is saved and announced with
:ref:`developers-event-after-profile-update-event` afterwards.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   What it does
    *   -   :php:`getProfile()`
        -   The mapped profile. The event has no setter: the listener changes
            the object, and the change is saved.
    *   -   :php:`getFrontendUserData()`
        -   The data the mapping used, with the values the listeners of
            :php:`BeforeProfileMappedFromFrontendUserEvent` added.
    *   -   :php:`getAction()`
        -   :php:`ProfileActionType::Create` or
            :php:`ProfileActionType::Update`.

Example
=======

A site's directory service delivers the gender as ``w``, ``m`` or ``d``, added
to the data as ``ldap.gender`` by the example of
:ref:`developers-event-before-profile-mapped-from-frontend-user-event`. The
listener translates it into the values the profile offers:

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/EventListener/MapGender.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MySitepackage\EventListener;

    use FGTCLB\AcademicPersons\Event\AfterProfileMappedFromFrontendUserEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class MapGender
    {
        private const GENDERS = ['w' => 'ms', 'm' => 'mr', 'd' => 'diverse'];

        #[AsEventListener(identifier: 'my-sitepackage/map-gender')]
        public function __invoke(AfterProfileMappedFromFrontendUserEvent $event): void
        {
            $source = (string)($event->getFrontendUserData()['ldap.gender'] ?? '');
            $event->getProfile()->setGender(self::GENDERS[$source] ?? '');
        }
    }

A listener that sets the employee type of the imported contract from the
frontend user data is described in
:ref:`configuration-frontend-user-sync-relations`.

Rules and pitfalls
==================

*   A value the map writes is written on every run. A listener that changes
    it has to change it on every run as well, or the next run writes the
    mapped value again.
*   The profile is not saved yet. Do not persist it from the listener, the
    factory saves it after all listeners ran.
*   On update the event is dispatched once per profile, so a frontend user
    with two synchronised profiles reaches the listener twice.

Introduced in
=============

*   :ref:`feature-frontend-user-sync-data-events` introduced the event.
