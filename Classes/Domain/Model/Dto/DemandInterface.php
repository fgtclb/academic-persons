<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Model\Dto;

interface DemandInterface
{
    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getGroupBy(): string;

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setGroupBy(string $groupBy): self;

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getSortBy(): string;

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setSortBy(string $sortBy): self;

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getSortByDirection(): string;

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setSortByDirection(string $sortByDirection): self;

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setCurrentPage(int $currentPage): self;

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getCurrentPage(): int;

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setAlphabetFilter(string $alphabetFilter): self;

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getAlphabetFilter(): string;

    public function setProfileList(string $profileList): self;

    public function getProfileList(): string;

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport direct storage page selection within the DTO to the
     * {@see ProfileRepository::findByDemand()} method.
     *
     * @todo Add to interface with next major version as breaking change.
     */
    //public function getStoragePages(): string;

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport direct storage page selection within the DTO to the
     * {@see ProfileRepository::findByDemand()} method.
     *
     * @todo Add to interface with next major version as breaking change.
     */
    //public function setStoragePages(string $storagePages): ProfileDemand;

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport direct storage page selection within the DTO to the
     * {@see ProfileRepository::findByDemand()} method.
     *
     * @todo Add to interface with next major version as breaking change.
     */
    // public function getFallbackForNonTranslated(): int;

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport direct storage page selection within the DTO to the
     * {@see ProfileRepository::findByDemand()} method.
     *
     * @todo Add to interface with next major version as breaking change.
     */
    //public function setFallbackForNonTranslated(int $fallbackForNonTranslated): ProfileDemand;
}
