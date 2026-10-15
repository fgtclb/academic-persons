..  _developers-event-after-profile-update-event:

..  _developers-trigger:

=======================
AfterProfileUpdateEvent
=======================

:php:`\FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent` is a PSR-14 event
announcing that a profile aggregate, the profile record or one of its child
records, has changed and was persisted. It is the trigger of the translation
synchronisation and of the slug of :composer:`fgtclb/academic-persons-edit`,
and the place for a project to react to every change of a profile, whatever
wrote it.

When it is dispatched
=====================

*   After a profile is auto-created for a frontend user
    (:php:`AbstractProfileFactory::createProfileForUser()`, also reached by the
    :bash:`academic:createprofiles` command).
*   After a profile is updated from its frontend user record
    (:php:`AbstractProfileFactory::updateProfileForUser()`, command
    :bash:`academic:updateprofiles`), per profile the update runs through,
    even when every value already matched. A profile whose :sql:`skip_sync`
    flag is set, or that a listener of :php:`BeforeProfileMappedFromFrontendUserEvent`
    skips, is neither updated nor announced.
*   By `EXT:academic_persons_edit` after every persisting frontend edit action.
*   After a **DataHandler save** of a live, default-language profile: the
    backend form, and any code that writes profiles through the DataHandler.
    Every default-language profile in the run's datamap is announced once per
    run, after all of the run is written. Saves of translations alone, saves
    in a workspace, commands such as copy, move or localize, and a save of
    only a child record, such as a contract edited on its own, are not
    announced.

What a listener reads
=====================

The event is read only. Besides the profile it carries the site the profile
belongs to, and the origin of the update:

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns
    *   -   :php:`getProfile()`
        -   The persisted profile in the default language.
    *   -   :php:`getSite()`
        -   The site the profile belongs to, when the dispatcher knows it, and
            :php:`null` otherwise.
    *   -   :php:`getOrigin()`
        -   Where the update came from, a case of
            :php:`\FGTCLB\AcademicPersons\Event\ProfileUpdateOrigin`.

..  list-table::
    :header-rows: 1

    *   - :php:`getOrigin()`
        - Dispatched by
        - :php:`getSite()`
    *   - :php:`ProfileUpdateOrigin::Creation`
        - :bash:`academic:createprofiles`
        - :php:`null`
    *   - :php:`ProfileUpdateOrigin::Synchronization`
        - :bash:`academic:updateprofiles`
        - :php:`null`
    *   - :php:`ProfileUpdateOrigin::FrontendEditing`
        - the profile editing plugin of `EXT:academic_persons_edit`
        - the site of the request
    *   - :php:`ProfileUpdateOrigin::Backend`
        - a DataHandler save
        - the site of the profile's page, :php:`null` when it belongs to none
    *   - :php:`ProfileUpdateOrigin::Import`
        - a DataHandler save of a run marked as an import (below)
        - the site of the profile's page, :php:`null` when it belongs to none
    *   - :php:`ProfileUpdateOrigin::Unknown`
        - code that passes no origin, such as code written for 2.x
        - whatever it passes, usually :php:`null`

The case set of :php:`ProfileUpdateOrigin` is fixed, so a listener may
:php:`match` over it exhaustively.

The extensions register three listeners of their own, which a project listener
orders itself against with the :php:`before` and :php:`after` arguments of
:php:`#[AsEventListener]`:

..  list-table::
    :header-rows: 1

    *   -   Identifier
        -   Extension
        -   What it does
    *   -   `academicPersonsUpdateProfileImageMetadata`
        -   :guilabel:`academic_persons`
        -   Writes the image metadata of the profile, except for the origins
            :php:`Backend` and :php:`Import`, whose DataHandler run has written
            it already, see :ref:`developers-event-modify-profile-image-metadata-event`.
    *   -   `generateSlugForProfile`
        -   :guilabel:`academic_persons_edit`
        -   Regenerates the slug of the profile.
    *   -   `syncChangesToTranslations`
        -   :guilabel:`academic_persons_edit`
        -   Synchronises the translations, see
            :ref:`developers-synchronisation`.

Example
=======

A site renders the profiles in a view of its own, a teaser element for example,
and tags the cached pages with ``my_profile_teaser_<uid>``. The listener flushes
them whenever a profile changes, whatever changed it:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/FlushProfileTeasers.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Cache\CacheManager;

    final readonly class FlushProfileTeasers
    {
        public function __construct(
            private CacheManager $cacheManager,
        ) {}

        #[AsEventListener(identifier: 'my-extension/flush-profile-teasers')]
        public function __invoke(AfterProfileUpdateEvent $event): void
        {
            $profileUid = (int)$event->getProfile()->getUid();
            $this->cacheManager->flushCachesInGroupByTag('pages', 'my_profile_teaser_' . $profileUid);
        }
    }

The pages of the plugins of this extension are flushed by the extension
itself.

Rules and pitfalls
==================

The dispatch contract, for code that dispatches the event itself:

*   The event carries the **persisted default language profile**: its
    :php:`getUid()` returns a real uid, and the record is not a translation
    overlay. Listeners read the database, not the object, so all changes must
    be persisted before dispatching.
*   The site is optional. Without one the synchronisation listener of
    `EXT:academic_persons_edit` determines it from the site of the global
    request, then from the pid, and skips the event silently when it cannot.
    An event of origin :php:`ProfileUpdateOrigin::Backend` or
    :php:`ProfileUpdateOrigin::Import` without a site is not synchronised at
    all: the DataHandler hook passes none only when the profile's page belongs
    to no site, and the site of a backend request is the one of the page
    selected in the page tree.
*   A project that dispatched the event from its own DataHandler hook to have
    backend saves synchronised must remove that hook: the save is announced
    by this extension now, and the hook would announce it a second time.

For a listener:

*   A large import is announced profile by profile, synchronously, in the
    request that writes it. A listener that does expensive work recognises an
    import by the origin :php:`ProfileUpdateOrigin::Import` and defers it.
*   A listener that writes profiles through the DataHandler marks its run as
    :php:`ProfileWriteCorrelation::Internal`, see below, or its write is
    announced again.

..  _developers-profile-write-correlation:

Marking a DataHandler run: ProfileWriteCorrelation
--------------------------------------------------

The DataHandler hook of this extension recognises two kinds of run by an
aspect of the run's correlation id, which
:php:`\FGTCLB\AcademicPersons\DataHandling\ProfileWriteCorrelation` creates:

:php:`ProfileWriteCorrelation::Import`
    An import. Its saves are announced like backend saves, once per profile
    and synchronously, with the origin :php:`ProfileUpdateOrigin::Import`, so
    that a listener can tell them apart or defer its own work. Every profile
    is synchronised in the same request, which is the cost of a large import.

:php:`ProfileWriteCorrelation::Internal`
    A write of this extension itself, by the translation synchronisation or
    by the profile image writes. Such a run is never announced: the update it
    belongs to is announced by the code that started it. A listener of
    :php:`AfterProfileUpdateEvent` that writes profiles through the
    DataHandler marks its run the same way: an announcement from the frontend
    or from a command has no DataHandler run around it, and the listener's
    write would be announced as a save of its own.

The synchronisation runs as the backend user of the save. An editor without
access to a target language gets no translation in it, and the DataHandler error
is logged, not shown.

A DataHandler run a listener starts from inside a backend save is nested in
the save's run, and the DataHandler flushes the reference index of the
outermost run only. Such a run passes a :php:`ReferenceIndexUpdater` of its
own to :php:`start()` and calls :php:`update()` on it afterwards, as the
synchronisation does.

The mark is set after :php:`start()`, which replaces the correlation id:

..  code-block:: php

    use FGTCLB\AcademicPersons\DataHandling\ProfileWriteCorrelation;
    use TYPO3\CMS\Core\DataHandling\DataHandler;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataHandler->start($datamap, []);
    $dataHandler->setCorrelationId(ProfileWriteCorrelation::Import->create());
    $dataHandler->process_datamap();

A DataHandler run started from inside another DataHandler run, from one of
its hooks or from a listener of the announcement it made, is never announced,
marked or not.

Introduced in
=============

The event is part of the extension since its first release. Later changes:

*   :ref:`important-ace-610-academic-persons` (2.4) synchronises a created
    profile into its translations again.
*   :ref:`feature-profile-updates-from-user-data-announce-themselves`
    announces the updates of :bash:`academic:updateprofiles`.
*   :ref:`important-backend-saves-announce-profile-updates` announces backend
    saves and imports.
*   :ref:`feature-profile-update-event-carries-site-and-origin` added the site
    and the origin.
