<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Backend\Form;

use TYPO3\CMS\Backend\Utility\BackendUtility;

class ProfileShowFieldsItemProcFunc
{
    /**
     * @param array<string, mixed> $params
     */
    public function showFields(&$params): void
    {
        // ToDo: Create a registry option to define more fields to select
        $params['items'] = array_merge($this->defaultFieldItemShowFields(), []);
        $params['itemGroups'] = [
            'contracts' => 'Contracts',
        ];
    }

    /**
     * @return array<int, array<string|int, mixed>>
     */
    private function defaultFieldItemShowFields(): array
    {
        return [
            [
                'label' => BackendUtility::getItemLabel('tx_academicpersons_domain_model_profile', 'image'),
                'value' => 'profile.image',
                'icon' => null,
                'group' => 'profile',
            ],
            [
                'label' => BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'position'),
                'value' => 'contracts.position',
                'icon' => null,
                'group' => 'contracts',
            ],
            [
                'label' => BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'organisational_unit'),
                'value' => 'contracts.organisationalUnit',
                'icon' => null,
                'group' => 'contracts',
            ],
            [
                'label' =>  BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'room'),
                'value' => 'contracts.room',
                'icon' => null,
                'group' => 'contracts',
            ],
            [
                'label' => BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'office_hours'),
                'value' => 'contracts.officeHours',
                'icon' => null,
                'group' => 'contracts',
            ],
            [
                'label' =>  BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'physical_addresses'),
                'value' => 'contracts.physicalAddresses',
                'icon' => null,
                'group' => 'contracts',
            ],
            [
                'label' =>  BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'email_addresses'),
                'value' => 'contracts.emailAddresses',
                'icon' => null,
                'group' => 'contracts',
            ],
            [
                'label' => BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'phone_numbers'),
                'value' => 'contracts.phoneNumbers',
                'icon' => null,
                'group' => 'contracts',
            ],
        ];
    }
}
