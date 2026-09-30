<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Event;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Profile\ProfileActionType;

/**
 * Dispatched by the frontend user synchronisation after the data of a frontend
 * user was mapped onto a profile, before the profile is saved. A listener
 * changes the profile, for example to translate a value of the source into one
 * the profile offers. The frontend user data is the one the mapping used, with
 * the values the listeners of {@see BeforeProfileMappedFromFrontendUserEvent} added.
 *
 * @api
 */
final class AfterProfileMappedFromFrontendUserEvent
{
    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    public function __construct(
        private readonly Profile $profile,
        private readonly array $frontendUserData,
        private readonly ProfileActionType $action,
    ) {}

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    /**
     * @return array<string, int|string|null>
     */
    public function getFrontendUserData(): array
    {
        return $this->frontendUserData;
    }

    public function getAction(): ProfileActionType
    {
        return $this->action;
    }
}
