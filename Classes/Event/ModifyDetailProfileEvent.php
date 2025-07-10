<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Event;

use FGTCLB\AcademicPersons\Controller\ProfileController;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * Fired in {@see ProfileController::detailAction()} included in `detail` and `listanddetail`
 * extbase plugins to allow assigning additional data to the detail view or replace the
 * profile.
 */
final class ModifyDetailProfileEvent
{
    public function __construct(
        private Profile $profile,
        private FluidViewInterface|CoreViewInterface $view,
    ) {}

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): void
    {
        $this->profile = $profile;
    }

    public function getView(): FluidViewInterface|CoreViewInterface
    {
        return $this->view;
    }
}
