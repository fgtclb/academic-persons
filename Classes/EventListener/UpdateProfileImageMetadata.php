<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\EventListener;

use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use FGTCLB\AcademicPersons\Service\ProfileImageMetadataService;

/**
 * Rewrites the image reference metadata after a frontend profile update. The
 * frontend editing flow persists through Extbase and bypasses the DataHandler, so
 * the hook that covers backend saves never fires for it - this listener is the
 * other half. Registered in `Configuration/Services.yaml`.
 *
 * @internal owned by EXT:academic_persons, no public API.
 */
final readonly class UpdateProfileImageMetadata
{
    public function __construct(
        private ProfileImageMetadataService $profileImageMetadataService,
    ) {}

    public function __invoke(AfterProfileUpdateEvent $event): void
    {
        $this->profileImageMetadataService->update($event->getProfile());
    }
}
