<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Domain\Model\Dto;

class ProfileDemand implements DemandInterface
{
    protected string $groupBy = '';

    protected string $sortBy = 'lastName';

    protected string $sortByDirection = 'asc';

    protected int $currentPage = 1;

    public function getGroupBy(): string
    {
        return $this->groupBy;
    }

    public function setGroupBy(string $groupBy): self
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function setSortBy(string $sortBy): self
    {
        $this->sortBy = $sortBy;
        return $this;
    }

    public function getSortByDirection(): string
    {
        return $this->sortByDirection;
    }

    public function setSortByDirection(string $sortByDirection): self
    {
        $this->sortByDirection = $sortByDirection;
        return $this;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function setCurrentPage(int $currentPage): void
    {
        $this->currentPage = $currentPage;
    }
}
