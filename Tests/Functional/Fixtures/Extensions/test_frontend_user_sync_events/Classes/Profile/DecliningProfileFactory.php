<?php

declare(strict_types=1);

namespace TESTS\TestFrontendUserSyncEvents\Profile;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Profile\AbstractProfileFactory;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * A project factory that finds no data for the user it is chosen for.
 */
#[Autoconfigure(public: true)]
final class DecliningProfileFactory extends AbstractProfileFactory
{
    public function shouldCreateProfileForUser(FrontendUserAuthentication $frontendUserAuthentication): bool
    {
        return true;
    }

    protected function createProfileFromFrontendUser(array $frontendUserData): ?Profile
    {
        return null;
    }
}
