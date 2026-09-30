<?php

declare(strict_types=1);

namespace TESTS\TestFrontendUserSyncEvents\EventListener;

use FGTCLB\AcademicPersons\Event\AfterProfileMappedFromFrontendUserEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Maps the gender the directory delivers to the values of the profile, and
 * writes the last name the frontend user carries in capitals as a name. The
 * last name is read from the profile, so it shows that the mapping ran first.
 */
final readonly class AdjustMappedProfile
{
    private const GENDERS = ['w' => 'ms', 'm' => 'mr'];

    #[AsEventListener(identifier: 'test-frontend-user-sync-events/adjust-mapped-profile')]
    public function __invoke(AfterProfileMappedFromFrontendUserEvent $event): void
    {
        $profile = $event->getProfile();
        $profile->setGender(self::GENDERS[(string)($event->getFrontendUserData()['ldap.gender'] ?? '')] ?? '');
        $profile->setLastName(ucfirst(mb_strtolower($profile->getLastName())));
    }
}
