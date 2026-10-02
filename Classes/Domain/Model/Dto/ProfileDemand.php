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
 * @api
 */
class ProfileDemand implements DemandInterface
{
    protected string $groupBy = '';
    protected string $sortBy = 'lastName';
    protected string $sortByDirection = 'asc';
    protected int $currentPage = 1;
    protected string $alphabetFilter = '';
    protected string $profileList = '';
    protected string $viewMode = '';
    private string $storagePages = '';
    private int $fallbackForNonTranslated = 0;
    private bool $showHiddenRecords = false;
    private bool $onlyValidContracts = false;

    /**
     * @var int[]
     */
    protected array $functionTypes = [];

    /**
     * @var int[]
     */
    protected array $organisationalUnits = [];

    protected int $functionTypeFilter = 0;
    protected int $organisationalUnitFilter = 0;

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
     * Applies to a manual selection as well: a selected list is paginated in the order
     * {@see self::getProfileList()} carries.
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Applies to a manual selection as well: a selected list is paginated in the order
     * {@see self::getProfileList()} carries.
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
     * The view mode a visitor chose. It selects no record: the list action resolves it
     * against the view modes the site allows and writes the result back, empty for the
     * default mode, so a navigation link carries it only while it differs from that.
     */
    public function getViewMode(): string
    {
        return $this->viewMode;
    }

    /**
     * The view mode a visitor chose. It selects no record: the list action resolves it
     * against the view modes the site allows and writes the result back, empty for the
     * default mode, so a navigation link carries it only while it differs from that.
     */
    public function setViewMode(string $viewMode): self
    {
        $this->viewMode = $viewMode;
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
     * The function type a visitor filters the list by, `0` for none. It narrows the list to
     * the profiles with a contract of this type, within {@see self::getFunctionTypes()}, the
     * restriction of the content element, which a visitor never changes. The list action
     * resets a value that is not one of the filter options to `0`.
     *
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getFunctionTypeFilter(): int
    {
        return $this->functionTypeFilter;
    }

    /**
     * The function type a visitor filters the list by, `0` for none. It narrows the list to
     * the profiles with a contract of this type, within {@see self::getFunctionTypes()}, the
     * restriction of the content element, which a visitor never changes. The list action
     * resets a value that is not one of the filter options to `0`.
     *
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setFunctionTypeFilter(int $functionTypeFilter): self
    {
        $this->functionTypeFilter = $functionTypeFilter;
        return $this;
    }

    /**
     * The organisational unit a visitor filters the list by, `0` for none, as
     * {@see self::getFunctionTypeFilter()} for the function type. With both set, the function
     * type and the unit have to be on the same contract.
     *
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function getOrganisationalUnitFilter(): int
    {
        return $this->organisationalUnitFilter;
    }

    /**
     * The organisational unit a visitor filters the list by, `0` for none, as
     * {@see self::getFunctionTypeFilter()} for the function type. With both set, the function
     * type and the unit have to be on the same contract.
     *
     * Does not have any effect when {@see self::getProfileList()} is not empty.
     */
    public function setOrganisationalUnitFilter(int $organisationalUnitFilter): self
    {
        $this->organisationalUnitFilter = $organisationalUnitFilter;
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

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport the plugin's "show hidden records" option within the
     * DTO to the {@see ProfileRepository::findByDemand()} method.
     */
    public function getShowHiddenRecords(): bool
    {
        return $this->showHiddenRecords;
    }

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport the plugin's "show hidden records" option within the
     * DTO to the {@see ProfileRepository::findByDemand()} method.
     */
    public function setShowHiddenRecords(bool $showHiddenRecords): ProfileDemand
    {
        $this->showHiddenRecords = $showHiddenRecords;
        return $this;
    }

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport the plugin's "only contracts valid today" option within
     * the DTO to the {@see ProfileRepository}: while it is set, the conditions on
     * contracts of the list, its letters and the next day its result changes
     * match only contracts valid today.
     */
    public function getOnlyValidContracts(): bool
    {
        return $this->onlyValidContracts;
    }

    /**
     * Not usable for hydration or direct extbase request argument mapping,
     * only to transport the plugin's "only contracts valid today" option within
     * the DTO to the {@see ProfileRepository}.
     */
    public function setOnlyValidContracts(bool $onlyValidContracts): ProfileDemand
    {
        $this->onlyValidContracts = $onlyValidContracts;
        return $this;
    }
}
