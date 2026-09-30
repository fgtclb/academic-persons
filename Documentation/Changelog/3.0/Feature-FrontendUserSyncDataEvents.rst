.. _feature-frontend-user-sync-data-events:

======================================================================
Feature: Events to add data to the synchronisation and adjust profiles
======================================================================

Description
===========

The synchronisation of :bash:`academic:createprofiles` and
:bash:`academic:updateprofiles` offered one way to change what it writes: a
listener of :php:`ChooseProfileFactoryEvent` replacing the whole profile
factory. Projects that read a directory service, or that translate a value such
as the gender, shipped a copy of the factory for it and missed every later fix
to the default one. A factory also had to return a profile, so a factory that
found no data for a frontend user still created an empty one.

Two PSR-14 events are dispatched by
:php:`\FGTCLB\AcademicPersons\Profile\AbstractProfileFactory`, so every factory
extending it dispatches them:

*   :php:`\FGTCLB\AcademicPersons\Event\BeforeProfileMappedFromFrontendUserEvent`,
    before the data of a frontend user is mapped. A listener adds or changes
    values, which the :yaml:`frontendUserSync` map then reads like columns, or
    skips. A skip on creation creates nothing for the frontend user. A skip on
    update leaves the profile of that event unchanged and unannounced.
*   :php:`\FGTCLB\AcademicPersons\Event\AfterProfileMappedFromFrontendUserEvent`,
    after the mapping and before the profile is saved. A listener changes the
    profile, with the frontend user data the mapping used at hand.

A value a listener adds is named :samp:`{source}.{key}` by convention, for
example ``ldap.room``, and mapped like a column:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicPersons/Settings.yaml

    frontendUserSync:
      contract:
        room: ldap.room

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
                $event->skip();
                return;
            }
            $event->setFrontendUserData([...$frontendUserData, 'ldap.room' => $entry?->room ?? '']);
        }
    }

The listener fetches what it needs per event and keeps nothing in a property:
it is one object for the whole run and every frontend user of it.

The protected method :php:`createProfileFromFrontendUser()` of
:php:`AbstractProfileFactory` may now return :php:`null`. A factory returning
it creates nothing for that frontend user, and :bash:`academic:createprofiles`
goes on with the next one.

:php:`AbstractProfileFactory` is public API from now on, as the base class of a
project's profile factory. Its protected methods
:php:`createProfileFromFrontendUser()` and
:php:`updateProfileFromFrontendUser()` are the part a subclass implements.

The events, the order they are dispatched in and a listener setting the
employee type are described in :ref:`developers-frontend-user-sync-events` and
:ref:`configuration-frontend-user-sync-relations`.

Impact
======

Nothing changes for an installation without a listener. A factory extending
:php:`AbstractProfileFactory` keeps working: its
:php:`createProfileFromFrontendUser()` may still declare :php:`Profile` as the
return type. A factory implementing :php:`ProfileFactoryInterface` directly
dispatches neither event, and one overriding :php:`createProfileForUser()` or
:php:`updateProfileForUser()` only where it calls the parent method.

A project factory that exists only to add directory data, skip frontend users
or translate values can be replaced by listeners of the two events.

.. index:: CLI, PHP-API, ext:academic_persons
