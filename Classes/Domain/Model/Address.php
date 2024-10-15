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
    protected ?Category $employeeType = null;

    protected ?OrganisationalUnit $organisationalUnit = null;

    protected ?FunctionType $functionType = null;

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     */
    protected string $street = '';

    protected string $streetNumber = '';

    protected string $additional = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     */
    protected string $zip = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     */
    protected string $city = '';

    protected string $state = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     */
    protected string $country = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
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

    public function setOrganisationalUnit(?OrganisationalUnit $organisationalUnit): void
    {
        $this->organisationalUnit = $organisationalUnit;
    }

    public function getOrganisationalUnit(): ?OrganisationalUnit
    {
        return $this->organisationalUnit;
    }

    public function setFunctionType(?FunctionType $functionType): void
    {
        $this->functionType = $functionType;
    }

    public function getFunctionType(): ?FunctionType
    {
        return $this->functionType;
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
