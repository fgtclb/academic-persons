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

class Address extends AbstractEntity
{
    protected ?Contract $contract = null;
    protected string $street = '';
    protected string $streetNumber = '';
    protected string $additional = '';
    protected string $zip = '';
    protected string $city = '';
    protected string $state = '';
    protected string $country = '';
    protected string $type = '';
    protected int $sorting = 0;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

    public function setContract(Contract $contract): self
    {
        $this->contract = $contract;
        return $this;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;
        return $this;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreetNumber(string $streetNumber): self
    {
        $this->streetNumber = $streetNumber;
        return $this;
    }

    public function getStreetNumber(): string
    {
        return $this->streetNumber;
    }

    public function setAdditional(string $additional): self
    {
        $this->additional = $additional;
        return $this;
    }

    public function getAdditional(): string
    {
        return $this->additional;
    }

    public function setZip(string $zip): self
    {
        $this->zip = $zip;
        return $this;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setState(string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
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
