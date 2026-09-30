<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Model\Dto;

/**
 * A profile of the default language or of all languages whose linked frontend
 * users are all gone or inactive, as
 * {@see \FGTCLB\AcademicPersons\Provider\InactiveFrontendUserProfileProvider} finds it
 * for `academic:cleanupprofiles`.
 *
 * @internal for the profile cleanup of academic_persons, no public API.
 */
final class ProfileCleanupCandidate
{
    /**
     * @param bool $allFrontendUsersDeleted Every linked frontend user is deleted or its
     *                                      row is missing. Otherwise every linked user is
     *                                      deleted, disabled or past its end time, and at
     *                                      least one of them is not deleted.
     */
    public function __construct(
        public readonly int $uid,
        public readonly string $label,
        public readonly bool $hidden,
        public readonly bool $allFrontendUsersDeleted,
    ) {}
}
