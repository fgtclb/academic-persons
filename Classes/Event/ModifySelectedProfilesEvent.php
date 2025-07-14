<?php

namespace FGTCLB\AcademicPersons\Event;

use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * Fired in {@see ProfileController::selectedProfilesAction()} included in `academicpersons_selectedprofiles`
 * extbase plugins to allow assigning additional data to the detail view or replace the profiles resultset.
 */
final class ModifySelectedProfilesEvent
{
    /**
     * @param QueryResultInterface<int, Profile> $profiles
     */
    public function __construct(
        private QueryResultInterface $profiles,
        private readonly FluidViewInterface|CoreViewInterface $view,
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
    ) {}

    /**
     * @return QueryResultInterface<int, Profile>
     */
    public function getProfiles(): QueryResultInterface
    {
        return $this->profiles;
    }

    /**
     * @param QueryResultInterface<int, Profile> $profiles
     */
    public function setProfiles(QueryResultInterface $profiles): void
    {
        $this->profiles = $profiles;
    }

    public function getView(): FluidViewInterface|CoreViewInterface
    {
        return $this->view;
    }

    public function getPluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }
}
