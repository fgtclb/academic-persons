<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tca;

use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class ContractLabels
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function getTitle(array &$parameters): void
    {
        // A record the backend cannot load comes without a uid, a new one with its
        // `NEW…` placeholder, which an integer column comparison rejects on PostgreSQL.
        $uid = $parameters['row']['uid'] ?? null;
        if (!MathUtility::canBeInterpretedAsInteger($uid) || (int)$uid <= 0) {
            return;
        }

        $contractRepository = GeneralUtility::makeInstance(ContractRepository::class);
        $contract = $contractRepository->findByUid((int)$uid);

        if ($contract) {
            $parameters['title'] = $contract->getLabel();
        }
    }
}
