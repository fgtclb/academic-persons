<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\HtmlSanitizer\Builder\CommonBuilder;
use TYPO3\HtmlSanitizer\Sanitizer;

class Profile extends AbstractEntity
{
    protected string $gender = '';
    protected string $title = '';
    protected string $firstName = '';
    protected string $firstNameAlpha = '';
    protected string $middleName = '';
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
    protected string $websiteTitle = '';
    protected string $website = '';
    protected string $teachingArea = '';
    protected string $coreCompetences = '';
    /**
     * @var ObjectStorage<ProfileInformation>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $memberships;
    /**
     * @var ObjectStorage<ProfileInformation>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $pressMedia;
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
     * @var ObjectStorage<ProfileInformation>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $scientificResearch;
    protected string $publicationsLink = '';
    protected string $publicationsLinkTitle = '';
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
    /**
     * @var ObjectStorage<FrontendUser>
     */
    protected ObjectStorage $frontendUsers;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void
    {
        $this->contracts = new ObjectStorage();
        $this->memberships = new ObjectStorage();
        $this->pressMedia = new ObjectStorage();
        $this->vita = new ObjectStorage();
        $this->publications = new ObjectStorage();
        $this->scientificResearch = new ObjectStorage();
        $this->cooperation = new ObjectStorage();
        $this->lectures = new ObjectStorage();
        $this->frontendUsers = new ObjectStorage();
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getGender(): string
    {
        return $this->gender;
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

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstNameAlpha(string $firstNameAlpha): self
    {
        $this->firstNameAlpha = $firstNameAlpha;
        return $this;
    }

    public function getFirstNameAlpha(): string
    {
        return $this->firstNameAlpha;
    }

    public function setMiddleName(string $middleName): self
    {
        $this->middleName = $middleName;
        return $this;
    }

    public function getMiddleName(): string
    {
        return $this->middleName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastNameAlpha(string $lastNameAlpha): self
    {
        $this->lastNameAlpha = $lastNameAlpha;
        return $this;
    }

    public function getLastNameAlpha(): string
    {
        return $this->lastNameAlpha;
    }

    public function setImage(?FileReference $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getImage(): ?FileReference
    {
        return $this->image;
    }

    /**
     * @param ObjectStorage<Contract> $contracts
     */
    public function setContracts(ObjectStorage $contracts): self
    {
        $this->contracts = $contracts;
        return $this;
    }

    /**
     * @return ObjectStorage<Contract>
     */
    public function getContracts(): ObjectStorage
    {
        return $this->contracts;
    }

    /**
     * @param ObjectStorage<FrontendUser> $frontendUsers
     */
    public function setFrontendUsers(ObjectStorage $frontendUsers): self
    {
        $this->frontendUsers = $frontendUsers;
        return $this;
    }

    /**
     * @return ObjectStorage<FrontendUser>
     */
    public function getFrontendUsers(): ObjectStorage
    {
        return $this->frontendUsers;
    }

    public function setPublicationsLink(string $publicationsLink): self
    {
        $this->publicationsLink = $publicationsLink;
        return $this;
    }

    public function getPublicationsLink(): string
    {
        return $this->publicationsLink;
    }

    public function setPublicationsLinkTitle(string $publicationsLinkTitle): self
    {
        $this->publicationsLinkTitle = $publicationsLinkTitle;
        return $this;
    }

    public function getPublicationsLinkTitle(): string
    {
        return $this->publicationsLinkTitle;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;
        return $this;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function setWebsiteTitle(string $websiteTitle): self
    {
        $this->websiteTitle = $websiteTitle;
        return $this;
    }

    public function getWebsiteTitle(): string
    {
        return $this->websiteTitle;
    }

    /**
     * -------------------------------------------------------------------------
     * Text properties (sanitized)
     * -------------------------------------------------------------------------
     */
    public function setCoreCompetences(string $coreCompetences): self
    {
        $this->coreCompetences = $this->getHtmlSanitizer()->sanitize($coreCompetences);
        return $this;
    }

    public function getCoreCompetences(): string
    {
        return $this->coreCompetences;
    }

    public function setMiscellaneous(string $miscellaneous): self
    {
        $this->miscellaneous = $this->getHtmlSanitizer()->sanitize($miscellaneous);
        return $this;
    }

    public function getMiscellaneous(): string
    {
        return $this->miscellaneous;
    }

    public function setSupervisedDoctoralThesis(string $supervisedDoctoralThesis): self
    {
        $this->supervisedDoctoralThesis = $this->getHtmlSanitizer()->sanitize($supervisedDoctoralThesis);
        return $this;
    }

    public function getSupervisedDoctoralThesis(): string
    {
        return $this->supervisedDoctoralThesis;
    }

    public function setSupervisedThesis(string $supervisedThesis): self
    {
        $this->supervisedThesis = $this->getHtmlSanitizer()->sanitize($supervisedThesis);
        return $this;
    }

    public function getSupervisedThesis(): string
    {
        return $this->supervisedThesis;
    }

    public function setTeachingArea(string $teachingArea): self
    {
        $this->teachingArea = $this->getHtmlSanitizer()->sanitize($teachingArea);
        return $this;
    }

    public function getTeachingArea(): string
    {
        return $this->teachingArea;
    }

    /**
     * -------------------------------------------------------------------------
     * Profile information properties  (sanitized)
     * -------------------------------------------------------------------------
     */

    /**
     * @param ObjectStorage<ProfileInformation> $cooperation
     */
    public function setCooperation(ObjectStorage $cooperation): self
    {
        foreach ($cooperation as $singleCooperation) {
            $singleCooperation->setTitle($this->getHtmlSanitizer()->sanitize($singleCooperation->getTitle()));
            $singleCooperation->setBodytext($this->getHtmlSanitizer()->sanitize($singleCooperation->getBodytext()));
            $singleCooperation->setLink($this->getHtmlSanitizer()->sanitize($singleCooperation->getLink()));
        }
        $this->cooperation = $cooperation;
        return $this;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getCooperation(): ObjectStorage
    {
        return $this->cooperation;
    }

    /**
     * @param ObjectStorage<ProfileInformation> $lectures
     */
    public function setLectures(ObjectStorage $lectures): self
    {
        foreach ($lectures as $lecture) {
            $lecture->setTitle($this->getHtmlSanitizer()->sanitize($lecture->getTitle()));
            $lecture->setBodytext($this->getHtmlSanitizer()->sanitize($lecture->getBodytext()));
            $lecture->setLink($this->getHtmlSanitizer()->sanitize($lecture->getLink()));
        }
        $this->lectures = $lectures;
        return $this;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getLectures(): ObjectStorage
    {
        return $this->lectures;
    }

    /**
     * @param ObjectStorage<ProfileInformation> $memberships
     */
    public function setMemberships(ObjectStorage $memberships): self
    {
        foreach ($memberships as $membership) {
            $membership->setTitle($this->getHtmlSanitizer()->sanitize($membership->getTitle()));
            $membership->setBodytext($this->getHtmlSanitizer()->sanitize($membership->getBodytext()));
            $membership->setLink($this->getHtmlSanitizer()->sanitize($membership->getLink()));
        }
        $this->memberships = $memberships;
        return $this;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getMemberships(): ObjectStorage
    {
        return $this->memberships;
    }

    /**
     * @param ObjectStorage<ProfileInformation> $pressMedia
     */
    public function setPressMedia(ObjectStorage $pressMedia): self
    {
        foreach ($pressMedia as $press) {
            $press->setTitle($this->getHtmlSanitizer()->sanitize($press->getTitle()));
            $press->setBodytext($this->getHtmlSanitizer()->sanitize($press->getBodytext()));
            $press->setLink($this->getHtmlSanitizer()->sanitize($press->getLink()));
        }
        $this->pressMedia = $pressMedia;
        return $this;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getPressMedia(): ObjectStorage
    {
        return $this->pressMedia;
    }

    /**
     * @param ObjectStorage<ProfileInformation> $publications
     */
    public function setPublications(ObjectStorage $publications): self
    {
        foreach ($publications as $publication) {
            $publication->setTitle($this->getHtmlSanitizer()->sanitize($publication->getTitle()));
            $publication->setBodytext($this->getHtmlSanitizer()->sanitize($publication->getBodytext()));
            $publication->setLink($this->getHtmlSanitizer()->sanitize($publication->getLink()));
        }
        $this->publications = $publications;
        return $this;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getPublications(): ObjectStorage
    {
        return $this->publications;
    }

    /**
     * @param ObjectStorage<ProfileInformation> $scientificResearch
     */
    public function setScientificResearch(ObjectStorage $scientificResearch): self
    {
        foreach ($scientificResearch as $research) {
            $research->setTitle($this->getHtmlSanitizer()->sanitize($research->getTitle()));
            $research->setBodytext($this->getHtmlSanitizer()->sanitize($research->getBodytext()));
            $research->setLink($this->getHtmlSanitizer()->sanitize($research->getLink()));
        }
        $this->scientificResearch = $scientificResearch;
        return $this;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getScientificResearch(): ObjectStorage
    {
        return $this->scientificResearch;
    }

    /**
     * @param ObjectStorage<ProfileInformation> $vita
     */
    public function setVita(ObjectStorage $vita): self
    {
        foreach ($vita as $singleVita) {
            $singleVita->setTitle($this->getHtmlSanitizer()->sanitize($singleVita->getTitle()));
            $singleVita->setBodytext($this->getHtmlSanitizer()->sanitize($singleVita->getBodytext()));
            $singleVita->setLink($this->getHtmlSanitizer()->sanitize($singleVita->getLink()));
        }
        $this->vita = $vita;
        return $this;
    }

    /**
     * @return ObjectStorage<ProfileInformation>
     */
    public function getVita(): ObjectStorage
    {
        return $this->vita;
    }

    /**
     * -------------------------------------------------------------------------
     * Additional properties
     * -------------------------------------------------------------------------
     */
    public function getLanguageUid(): int
    {
        // @toodo Property is planned to be made private in the future and will only be accessible via getter
        return (int)$this->_languageUid;
    }

    public function getIsTranslation(): bool
    {
        return $this->_localizedUid !== $this->uid;
    }

    /**
     * --------------------------------------------------------
     * Helper functions
     * --------------------------------------------------------
     */
    protected function getHtmlSanitizer(): Sanitizer
    {
        static $htmlSanitizer = null;
        if ($htmlSanitizer === null) {
            $htmlSanitizer = GeneralUtility::makeInstance(CommonBuilder::class)->build();
        }
        return $htmlSanitizer;
    }
}
