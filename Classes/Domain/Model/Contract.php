<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Domain\Model;

use DateTime;
use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Contract extends AbstractEntity
{
    protected ?Profile $profile = null;

    protected ?OrganisationalUnit $organisationalUnit = null;

    protected ?FunctionType $functionType = null;

    protected ?DateTime $validFrom = null;

    protected ?DateTime $validTo = null;

    protected ?Category $employeeType = null;

    protected string $position = '';

    protected ?Location $location = null;

    protected string $room = '';

    protected string $officeHours = '';

    protected bool $publish = false;

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

    public function __construct()
    {
        $this->physicalAddresses = new ObjectStorage();
        $this->emailAddresses = new ObjectStorage();
        $this->phoneNumbers = new ObjectStorage();
    }

    public function setProfile(?Profile $profile): void
    {
        $this->profile = $profile;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
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

    public function setValidFrom(?DateTime $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidFrom(): ?DateTime
    {
        return $this->validFrom;
    }

    public function setValidTo(?DateTime $validTo): void
    {
        $this->validTo = $validTo;
    }

    public function getValidTo(): ?DateTime
    {
        return $this->validTo;
    }

    public function setEmployeeType(?Category $employeeType): void
    {
        $this->employeeType = $employeeType;
    }

    public function getEmployeeType(): ?Category
    {
        return $this->employeeType;
    }

    public function setPosition(string $position): void
    {
        $this->position = $position;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setLocation(Location $location): void
    {
        $this->location = $location;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setRoom(string $room): void
    {
        $this->room = $room;
    }

    public function getRoom(): string
    {
        return $this->room;
    }

    public function setOfficeHours(string $officeHours): void
    {
        $this->officeHours = $officeHours;
    }

    public function getOfficeHours(): string
    {
        return $this->officeHours;
    }

    public function setPublish(bool $publish): void
    {
        $this->publish = $publish;
    }

    public function isPublish(): bool
    {
        return $this->publish;
    }

    /**
     * @param ObjectStorage<Address> $physicalAddresses
     */
    public function setPhysicalAddresses(ObjectStorage $physicalAddresses): void
    {
        $this->physicalAddresses = $physicalAddresses;
    }

    public function addPhysicalAddress(Address $physicalAddress): void
    {
        $this->physicalAddresses->attach($physicalAddress);
    }

    public function removePhysicalAddress(Address $physicalAddress): void
    {
        $this->physicalAddresses->detach($physicalAddress);
    }

    /**
     * @return ObjectStorage<Address>
     */
    public function getPhysicalAddresses(): ObjectStorage
    {
        return $this->physicalAddresses;
    }

    /**
     * @param ObjectStorage<Email> $emailAddresses
     */
    public function setEmailAddresses(ObjectStorage $emailAddresses): void
    {
        $this->emailAddresses = $emailAddresses;
    }

    public function addEmailAddress(Email $emailAddress): void
    {
        $this->emailAddresses->attach($emailAddress);
    }

    public function removeEmailAddress(Email $emailAddress): void
    {
        $this->emailAddresses->detach($emailAddress);
    }

    /**
     * @return ObjectStorage<Email>
     */
    public function getEmailAddresses(): ObjectStorage
    {
        return $this->emailAddresses;
    }

    /**
     * @param ObjectStorage<PhoneNumber> $phoneNumbers
     */
    public function setPhoneNumbers(ObjectStorage $phoneNumbers): void
    {
        $this->phoneNumbers = $phoneNumbers;
    }

    public function addPhoneNumber(PhoneNumber $phoneNumber): void
    {
        $this->phoneNumbers->attach($phoneNumber);
    }

    public function removePhoneNumber(PhoneNumber $phoneNumber): void
    {
        $this->phoneNumbers->detach($phoneNumber);
    }

    /**
     * @return ObjectStorage<PhoneNumber>
     */
    public function getPhoneNumbers(): ObjectStorage
    {
        return $this->phoneNumbers;
    }

    public function getLabel(): string
    {
        $firstName = '-';
        $lastName = '-';
        if ($this->profile !== null) {
            $firstName = $this->profile->getLastName() ?? '-';
            $lastName = $this->profile->getFirstName() ?? '-';
        }

        $functionType = '-';
        if ($this->functionType !== null) {
            $functionType = $this->functionType->getFunctionName() ?? '-';
        }

        $organisationalUnit = '-';
        if ($this->organisationalUnit !== null) {
            $organisationalUnit = $this->organisationalUnit->getUnitName() ?? '-';
        }

        $validFrom = $this->validFrom ? $this->validFrom->format('Y-m-d') : '-';
        $validTo = $this->validTo ? $this->validTo->format('Y-m-d') : '-';

        return sprintf(
            '%s, %s / %s / %s / %s-%s',
            $firstName,
            $lastName,
            $functionType,
            $organisationalUnit,
            $validFrom,
            $validTo
        );
    }
}
