<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Event;

use FGTCLB\AcademicPersons\Profile\ProfileActionType;
use FGTCLB\AcademicPersons\Profile\ProfileFactoryInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

final class ChooseProfileFactoryEvent
{
    public function __construct(
        private readonly FrontendUserAuthentication $frontendUserAuthentication,
        private readonly ProfileActionType $action = ProfileActionType::Create,
        private ?ProfileFactoryInterface $profileFactory = null,
    ) {}

    public function getFrontendUserAuthentication(): FrontendUserAuthentication
    {
        return $this->frontendUserAuthentication;
    }

    public function getAction(): ProfileActionType
    {
        return $this->action;
    }

    public function getProfileFactory(): ?ProfileFactoryInterface
    {
        return $this->profileFactory;
    }

    public function setProfileFactory(ProfileFactoryInterface $profileFactory): void
    {
        $this->profileFactory = $profileFactory;
    }
}
