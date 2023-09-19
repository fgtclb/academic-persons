<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Domain\Model\Dto;

interface DemandInterface
{
    public function getGroupBy(): string;

    public function setGroupBy(string $groupBy): self;

    public function getSortBy(): string;

    public function setSortBy(string $sortBy): self;

    public function getSortByDirection(): string;

    public function setSortByDirection(string $sortByDirection): self;

    public function setCurrentPage(int $currentPage): self;

    public function getCurrentPage(): int;

    public function setProfileList(string $profileList): self;

    public function getProfileList(): string;
}
