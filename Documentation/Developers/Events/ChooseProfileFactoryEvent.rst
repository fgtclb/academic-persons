..  _developers-event-choose-profile-factory-event:

=========================
ChooseProfileFactoryEvent
=========================

:php:`\FGTCLB\AcademicPersons\Event\ChooseProfileFactoryEvent` lets a listener
choose the profile factory that creates or updates the profiles of a frontend
user in the frontend user synchronisation. Without a listener, the default
factory :php:`\FGTCLB\AcademicPersons\Profile\ProfileFactory` does it.

Before a factory of its own, check whether a listener of the mapping events is
enough, see :ref:`developers-frontend-user-sync-events`: a factory replaces the
default one as a whole, and does not pick up later fixes to it.

When it is dispatched
=====================

The commands :bash:`academic:createprofiles` and
:bash:`academic:updateprofiles` dispatch the event once for every frontend user
they process, after the environment of the frontend user's site is set up and
before anything else happens for that user. The factory the event names is then
asked whether it creates or updates profiles for the user at all, and does it.
It is the first step of the run described in
:ref:`developers-frontend-user-sync-events`.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   What it does
    *   -   :php:`getFrontendUserAuthentication()`
        -   The frontend user, with its record in :php:`$user` and its groups
            fetched.
    *   -   :php:`getAction()`
        -   :php:`ProfileActionType::Create` for
            :bash:`academic:createprofiles`, :php:`ProfileActionType::Update`
            for :bash:`academic:updateprofiles`.
    *   -   :php:`getProfileFactory()`
        -   The factory a listener before chose, or :php:`null`.
    *   -   :php:`setProfileFactory()`
        -   Chooses the factory, an implementation of
            :php:`\FGTCLB\AcademicPersons\Profile\ProfileFactoryInterface`.

Example
=======

A site keeps the frontend users of its staff directory on one storage folder
and builds their profiles with a factory of its own, which reads a directory
service. Every other frontend user keeps the default factory:

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/EventListener/ChooseDirectoryProfileFactory.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MySitepackage\EventListener;

    use FGTCLB\AcademicPersons\Event\ChooseProfileFactoryEvent;
    use MyVendor\MySitepackage\Profile\DirectoryProfileFactory;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class ChooseDirectoryProfileFactory
    {
        private const STAFF_DIRECTORY_FOLDER = 42;

        public function __construct(
            private DirectoryProfileFactory $directoryProfileFactory,
        ) {}

        #[AsEventListener(identifier: 'my-sitepackage/choose-directory-profile-factory')]
        public function __invoke(ChooseProfileFactoryEvent $event): void
        {
            $frontendUser = $event->getFrontendUserAuthentication()->user ?? [];
            if ((int)($frontendUser['pid'] ?? 0) !== self::STAFF_DIRECTORY_FOLDER) {
                return;
            }
            $event->setProfileFactory($this->directoryProfileFactory);
        }
    }

:php:`DirectoryProfileFactory` extends
:php:`\FGTCLB\AcademicPersons\Profile\AbstractProfileFactory`, so the mapping
events are dispatched for its profiles as well. How such a factory is built is
described in :ref:`configuration-frontend-user-sync-factories`.

Rules and pitfalls
==================

*   The last listener that calls :php:`setProfileFactory()` wins. A listener
    that only chooses for some frontend users returns early for the others and
    leaves the choice of a listener before it alone.
*   A factory that implements :php:`ProfileFactoryInterface` directly, without
    extending :php:`AbstractProfileFactory`, dispatches neither
    :php:`BeforeProfileMappedFromFrontendUserEvent` nor
    :php:`AfterProfileMappedFromFrontendUserEvent`, and announces nothing
    unless it dispatches :php:`AfterProfileUpdateEvent` itself.
*   A chosen factory may ignore the extension configuration that makes the
    default factory create profiles at all.
*   Listeners are shared services, one object for the whole run. Keep nothing
    of one frontend user in a property of the listener.

Introduced in
=============

The event is part of the extension since its first release. Later changes:

*   :ref:`feature-frontend-user-sync-data-events` added the mapping events,
    which make a factory of its own unnecessary for most projects.
*   :ref:`important-ace-845-academic-persons` (2.4) names the default factory
    in the hint the commands print.
