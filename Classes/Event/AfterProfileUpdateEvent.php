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
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Announces a changed default-language profile, after it was written.
 *
 * The site is the one the profile belongs to, when the dispatcher knows it, and
 * `null` otherwise: a listener that needs one resolves it from the profile's page.
 */
final class AfterProfileUpdateEvent
{
    public function __construct(
        private readonly Profile $profile,
        private readonly ?Site $site = null,
        private readonly ProfileUpdateOrigin $origin = ProfileUpdateOrigin::Unknown,
    ) {}

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function getOrigin(): ProfileUpdateOrigin
    {
        return $this->origin;
    }
}
