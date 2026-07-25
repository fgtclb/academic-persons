<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `itemsProcFunc` handler dispatching {@see ModifyTcaSelectFieldItemsEvent} PSR-14 event
 * with {@see self::getDefaultShowFieldItems()} to allow projects or other extension to
 * modify the available select items.
 *
 * This is executed in the backend in FormEngine for TCA fields using this itemsProcFunc
 * handler and also in controllers to retrieve the select options of the field.
 */
final class ProfileShowFieldsItems
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
