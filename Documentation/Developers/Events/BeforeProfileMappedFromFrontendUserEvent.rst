..  _developers-event-before-profile-mapped-from-frontend-user-event:

========================================
BeforeProfileMappedFromFrontendUserEvent
========================================

:php:`\FGTCLB\AcademicPersons\Event\BeforeProfileMappedFromFrontendUserEvent`
hands a listener the data of a frontend user before the frontend user
synchronisation maps it onto a profile. A listener adds values of its own,
which the map then reads like columns, changes values, or skips the frontend
user.

When it is dispatched
=====================

:php:`AbstractProfileFactory` dispatches the event, so every factory extending
it does, a project's factory included, unless it overrides
:php:`createProfileForUser()` or :php:`updateProfileForUser()` without calling
the parent method:

*   :bash:`academic:createprofiles`: once per frontend user, before a profile
    is created for it.
*   :bash:`academic:updateprofiles`: once per synchronised profile of the
    frontend user, before the profile is updated. Not for a profile whose
    :sql:`skip_sync` flag is set.

It is step 2 of the run described in :ref:`developers-frontend-user-sync-events`,
after :ref:`developers-event-choose-profile-factory-event` and before
:ref:`developers-event-after-profile-mapped-from-frontend-user-event`.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   What it does
    *   -   :php:`getFrontendUserData()`, :php:`setFrontendUserData()`
        -   The data the map reads, the :sql:`fe_users` record of the
            frontend user plus what listeners added.
    *   -   :php:`getAction()`
        -   :php:`ProfileActionType::Create` or
            :php:`ProfileActionType::Update`.
    *   -   :php:`getProfile()`
        -   The profile about to be updated, :php:`null` when a profile is
            created.
    *   -   :php:`skip()`, :php:`isSkipped()`
        -   Skips the frontend user on creation and the profile on update,
            and tells whether a listener did.

Adding values
-------------

A value a listener adds is read by the map like a column of the frontend user.
Name it :samp:`{source}.{key}`, for example ``ldap.room``: no column of a TYPO3
table contains a dot, so such a key never hides a real column. The convention
is not enforced. Keep :sql:`uid` and :sql:`pid` as they are, the default
factory stores the profile on the page :sql:`pid` and names the imported
records after :sql:`uid`.

The default factory refuses a map that reads a key the data does not have,
with the code ``1790142326``. A listener that adds a key the map reads
therefore adds it for every frontend user, as ``''`` when its source has no
value.

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicPersons/Settings.yaml

    frontendUserSync:
      contract:
        room: ldap.room

Skipping
--------

After :php:`skip()` on creation, :bash:`academic:createprofiles` creates no
profile for the frontend user and goes on with the next one. The next run asks
again, because the frontend user still has no profile. On update, the event
belongs to one profile, and :bash:`academic:updateprofiles` leaves that profile
as it is and does not announce it, like a profile whose :sql:`skip_sync` flag
is set. The other profiles of the frontend user are updated as usual, so a
listener that should skip the whole frontend user skips every one of its
events. Listeners after the one that skipped still run, and :php:`isSkipped()`
tells them.

Example
=======

A site reads the room and the gender of its staff from a directory service.
Listeners are shared services, one object for the whole run and every frontend
user of it. Keep nothing of one frontend user in a property of the listener,
and fetch what it needs per event:

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/EventListener/AddDirectoryData.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MySitepackage\EventListener;

    use FGTCLB\AcademicPersons\Event\BeforeProfileMappedFromFrontendUserEvent;
    use FGTCLB\AcademicPersons\Profile\ProfileActionType;
    use MyVendor\MySitepackage\Directory\DirectoryClient;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class AddDirectoryData
    {
        public function __construct(
            private DirectoryClient $directoryClient,
        ) {}

        #[AsEventListener(identifier: 'my-sitepackage/add-directory-data')]
        public function __invoke(BeforeProfileMappedFromFrontendUserEvent $event): void
        {
            $frontendUserData = $event->getFrontendUserData();
            $entry = $this->directoryClient->findByUsername((string)$frontendUserData['username']);
            if ($entry === null && $event->getAction() === ProfileActionType::Create) {
                // Unknown to the directory: no profile is created for the frontend user.
                $event->skip();
                return;
            }
            // An existing profile keeps being updated, with empty directory values when the entry is gone.
            $event->setFrontendUserData([
                ...$frontendUserData,
                'ldap.room' => $entry?->room ?? '',
                'ldap.gender' => $entry?->gender ?? '',
            ]);
        }
    }

The value ``ldap.gender`` is translated into one the profile offers by the
example of :ref:`developers-event-after-profile-mapped-from-frontend-user-event`.

Rules and pitfalls
==================

*   On update, each event starts from the data of the frontend user, not from
    what a listener set for the profile before.
*   A factory that implements :php:`ProfileFactoryInterface` directly
    dispatches the event only if it does so itself.
*   Listeners of one event are ordered with the :php:`before` and
    :php:`after` arguments of :php:`#[AsEventListener]`. Use TYPO3's
    attribute, :php:`TYPO3\CMS\Core\Attribute\AsEventListener`: Symfony's
    registers nothing, and the listener never runs.

Introduced in
=============

*   :ref:`feature-frontend-user-sync-data-events` introduced the event.
