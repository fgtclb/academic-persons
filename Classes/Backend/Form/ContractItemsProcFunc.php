<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Backend\Form;

use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

class ContractItemsProcFunc
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        // @todo: Check how to handle hidden and deleted records in selection and existing relations
        // @todo: Check how to handle publish property of contracts in selection existing relations

        $contractRepository = GeneralUtility::makeInstance(ContractRepository::class);
        $contractRepository->setDefaultQuerySettings($querySettings);
        $contracts = $contractRepository->findAll();

        $items = [];
        foreach ($contracts as $contract) {
            $items[$contract->getUid()] = [
                'lastName' => $contract->getProfile()?->getLastName(),
                'firstName' => $contract->getProfile()?->getFirstName(),
                'label' => $contract->getLabel(),
            ];
        }

        uasort(
            $items,
            fn($a, $b): int =>
                [$a['lastName'], $a['firstName']]
                <=>
                [$b['lastName'], $b['firstName']]
        );

        foreach ($items as $key => $properties) {
            $parameters['items'][] = [
                $properties['label'],
                $key,
                'tx_academiccontacts4pages_domain_model_contract',
            ];
        }
    }
}
