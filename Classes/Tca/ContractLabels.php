<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tca;

use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContractLabels
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function getTitle(array &$parameters): void
    {
        if (!isset($parameters['row']) && !isset($parameters['row']['uid'])) {
            return;
        }

        $contractRepository = GeneralUtility::makeInstance(ContractRepository::class);
        $contract = $contractRepository->findByUid($parameters['row']['uid']);

        if ($contract) {
            $parameters['title'] = $contract->getLabel();
        }
    }
}
