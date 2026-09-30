<?php

declare(strict_types=1);

namespace TESTS\TestFrontendUserSyncEvents\EventListener;

use FGTCLB\AcademicPersons\Event\AfterProfileMappedFromFrontendUserEvent;
use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use FGTCLB\AcademicPersons\Event\BeforeProfileMappedFromFrontendUserEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Records the events of a synchronisation run in the order they are
 * dispatched. Changes nothing. A test resets it before it acts.
 */
final class RecordSynchronisation
{
    /**
     * @var list<string> Event, action, frontend user and profile uid, per event
     */
    public static array $events = [];

    #[AsEventListener(identifier: 'test-frontend-user-sync-events/record-data', after: 'test-frontend-user-sync-events/add-directory-data')]
    public function recordData(BeforeProfileMappedFromFrontendUserEvent $event): void
    {
        self::$events[] = sprintf(
            'data %s %s profile %s%s',
            $event->getAction()->value,
            $event->getFrontendUserData()['username'],
            $event->getProfile()?->getUid() ?? 'none',
            $event->isSkipped() ? ' skipped' : '',
        );
    }

    #[AsEventListener(identifier: 'test-frontend-user-sync-events/record-mapped')]
    public function recordMapped(AfterProfileMappedFromFrontendUserEvent $event): void
    {
        self::$events[] = sprintf(
            'mapped %s %s profile %s users %d',
            $event->getAction()->value,
            $event->getFrontendUserData()['username'],
            $event->getProfile()->getUid() ?? 'new',
            $event->getProfile()->getFrontendUsers()->count(),
        );
    }

    #[AsEventListener(identifier: 'test-frontend-user-sync-events/record-update')]
    public function recordUpdate(AfterProfileUpdateEvent $event): void
    {
        self::$events[] = sprintf(
            'updated %s profile %d %s',
            $event->getOrigin()->value,
            $event->getProfile()->getUid(),
            $event->getProfile()->getImportIdentifier(),
        );
    }
}
