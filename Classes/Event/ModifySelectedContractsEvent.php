<?php

namespace FGTCLB\AcademicPersons\Event;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * Fired in {@see ProfileController::selectedProfilesAction()} included in `academicpersons_selectedprofiles`
 * extbase plugins to allow assigning additional data to the detail view or replace the profiles resultset.
 */
final class ModifySelectedContractsEvent
{
    /**
     * @param QueryResultInterface<int, Contract>$contracts
     */
    public function __construct(
        private QueryResultInterface $contracts,
        private readonly FluidViewInterface|CoreViewInterface $view,
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
    ) {}

    /**
     * @return QueryResultInterface<int, Contract>
     */
    public function getContracts(): QueryResultInterface
    {
        return $this->contracts;
    }

    /**
     * @param QueryResultInterface<int, Contract> $profiles
     */
    public function setContracts(QueryResultInterface $profiles): void
    {
        $this->contracts = $profiles;
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
