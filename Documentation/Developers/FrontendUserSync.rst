..  _developers-frontend-user-sync-events:

================================================
Taking part in the frontend user synchronisation
================================================

The commands :bash:`academic:createprofiles` and
:bash:`academic:updateprofiles` build profiles from the data of frontend
users, following the map described in :ref:`configuration-frontend-user-sync`.
Two events let a project add data of its own and adjust the result, without a
profile factory of its own. :php:`AbstractProfileFactory` dispatches them, so
every factory extending it does, a project's factory included, unless it
overrides :php:`createProfileForUser()` or :php:`updateProfileForUser()`
without calling the parent method.

..  list-table::
    :header-rows: 1

    *   -   Event
        -   Dispatched
        -   A listener can
    *   -   :ref:`BeforeProfileMappedFromFrontendUserEvent <developers-event-before-profile-mapped-from-frontend-user-event>`
        -   before the data of a frontend user is mapped: once per frontend
            user when a profile is created, and once per synchronised profile
            of the frontend user when profiles are updated, not for a profile
            whose :sql:`skip_sync` flag is set
        -   add or change values, or skip the frontend user on creation and
            the profile on update
    *   -   :ref:`AfterProfileMappedFromFrontendUserEvent <developers-event-after-profile-mapped-from-frontend-user-event>`
        -   after the mapping, before the profile is saved
        -   change the profile

For one frontend user, a run goes through these steps:

#.  :ref:`ChooseProfileFactoryEvent <developers-event-choose-profile-factory-event>`
    chooses the factory.
#.  :php:`BeforeProfileMappedFromFrontendUserEvent` carries the data of the
    frontend user, the action (:php:`ProfileActionType::Create` or
    :php:`ProfileActionType::Update`) and, on update, the profile.
    :php:`getProfile()` is :php:`null` when a profile is created.
#.  The factory maps the data onto the profile and its imported contract.
#.  :php:`AfterProfileMappedFromFrontendUserEvent` carries the profile, the
    data the mapping used, with the values added in step 2, and the action.
#.  The profile is saved, and
    :ref:`AfterProfileUpdateEvent <developers-event-after-profile-update-event>`
    announces it, see :ref:`developers-trigger`.

On update, steps 2 to 4 repeat for every synchronised profile of the frontend
user, and step 5 then saves and announces them together. Each event of step 2
starts from the data of the frontend user, not from what a listener set for
the profile before.

Listeners of one event are ordered with the :php:`before` and :php:`after`
arguments of :php:`#[AsEventListener]`. Use TYPO3's attribute,
:php:`TYPO3\CMS\Core\Attribute\AsEventListener`: Symfony's registers nothing,
and the listener never runs.

A factory of its own may decline to create a profile, see
:ref:`configuration-frontend-user-sync-factories`.

The commands also dispatch
:php:`\FGTCLB\AcademicPersons\Service\Event\ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent`
before they set up the environment of a frontend user's site. It is marked
:php:`@internal` as experimental, like the environment handling it belongs
to, and is not documented here.
