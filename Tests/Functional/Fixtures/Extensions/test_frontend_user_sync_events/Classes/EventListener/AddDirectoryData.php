<?php

declare(strict_types=1);

namespace TESTS\TestFrontendUserSyncEvents\EventListener;

use FGTCLB\AcademicPersons\Event\BeforeProfileMappedFromFrontendUserEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Stands in for a directory service: adds the room, the gender and the status
 * of a user, empty values for a user it does not know, and skips a user who
 * has left. The values of the skipped user are added before the skip, so a
 * skip that is not honoured shows in the profile. The directory also lists
 * profile 83 as archived, so that one profile of a user with two is skipped on
 * update. It refuses data that already carries its values: every event starts
 * from the frontend user.
 */
final readonly class AddDirectoryData
{
    private const DIRECTORY = [
        'listed' => ['ldap.room' => 'A 2.14', 'ldap.gender' => 'w', 'ldap.status' => 'active'],
        'skipped' => ['ldap.room' => 'D 4.01', 'ldap.gender' => 'm', 'ldap.status' => 'left'],
    ];

    private const UNKNOWN = ['ldap.room' => '', 'ldap.gender' => '', 'ldap.status' => ''];

    private const ARCHIVED_PROFILES = [83];

    #[AsEventListener(identifier: 'test-frontend-user-sync-events/add-directory-data')]
    public function __invoke(BeforeProfileMappedFromFrontendUserEvent $event): void
    {
        $frontendUserData = $event->getFrontendUserData();
        if (array_key_exists('ldap.status', $frontendUserData)) {
            throw new \LogicException('The data of the frontend user arrived with the values of an earlier event.', 1790810001);
        }
        $directoryData = self::DIRECTORY[(string)$frontendUserData['username']] ?? self::UNKNOWN;
        $event->setFrontendUserData([...$frontendUserData, ...$directoryData]);
        if ($directoryData['ldap.status'] === 'left'
            || in_array($event->getProfile()?->getUid(), self::ARCHIVED_PROFILES, true)
        ) {
            $event->skip();
        }
    }
}
