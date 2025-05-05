<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Model\Dto;

class ProfileDemand implements DemandInterface
{
    protected string $groupBy = '';
    protected string $sortBy = 'lastName';
    protected string $sortByDirection = 'asc';
    protected int $currentPage = 1;
    protected string $alphabetFilter = '';
    protected string $profileList = '';
    private string $storagePages = '';
    private int $fallbackForNonTranslated = 0;

    /**
     * @var int[]
     */
    protected array $functionTypes = [];

    /**
     * @var int[]
     */
    protected array $organisationalUnits = [];

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getGroupBy(): string
    {
        return $this->groupBy;
    }

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setGroupBy(string $groupBy): self
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setSortBy(string $sortBy): self
    {
        $this->sortBy = $sortBy;
        return $this;
    }

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getSortByDirection(): string
    {
        return $this->sortByDirection;
    }

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setSortByDirection(string $sortByDirection): self
    {
        $this->sortByDirection = $sortByDirection;
        return $this;
    }

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setCurrentPage(int $currentPage): self
    {
        $this->currentPage = $currentPage;
        return $this;
    }

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getAlphabetFilter(): string
    {
        return $this->alphabetFilter;
    }

    /**
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setAlphabetFilter(string $alphabetFilter): self
    {
        $this->alphabetFilter = $alphabetFilter;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getFunctionTypes(): array
    {
        return $this->functionTypes;
    }

    /**
     * @param int[] $functionTypes
     * @return ProfileDemand
     */
    public function setFunctionTypes(array $functionTypes): ProfileDemand
    {
        $this->functionTypes = $functionTypes;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getOrganisationalUnits(): array
    {
        return $this->organisationalUnits;
    }

    /**
     * @param int[] $organisationalUnits
     * @return ProfileDemand
     */
    public function setOrganisationalUnits(array $organisationalUnits): ProfileDemand
    {
        $this->organisationalUnits = $organisationalUnits;
        return $this;
    }

    /**
     * Overrules all other filter options when not empty string,
     * except special settings:
     *
     * - {@see self::getFallbackForNonTranslated()}
     * - {@see self::getStoragePages()}
     */
    public function getProfileList(): string
    {
        return $this->profileList;
    }

    /**
     * Overrules all other filter options when not empty string,
     * except special settings:
     *
     * - {@see self::getFallbackForNonTranslated()}
     * - {@see self::getStoragePages()}
     */
    public function setProfileList(string $profileList): self
    {
        $this->profileList = $profileList;
        return $this;
    }

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport direct storage page selection within the DTO to the
     * {@see ProfileRepository::findByDemand()} method.
     */
    public function getStoragePages(): string
    {
        return $this->storagePages;
    }

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport direct storage page selection within the DTO to the
     * {@see ProfileRepository::findByDemand()} method.
     */
    public function setStoragePages(string $storagePages): ProfileDemand
    {
        $this->storagePages = $storagePages;
        return $this;
    }

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport direct storage page selection within the DTO to the
     * {@see ProfileRepository::findByDemand()} method.
     */
    public function getFallbackForNonTranslated(): int
    {
        return $this->fallbackForNonTranslated;
    }

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport direct storage page selection within the DTO to the
     * {@see ProfileRepository::findByDemand()} method.
     */
    public function setFallbackForNonTranslated(int $fallbackForNonTranslated): ProfileDemand
    {
        $this->fallbackForNonTranslated = $fallbackForNonTranslated;
        return $this;
    }
}
