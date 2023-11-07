<?php

declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Tca;

use Fgtclb\AcademicPersons\Domain\Repository\ContractRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContractLabels
{
    public function getTitle(&$parameters)
    {
        $contractRepository = GeneralUtility::makeInstance(ContractRepository::class);
        $contract = $contractRepository->findByUid($parameters['row']['uid']);
        $newTitle = '';
        if ($contract->getProfile()) {
            $newTitle .= $contract->getProfile()->getLastName() . ', ' . $contract->getProfile()->getFirstName();
        }
        if ($contract->getEmployeeType()) {
            $newTitle .= ' (' . $contract->getEmployeeType()->getTitle() . ')';
        }
        $parameters['title'] = $newTitle;
    }
}