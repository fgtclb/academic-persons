<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `itemsProcFunc` handler dispatching {@see ModifyTcaSelectFieldItemsEvent} PSR-14 event
 * with {@see self::getDefaultContractItems()} to allow projects or other extension to
 * modify the available select items.
 *
 * This is executed in the backend in FormEngine for TCA fields using this itemsProcFunc
 * handler and also in controllers to retrieve the select options of the field.
 */
final class ContractItems
{
    /**
     * @param array{
     *      items: array<int, array{
     *       label?: string|null,
     *       value?: mixed,
     *       icon?: string|null,
     *       group?: string|null,
     *      }>,
     *      config: array<string, mixed>,
     *      TSconfig: array<string, mixed>,
     *      table: string,
     *      row: array<string, mixed>,
     *      field: string,
     *      effectivePid: int,
     *      site: Site|null,
     *      flexParentDatabaseRow?: array<string, mixed>|null,
     *      inlineParentUid?: int,
     *      inlineParentTableName?: string,
     *      inlineParentFieldName?: string,
     *      inlineParentConfig?: array<string, mixed>,
     *      inlineTopMostParentUid?: int,
     *      inlineTopMostParentTableName?: string,
     *      inlineTopMostParentFieldName?: string,
     *  } $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        ArrayUtility::mergeRecursiveWithOverrule(
            $parameters['items'],
            $this->getDefaultContractItems($parameters)
        );
        /** @var ModifyTcaSelectFieldItemsEvent $event */
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(new ModifyTcaSelectFieldItemsEvent(parameters: $parameters));
        $parameters = $event->getParameters();
    }

    /**
     * @param array{
     *      items: array<int, array{
     *       label?: string|null,
     *       value?: mixed,
     *       icon?: string|null,
     *       group?: string|null,
     *      }>,
     *      config: array<string, mixed>,
     *      TSconfig: array<string, mixed>,
     *      table: string,
     *      row: array<string, mixed>,
     *      field: string,
     *      effectivePid: int,
     *      site: Site|null,
     *      flexParentDatabaseRow?: array<string, mixed>|null,
     *      inlineParentUid?: int,
     *      inlineParentTableName?: string,
     *      inlineParentFieldName?: string,
     *      inlineParentConfig?: array<string, mixed>,
     *      inlineTopMostParentUid?: int,
     *      inlineTopMostParentTableName?: string,
     *      inlineTopMostParentFieldName?: string,
     *  } $parameters
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
