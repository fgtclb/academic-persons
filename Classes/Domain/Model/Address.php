<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Address extends AbstractEntity
{
    /**
     * @var Category|null
     */
    protected ?Category $employeeType = null;

    /**
     * @var Category|null
     */
    protected ?Category $organisationalLevel1 = null;

    /**
     * @var Category|null
     */
    protected ?Category $organisationalLevel2 = null;

    /**
     * @var Category|null
     */
    protected ?Category $organisationalLevel3 = null;

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 120})
     */
    protected string $street = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 10})
     */
    protected string $streetNumber = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 120})
     */
    protected string $additional = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 10})
     */
    protected string $zip = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 100})
     */
    protected string $city = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 60})
     */
    protected string $state = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 100})
     */
    protected string $country = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 100})
     */
    protected string $type = '';

    public function getEmployeeType(): ?Category
    {
        return $this->employeeType;
    }

    public function setEmployeeType(?Category $employeeType): void
    {
        $this->employeeType = $employeeType;
    }

    public function getOrganisationalLevel1(): ?Category
    {
        return $this->organisationalLevel1;
    }

    public function setOrganisationalLevel1(?Category $organisationalLevel1): void
    {
        $this->organisationalLevel1 = $organisationalLevel1;
    }

    public function getOrganisationalLevel2(): ?Category
    {
        return $this->organisationalLevel2;
    }

    public function setOrganisationalLevel2(?Category $organisationalLevel2): void
    {
        $this->organisationalLevel2 = $organisationalLevel2;
    }

    public function getOrganisationalLevel3(): ?Category
    {
        return $this->organisationalLevel3;
    }

    public function setOrganisationalLevel3(?Category $organisationalLevel3): void
    {
        $this->organisationalLevel3 = $organisationalLevel3;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getStreetNumber(): string
    {
        return $this->streetNumber;
    }

    public function setStreetNumber(string $streetNumber): void
    {
        $this->streetNumber = $streetNumber;
    }

    public function getAdditional(): string
    {
        return $this->additional;
    }

    public function setAdditional(string $additional): void
    {
        $this->additional = $additional;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
