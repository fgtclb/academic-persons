<?php

declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Tca;

use Fgtclb\AcademicPersons\Domain\Repository\ContractRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContractLabels
{
    /**
     * @param array{items: list<array<string, string|int>>} $parameters
     */
    public function getTitle(array &$parameters): void
    {
        if (!isset($parameters['row']) && !isset($parameters['row']['uid'])) {
            return;
        }

        $contractRepository = GeneralUtility::makeInstance(ContractRepository::class);
        $contract = $contractRepository->findByUid($parameters['row']['uid']);

        if ($contract) {
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
}