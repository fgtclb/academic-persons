<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `itemsProcFunc` handler dispatching {@see ModifyTcaSelectFieldItemsEvent} PSR-14 event
 * with {@see self::getDefaultShowFieldItems()} to allow projects or other extension to
 * modify the available select items.
 *
 * This is executed in the backend in FormEngine for TCA fields using this itemsProcFunc
 * handler and also in controllers to retrieve the select options of the field.
 *
 * @phpstan-import-type ItemsProcFuncParameters from ModifyTcaSelectFieldItemsEvent
 */
final class ProfileShowFieldsItems
{
    /**
     * @param ItemsProcFuncParameters $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        ArrayUtility::mergeRecursiveWithOverrule(
            $parameters['items'],
            $this->getDefaultShowFieldItems()
        );

        $parameters['itemGroups'] = [
            'contracts' => 'Contracts',
        ];

        /** @var ModifyTcaSelectFieldItemsEvent $event */
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(new ModifyTcaSelectFieldItemsEvent(parameters: $parameters));
        $parameters = $event->getParameters();
    }

    /**
     * BackendUtility::getItemLabel() was deprecated in TYPO3 v14.0 (removed in
     * v15.0); resolve the field label through the TCA schema instead, which is
     * available on both TYPO3 v13 and v14.
     */
    private function getFieldLabel(string $table, string $field): string
    {
        return GeneralUtility::makeInstance(TcaSchemaFactory::class)
            ->get($table)
            ->getField($field)
            ->getLabel();
    }

    /**
     * @return array<int, array{
     *     label: string|null,
     *     value: string,
     *     group: string,
     * }>
     */
    private function getDefaultShowFieldItems(): array
    {
        return [
            [
                'label' => $this->getFieldLabel('tx_academicpersons_domain_model_profile', 'image'),
                'value' => 'profile.image',
                'group' => 'profile',
            ],
            [
                'label' => $this->getFieldLabel('tx_academicpersons_domain_model_contract', 'position'),
                'value' => 'contracts.position',
                'group' => 'contracts',
            ],
            [
                'label' => $this->getFieldLabel('tx_academicpersons_domain_model_contract', 'function_type'),
                'value' => 'contracts.functionType',
                'group' => 'contracts',
            ],
            [
                'label' => $this->getFieldLabel('tx_academicpersons_domain_model_contract', 'organisational_unit'),
                'value' => 'contracts.organisationalUnit',
                'group' => 'contracts',
            ],
            [
                'label' =>  $this->getFieldLabel('tx_academicpersons_domain_model_contract', 'room'),
                'value' => 'contracts.room',
                'group' => 'contracts',
            ],
            [
                'label' => $this->getFieldLabel('tx_academicpersons_domain_model_contract', 'office_hours'),
                'value' => 'contracts.officeHours',
                'group' => 'contracts',
            ],
            [
                'label' =>  $this->getFieldLabel('tx_academicpersons_domain_model_contract', 'physical_addresses'),
                'value' => 'contracts.physicalAddresses',
                'group' => 'contracts',
            ],
            [
                'label' =>  $this->getFieldLabel('tx_academicpersons_domain_model_contract', 'email_addresses'),
                'value' => 'contracts.emailAddresses',
                'group' => 'contracts',
            ],
            [
                'label' => $this->getFieldLabel('tx_academicpersons_domain_model_contract', 'phone_numbers'),
                'value' => 'contracts.phoneNumbers',
                'group' => 'contracts',
            ],
            [
                'label' => $this->getFieldLabel('tx_academicpersons_domain_model_contract', 'location'),
                'value' => 'contracts.location',
                'group' => 'contracts',
            ],
        ];
    }
}
