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
 * Dispatched by the frontend user synchronisation before the data of a frontend
 * user is mapped onto a profile: once per frontend user when a profile is
 * created, and once per synchronised profile of the frontend user when profiles
 * are updated. A profile excluded with `skip_sync` gets no event.
 *
 * A listener adds or changes values, which the profile factory then maps like
 * the columns of the frontend user. An added key follows the convention
 * `<source>.<key>`, `ldap.room` for example, so it can never collide with a
 * column. A listener can also skip. On creation, nothing is created for the
 * frontend user. On update, the profile of this event is neither changed nor
 * announced, and the other profiles of the frontend user are updated as usual.
 * Listeners after the one that skipped still run and can ask
 * {@see self::isSkipped()}.
 *
 * @api
 */
final class BeforeProfileMappedFromFrontendUserEvent
{
    private bool $skipped = false;

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    public function __construct(
        private array $frontendUserData,
        private readonly ProfileActionType $action,
        private readonly ?Profile $profile = null,
    ) {}

    /**
     * @return array<string, int|string|null>
     */
    public function getFrontendUserData(): array
    {
        return $this->frontendUserData;
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    public function setFrontendUserData(array $frontendUserData): void
    {
        $this->frontendUserData = $frontendUserData;
    }

    public function getAction(): ProfileActionType
    {
        return $this->action;
    }

    /**
     * The profile about to be updated, `null` when a profile is created.
     */
    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function skip(): void
    {
        $this->skipped = true;
    }

    public function isSkipped(): bool
    {
        return $this->skipped;
    }
}
