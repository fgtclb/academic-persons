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
        $this->contracts = new ObjectStorage();
    }

    public function setParent(?OrganisationalUnit $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?OrganisationalUnit
    {
        return $this->parent;
    }

    public function setUnitName(string $unitName): void
    {
        $this->unitName = $unitName;
    }

    public function getUnitName(): string
    {
        return $this->unitName;
    }

    public function setUniqueName(string $uniqueName): void
    {
        $this->uniqueName = $uniqueName;
    }

    public function getUniqueName(): string
    {
        return $this->uniqueName;
    }

    public function setDisplayText(string $displayText): void
    {
        $this->displayText = $displayText;
    }

    public function getDisplayText(): string
    {
        return $this->displayText;
    }

    public function setLongText(string $longText): void
    {
        $this->longText = $longText;
    }

    public function getLongText(): string
    {
        return $this->longText;
    }

    public function setValidFrom(?\DateTime $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    public function setValidTo(?\DateTime $validTo): void
    {
        $this->validTo = $validTo;
    }

    public function getValidTo(): ?\DateTime
    {
        return $this->validTo;
    }

    /**
     * @param ObjectStorage<Contract> $contracts
     */
    public function setContracts(ObjectStorage $contracts): void
    {
        $this->contracts = $contracts;
    }

    public function addContract(Contract $contract): void
    {
        $this->contracts->attach($contract);
    }

    public function removeContract(Contract $contract): void
    {
        $this->contracts->detach($contract);
    }

    /**
     * @return ObjectStorage<Contract>
     */
    public function getContracts(): ObjectStorage
    {
        return $this->contracts;
    }
}
