.. _feature-1783613368:

==============================================================================
Feature: Dispatch `ModifyTcaSelectFieldItemsEvent` in `itemsProcFunc` handlers
==============================================================================

Description
===========

The following `itemsProcFunc` handlers provided by `EXT:academic_persons` now
dispatch the shared PSR-14 event
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent`:

*  :php:`\FGTCLB\AcademicPersons\Backend\FormEngine\ContractItems`
*  :php:`\FGTCLB\AcademicPersons\Backend\FormEngine\ProfileShowFieldsItems`

Previously the shipped select items could only be adjusted by adding items
directly (without influence on the ordering) or by implementing a custom
`itemsProcFunc` replacing the shipped one. Both ways were neither convenient
nor testable.

With the dispatched event, projects can modify the available select items for
the backend (FormEngine) - and, where the handler is reused, for the frontend
- through a single PSR-14 event listener, instead of dealing with a dozen
dedicatedly named events for the same purpose.

Example
=======

.. code-block:: php
    :caption: EXT:my_ext/Classes/EventListener/ModifyTcaSelectFieldItemsEventListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExt\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
    use TYPO3\CMS\Backend\Utility\BackendUtility;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    #[AsEventListener(identifier: 'my-ext/modify-academic-persons-tca-select-items')]
    final class ModifyTcaSelectFieldItemsEventListener
    {
        public function __invoke(ModifyTcaSelectFieldItemsEvent $event): void
        {
            $tableName = $event->getParameters()['table'];
            $fieldName = $event->getParameters()['field'];
            if ($tableName !== 'tt_content') {
                // Not the table we want to handle. Skip.
                return;
            }
            if ($fieldName === 'settings.showFields') {
                $this->modifyPersonsShowFieldsSelectItems($event);
            }
            if ($fieldName === 'settings.selectedContracts') {
                $this->modifyPersonsContractSelectItems($event);
            }
        }

        private function modifyPersonsShowFieldsSelectItems(
            ModifyTcaSelectFieldItemsEvent $event,
        ): void {
            $parameters = $event->getParameters();
            $parameters['items'][] = [
                'label' => BackendUtility::getItemLabel('tx_academicpersons_domain_model_contract', 'position'),
                'value' => 'contracts.position',
                'group' => 'contracts',
            ];
            $event->setParameters($parameters);
        }

        private function modifyPersonsContractSelectItems(
            ModifyTcaSelectFieldItemsEvent $event,
        ): void {
            $parameters = $event->getParameters();
            $parameters['items'][] = [
                'label' => 'LLL:EXT:my_ext/Resources/Private/Language/locallang_be.xlf:custom_contract',
                'value' => 10,
            ];
            $event->setParameters($parameters);
        }
    }

.. index:: Backend, PHP, TCA, ext:academic_persons
