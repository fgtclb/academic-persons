<?php

declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Backend\Form;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

class ProfileShowFieldsItemProcFunc
{
    /**
     * @param array<string, mixed> $params
     *
     * @return void
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
        $typo3MajorVersion = (new Typo3Version())->getMajorVersion();
        $selectLabelKey = ($typo3MajorVersion >= 12) ? 'label' : 0;
        $selectValueKey = ($typo3MajorVersion >= 12) ? 'value' : 1;
        $selectIconKey = ($typo3MajorVersion >= 12) ? 'icon' : 1;
        $selectGroupKey = ($typo3MajorVersion >= 12) ? 'group' : 3;

        return [
            [
                $selectLabelKey => BackendUtility::getItemLabel('tx_academicpersons_domain_model_profile', 'image'),
                $selectValueKey => 'profile.image',
                $selectIconKey => null,
                $selectGroupKey => 'profile',
            ],
            [
                $selectLabelKey => BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'position'),
                $selectValueKey => 'contracts.position',
                $selectIconKey => null,
                $selectGroupKey => 'contracts',
            ],
            [
                $selectLabelKey => BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'organisational_unit'),
                $selectValueKey => 'contracts.organisationalUnit',
                $selectIconKey => null,
                $selectGroupKey => 'contracts',
            ],
            [
                $selectLabelKey =>  BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'room'),
                $selectValueKey => 'contracts.room',
                $selectIconKey => null,
                $selectGroupKey => 'contracts',
            ],
            [
                $selectLabelKey => BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'office_hours'),
                $selectValueKey => 'contracts.officeHours',
                $selectIconKey => null,
                $selectGroupKey => 'contracts',
            ],
            [
                $selectLabelKey =>  BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'physical_addresses'),
                $selectValueKey => 'contracts.physicalAddresses',
                $selectIconKey => null,
                $selectGroupKey => 'contracts',
            ],
            [
                $selectLabelKey =>  BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'email_addresses'),
                $selectValueKey => 'contracts.emailAddresses',
                $selectIconKey => null,
                $selectGroupKey => 'contracts',
            ],
            [
                $selectLabelKey => BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'phone_numbers'),
                $selectValueKey => 'contracts.phoneNumbers',
                $selectIconKey => null,
                $selectGroupKey => 'contracts',
            ],
        ];
    }
}
