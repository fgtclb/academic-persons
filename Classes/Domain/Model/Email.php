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

class Email extends AbstractEntity
{
    protected ?Contract $contract = null;
    protected string $email = '';
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

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
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
