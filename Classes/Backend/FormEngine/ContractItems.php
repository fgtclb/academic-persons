<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `itemsProcFunc` handler dispatching {@see ModifyTcaSelectFieldItemsEvent} PSR-14 event
 * with {@see self::getDefaultContractItems()} to allow projects or other extension to
 * modify the available select items.
 *
 * This is executed in the backend in FormEngine for TCA fields using this itemsProcFunc
 * handler and also in controllers to retrieve the select options of the field.
 *
 * @phpstan-import-type ItemsProcFuncParameters from ModifyTcaSelectFieldItemsEvent
 */
final class ContractItems
{
    /**
     * @param ItemsProcFuncParameters $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        // Appended, never merged: both lists are indexed from 0, so a merge replaced the
        // items the field declares itself, the empty item of a single select among them.
        $parameters['items'] = [
            ...array_values($parameters['items']),
            ...$this->getDefaultContractItems($parameters),
        ];
        /** @var ModifyTcaSelectFieldItemsEvent $event */
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(new ModifyTcaSelectFieldItemsEvent(parameters: $parameters));
        $parameters = $event->getParameters();
    }

    /**
     * @param ItemsProcFuncParameters $parameters
     * @return array<int<0, max>, array{
     *     label: string,
     *     value: int<1, max>|string,
     * }>
     */
    private function getDefaultContractItems(array $parameters): array
    {
        // @todo: Check how to handle hidden and deleted records in selection and existing relations
        // @todo: Check how to handle publish property of contracts in selection existing relations
        $contractRepository = GeneralUtility::makeInstance(ContractRepository::class);
        $contracts = $contractRepository->getContractItemsForTcaItemsProcFunc($parameters);

        $items = [];
        $sortedItems = [];
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
            $sortedItems[] = [
                'label' => $properties['label'],
                'value' => $key,
            ];
        }

        return $sortedItems;
    }
}
