<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Email extends AbstractEntity
{
    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator")
     */
    protected string $email = '';

    /**
     * @Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     */
    protected string $type = '';

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
