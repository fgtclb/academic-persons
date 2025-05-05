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
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class OrganisationalUnit extends AbstractEntity
{
    protected ?OrganisationalUnit $parent = null;
    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     */
    protected string $unitName = '';
    protected string $uniqueName = '';
    protected string $displayText = '';
    protected string $longText = '';
    protected ?\DateTime $validFrom = null;
    protected ?\DateTime $validTo = null;

    /**
     * @var ObjectStorage<Contract>
     * @Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $contracts;

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
    }

    public function setParent(?OrganisationalUnit $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function getParent(): ?OrganisationalUnit
    {
        return $this->parent;
    }

    public function setUnitName(string $unitName): self
    {
        $this->unitName = $unitName;
        return $this;
    }

    public function getUnitName(): string
    {
        return $this->unitName;
    }

    public function setUniqueName(string $uniqueName): self
    {
        $this->uniqueName = $uniqueName;
        return $this;
    }

    public function getUniqueName(): string
    {
        return $this->uniqueName;
    }

    public function setDisplayText(string $displayText): self
    {
        $this->displayText = $displayText;
        return $this;
    }

    public function getDisplayText(): string
    {
        return $this->displayText;
    }

    public function setLongText(string $longText): self
    {
        $this->longText = $longText;
        return $this;
    }

    public function getLongText(): string
    {
        return $this->longText;
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

    /**
     * @param ObjectStorage<Contract> $contracts
     */
    public function setContracts(ObjectStorage $contracts): self
    {
        $this->contracts = $contracts;
        return $this;
    }

    public function addContract(Contract $contract): self
    {
        $this->contracts->attach($contract);
        return $this;
    }

    public function removeContract(Contract $contract): self
    {
        $this->contracts->detach($contract);
        return $this;
    }

    /**
     * @return ObjectStorage<Contract>
     */
    public function getContracts(): ObjectStorage
    {
        return $this->contracts;
    }
}
