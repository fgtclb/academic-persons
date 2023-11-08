<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Profile extends AbstractEntity
{
    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 50})
     */
    protected string $gender = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 50})
     */
    protected string $title = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 80})
     */
    protected string $firstName = '';

    protected string $firstNameAlpha = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 80})
     */
    protected string $middleName = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 80})
     */
    protected string $lastName = '';

    protected string $lastNameAlpha = '';

    /**
     * @Cascade("remove")
     */
    protected ?FileReference $image = null;

    /**
     * @var ObjectStorage<Contract>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $contracts;

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 255})
     */
    protected string $website = '';

    protected string $teachingArea = '';

    protected string $coreCompetences = '';

    /**
     * @var ObjectStorage<ProfileInformation>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $memberships;

    protected string $supervisedThesis = '';

    protected string $supervisedDoctoralThesis = '';

    /**
     * @var ObjectStorage<ProfileInformation>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $vita;

    /**
     * @var ObjectStorage<ProfileInformation>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $publications;

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator", options={"maximum": 255})
     */
    protected string $publicationsLink = '';

    protected string $miscellaneous = '';

    /**
     * @var ObjectStorage<ProfileInformation>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $cooperation;

    /**
     * @var ObjectStorage<ProfileInformation>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $lectures;

    public function __construct()
    {
        $this->contracts = new ObjectStorage();
        $this->memberships = new ObjectStorage();
        $this->vita = new ObjectStorage();
        $this->publications = new ObjectStorage();
        $this->cooperation = new ObjectStorage();
        $this->lectures = new ObjectStorage();
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): void
    {
        $this->gender = $gender;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getFirstNameAlpha(): string
    {
        return $this->firstNameAlpha;
    }

    public function setFirstNameAlpha(string $firstNameAlpha): void
    {
        $this->firstNameAlpha = $firstNameAlpha;
    }

    public function getMiddleName(): string
    {
        return $this->middleName;
    }

    public function setMiddleName(string $middleName): void
    {
        $this->middleName = $middleName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getLastNameAlpha(): string
    {
        return $this->lastNameAlpha;
    }

    public function setLastNameAlpha(string $lastNameAlpha): void
    {
        $this->lastNameAlpha = $lastNameAlpha;
    }

    public function getImage(): ?FileReference
    {
        return $this->image;
    }

    public function setImage(?FileReference $image): void
    {
        $this->image = $image;
    }

    /**
     * @return ObjectStorage<Contract>
     */
    public function getContracts(): ObjectStorage
    {
        return $this->contracts;
    }

    /**
     * @param ObjectStorage<Contract> $contracts
     */
    public function setContracts(ObjectStorage $contracts): void
    {
        $this->contracts = $contracts;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function setWebsite(string $website): void
    {
        $this->website = $website;
    }

    public function getTeachingArea(): string
    {
        return $this->teachingArea;
    }

    public function setTeachingArea(string $teachingArea): void
    {
        $this->teachingArea = $teachingArea;
    }

    public function getCoreCompetences(): string
    {
        return $this->coreCompetences;
    }

    public function setCoreCompetences(string $coreCompetences): void
    {
        $this->coreCompetences = $coreCompetences;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getMemberships(): ObjectStorage
    {
        return $this->memberships;
    }

    /**
     * @param ObjectStorage<ProfileInformation> $memberships
     */
    public function setMemberships(ObjectStorage $memberships): void
    {
        $this->memberships = $memberships;
    }

    public function getSupervisedThesis(): string
    {
        return $this->supervisedThesis;
    }

    public function setSupervisedThesis(string $supervisedThesis): void
    {
        $this->supervisedThesis = $supervisedThesis;
    }

    public function getSupervisedDoctoralThesis(): string
    {
        return $this->supervisedDoctoralThesis;
    }

    public function setSupervisedDoctoralThesis(string $supervisedDoctoralThesis): void
    {
        $this->supervisedDoctoralThesis = $supervisedDoctoralThesis;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getVita(): ObjectStorage
    {
        return $this->vita;
    }

    /**
     * @param ObjectStorage<ProfileInformation> $vita
     */
    public function setVita(ObjectStorage $vita): void
    {
        $this->vita = $vita;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getPublications(): ObjectStorage
    {
        return $this->publications;
    }

    /**
     * @param ObjectStorage<ProfileInformation> $publications
     */
    public function setPublications(ObjectStorage $publications): void
    {
        $this->publications = $publications;
    }

    public function getPublicationsLink(): string
    {
        return $this->publicationsLink;
    }

    public function setPublicationsLink(string $publicationsLink): void
    {
        $this->publicationsLink = $publicationsLink;
    }

    public function getMiscellaneous(): string
    {
        return $this->miscellaneous;
    }

    public function setMiscellaneous(string $miscellaneous): void
    {
        $this->miscellaneous = $miscellaneous;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getCooperation(): ObjectStorage
    {
        return $this->cooperation;
    }

    /**
     * @param ObjectStorage<ProfileInformation> $cooperation
     */
    public function setCooperation(ObjectStorage $cooperation): void
    {
        $this->cooperation = $cooperation;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getLectures(): ObjectStorage
    {
        return $this->lectures;
    }

    /**
     * @param ObjectStorage<ProfileInformation> $lectures
     */
    public function setLectures(ObjectStorage $lectures): void
    {
        $this->lectures = $lectures;
    }
}
