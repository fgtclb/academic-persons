<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class FunctionType extends AbstractEntity
{
    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     */
    protected string $functionName = '';
    protected string $functionNameMale = '';
    protected string $functionNameFemale = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

    public function setFunctionName(string $functionName): self
    {
        $this->functionName = $functionName;
        return $this;
    }

    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    public function setFunctionNameMale(string $functionNameMale): self
    {
        $this->functionNameMale = $functionNameMale;
        return $this;
    }

    public function getFunctionNameMale(): string
    {
        return $this->functionNameMale;
    }

    public function setFunctionNameFemale(string $functionNameFemale): self
    {
        $this->functionNameFemale = $functionNameFemale;
        return $this;
    }

    public function getFunctionNameFemale(): string
    {
        return $this->functionNameFemale;
    }
}
