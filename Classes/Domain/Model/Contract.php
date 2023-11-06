<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Domain\Model;

use Fgtclb\AcademicPersons\Domain\Model\Profile;
use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Contract extends AbstractEntity
{
    /**
     * @var Profile|null
     */
    protected ?Profile $profile = null;

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
     * @var ObjectStorage<Address>
     * @Lazy
     */
    protected ObjectStorage $physicalAddressesFromOrganisation;

    /**
     * @var ObjectStorage<Address>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $physicalAddresses;

    /**
     * @var ObjectStorage<Email>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $emailAddresses;

    /**
     * @var ObjectStorage<PhoneNumber>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $phoneNumbers;

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 100})
     */
    protected string $position = '';

    protected ?Location $location = null;

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 100})
     */
    protected string $room = '';

    protected string $officeHours = '';

    protected bool $publish = false;

    public function __construct()
    {
        $this->physicalAddressesFromOrganisation = new ObjectStorage();
        $this->physicalAddresses = new ObjectStorage();
        $this->emailAddresses = new ObjectStorage();
        $this->phoneNumbers = new ObjectStorage();
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): void
    {
        $this->profile = $profile;
    }

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

    /**
     * @return ObjectStorage<Address>
     */
    public function getPhysicalAddressesFromOrganisation(): ObjectStorage
    {
        return $this->physicalAddressesFromOrganisation;
    }

    /**
     * @param ObjectStorage<Address> $physicalAddressesFromOrganisation
     */
    public function setPhysicalAddressesFromOrganisation(ObjectStorage $physicalAddressesFromOrganisation): void
    {
        $this->physicalAddressesFromOrganisation = $physicalAddressesFromOrganisation;
    }

    /**
     * @return ObjectStorage<Address>
     */
    public function getPhysicalAddresses(): ObjectStorage
    {
        return $this->physicalAddresses;
    }

    /**
     * @param ObjectStorage<Address> $physicalAddresses
     */
    public function setPhysicalAddresses(ObjectStorage $physicalAddresses): void
    {
        $this->physicalAddresses = $physicalAddresses;
    }

    /**
     * @return ObjectStorage<Email>
     */
    public function getEmailAddresses(): ObjectStorage
    {
        return $this->emailAddresses;
    }

    /**
     * @param ObjectStorage<Email> $emailAddresses
     */
    public function setEmailAddresses(ObjectStorage $emailAddresses): void
    {
        $this->emailAddresses = $emailAddresses;
    }

    /**
     * @return ObjectStorage<PhoneNumber>
     */
    public function getPhoneNumbers(): ObjectStorage
    {
        return $this->phoneNumbers;
    }

    /**
     * @param ObjectStorage<PhoneNumber> $phoneNumbers
     */
    public function setPhoneNumbers(ObjectStorage $phoneNumbers): void
    {
        $this->phoneNumbers = $phoneNumbers;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setPosition(string $position): void
    {
        $this->position = $position;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(Location $location): void
    {
        $this->location = $location;
    }

    public function getRoom(): string
    {
        return $this->room;
    }

    public function setRoom(string $room): void
    {
        $this->room = $room;
    }

    public function getOfficeHours(): string
    {
        return $this->officeHours;
    }

    public function setOfficeHours(string $officeHours): void
    {
        $this->officeHours = $officeHours;
    }

    public function isPublish(): bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): void
    {
        $this->publish = $publish;
    }
}
