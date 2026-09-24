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
use FGTCLB\AcademicPersons\Event\ProfileUpdateOrigin;
use FGTCLB\AcademicPersons\Service\ProfileImageMetadataService;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Rewrites the image reference metadata after a frontend profile update. The
 * frontend editing flow persists through Extbase and bypasses the DataHandler, so
 * the hook that covers backend saves never fires for it - this listener is the
 * other half. Registered in `Configuration/Services.yaml`.
 *
 * A backend save and an import are announced too, and are DataHandler runs: the
 * hook has written the metadata for them already, and only where a name or the
 * image changed. Writing it again here would rewrite it on every save.
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
        if (in_array($event->getOrigin(), [ProfileUpdateOrigin::Backend, ProfileUpdateOrigin::Import], true)) {
            return;
        }
        // `AfterProfileUpdateEvent` carries no request, and this listener is the
        // boundary: the request is resolved here, once, so that the service stays a
        // service and a listener of `ModifyProfileImageMetadataEvent` still gets the
        // request the write happens in. There is none on the command line.
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $this->profileImageMetadataService->update(
            $event->getProfile(),
            $request instanceof ServerRequestInterface ? $request : null,
        );
    }
}
