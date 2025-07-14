<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Event;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * Fired in {@see ProfileController::listAction()} included in `detail` and `listanddetail`
 * extbase plugins to allow assigning additional data to the detail view or replace the
 * profile.
 *
 * @todo Dispatched also in other actions, which is wrong and requires to dispatch dedicated
 *       events and will be done in a follow-up change.
 */
final class ModifyListProfilesEvent
{
    /**
     * @param QueryResultInterface<Profile> $profiles
     */
    public function __construct(
        private QueryResultInterface $profiles,
        private FluidViewInterface|CoreViewInterface $view,
    ) {}

    /**
     * @return QueryResultInterface<Profile>
     */
    public function getProfiles(): QueryResultInterface
    {
        return $this->profiles;
    }

    /**
     * @param QueryResultInterface<Profile> $profiles
     */
    public function setProfiles(QueryResultInterface $profiles): void
    {
        $this->profiles = $profiles;
    }

    public function getView(): FluidViewInterface|CoreViewInterface
    {
        return $this->view;
    }
}
