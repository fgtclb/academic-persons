<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class ProfileInformation extends AbstractEntity
{
    protected Profile $profile;
    protected string $type = '';
    protected string $title = '';
    protected string $bodytext = '';
    protected string $link = '';
    protected ?int $year = null;
    protected ?int $yearStart = null;
    protected ?int $yearEnd = null;
    protected int $sorting = 0;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setBodytext(string $bodytext): self
    {
        $this->bodytext = $bodytext;
        return $this;
    }

    public function getBodytext(): string
    {
        return $this->bodytext;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;
        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYearStart(?int $yearStart): self
    {
        $this->yearStart = $yearStart;
        return $this;
    }

    public function getYearStart(): ?int
    {
        return $this->yearStart;
    }

    public function setYearEnd(?int $yearEnd): self
    {
        $this->yearEnd = $yearEnd;
        return $this;
    }

    public function getYearEnd(): ?int
    {
        return $this->yearEnd;
    }

    public function setSorting(int $sorting): self
    {
        $this->sorting = $sorting;
        return $this;
    }

    public function getSorting(): int
    {
        return $this->sorting;
    }
}
