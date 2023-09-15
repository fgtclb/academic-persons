<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Event;

use Fgtclb\AcademicPersons\Domain\Model\Profile;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

final class ModifyDetailProfileEvent
{
    private Profile $profile;

    private ViewInterface $view;

    public function __construct(Profile $profile, ViewInterface $view)
    {
        $this->profile = $profile;
        $this->view = $view;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): void
    {
        $this->profile = $profile;
    }

    public function getView(): ViewInterface
    {
        return $this->view;
    }
}
