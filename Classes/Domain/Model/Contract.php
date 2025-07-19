<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Model;

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
    protected ?\DateTime $validFrom = null;
    protected ?\DateTime $validTo = null;
    protected ?Category $employeeType = null;
    protected string $position = '';
    protected ?Location $location = null;
    protected string $room = '';
    protected string $officeHours = '';
    protected bool $publish = false;
    protected int $sorting = 0;

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
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void
    {
        $this->physicalAddresses = new ObjectStorage();
        $this->emailAddresses = new ObjectStorage();
        $this->phoneNumbers = new ObjectStorage();
    }

    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setOrganisationalUnit(?OrganisationalUnit $organisationalUnit): self
    {
        $this->organisationalUnit = $organisationalUnit;
        return $this;
    }

    public function getOrganisationalUnit(): ?OrganisationalUnit
    {
        return $this->organisationalUnit;
    }

    public function setFunctionType(?FunctionType $functionType): self
    {
        $this->functionType = $functionType;
        return $this;
    }

    public function getFunctionType(): ?FunctionType
    {
        return $this->functionType;
    }

    public function setValidFrom(?\DateTime $validFrom): self
    {
        $this->validFrom = $validFrom;
        return $this;
    }

    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    public function setValidTo(?\DateTime $validTo): self
    {
        $this->validTo = $validTo;
        return $this;
    }

    public function getValidTo(): ?\DateTime
    {
        return $this->validTo;
    }

    public function setEmployeeType(?Category $employeeType): self
    {
        $this->employeeType = $employeeType;
        return $this;
    }

    public function getEmployeeType(): ?Category
    {
        return $this->employeeType;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setRoom(string $room): self
    {
        $this->room = $room;
        return $this;
    }

    public function getRoom(): string
    {
        return $this->room;
    }

    public function setOfficeHours(string $officeHours): self
    {
        $this->officeHours = $officeHours;
        return $this;
    }

    public function getOfficeHours(): string
    {
        return $this->officeHours;
    }

    public function setPublish(bool $publish): self
    {
        $this->publish = $publish;
        return $this;
    }

    public function isPublish(): bool
    {
        return $this->publish;
    }

    public function getPublish(): bool
    {
        return $this->publish;
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

    /**
     * @param ObjectStorage<Address> $physicalAddresses
     */
    public function setPhysicalAddresses(ObjectStorage $physicalAddresses): self
    {
        $this->physicalAddresses = $physicalAddresses;
        return $this;
    }

    public function addPhysicalAddress(Address $physicalAddress): self
    {
        $this->physicalAddresses->attach($physicalAddress);
        return $this;
    }

    public function removePhysicalAddress(Address $physicalAddress): self
    {
        $this->physicalAddresses->detach($physicalAddress);
        return $this;
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
    public function setEmailAddresses(ObjectStorage $emailAddresses): self
    {
        $this->emailAddresses = $emailAddresses;
        return $this;
    }

    public function addEmailAddress(Email $emailAddress): self
    {
        $this->emailAddresses->attach($emailAddress);
        return $this;
    }

    public function removeEmailAddress(Email $emailAddress): self
    {
        $this->emailAddresses->detach($emailAddress);
        return $this;
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
    public function setPhoneNumbers(ObjectStorage $phoneNumbers): self
    {
        $this->phoneNumbers = $phoneNumbers;
        return $this;
    }

    public function addPhoneNumber(PhoneNumber $phoneNumber): self
    {
        $this->phoneNumbers->attach($phoneNumber);
        return $this;
    }

    public function removePhoneNumber(PhoneNumber $phoneNumber): self
    {
        $this->phoneNumbers->detach($phoneNumber);
        return $this;
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
        $firstName = ($this->getProfile()?->getLastName() ?? '') ?: '-';
        $lastName = ($this->getProfile()?->getFirstName() ?? '') ?: '-';
        $functionType = ($this->getFunctionType()?->getFunctionName() ?? '') ?: '-';
        $organisationalUnit = ($this->getOrganisationalUnit()?->getUnitName() ?? '') ?: '-';
        $validFrom = ($this->getValidFrom()?->format('Y-m-d') ?? '') ?: '-';
        $validTo = ($this->getValidTo()?->format('Y-m-d') ?? '') ?: '-';
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
