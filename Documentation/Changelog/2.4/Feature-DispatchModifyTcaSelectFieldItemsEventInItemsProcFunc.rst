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

With the dispatched event, projects can modify the available select items of
the backend form through a single PSR-14 event listener, instead of dealing
with a dozen dedicatedly named events for the same purpose. The event carries
the complete `itemsProcFunc` parameters, including :php:`table`, :php:`field`
and :php:`items`. For a FlexForm field, :php:`field` is the name of the
FlexForm element, for example :php:`settings.showFields`.

Example
-------

:php:`TYPO3\CMS\Core\Attribute\AsEventListener` does not exist on TYPO3 v12,
so a listener that has to run on both supported versions is registered in
:file:`Configuration/Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_ext/Configuration/Services.yaml

    services:
      MyVendor\MyExt\EventListener\ModifyTcaSelectFieldItemsEventListener:
        tags:
          - name: event.listener
            identifier: 'my-ext/modify-academic-persons-tca-select-items'

..  code-block:: php
    :caption: EXT:my_ext/Classes/EventListener/ModifyTcaSelectFieldItemsEventListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExt\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;

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
            // This project does not show rooms, so the option is not offered.
            $parameters = $event->getParameters();
            $parameters['items'] = array_values(array_filter(
                $parameters['items'],
                static fn(array $item): bool => ($item['value'] ?? null) !== 'contracts.room',
            ));
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

Impact
======

Nothing changes for an installation without a listener: the handlers deliver
the items they delivered before. A listener receives the items after the
handler added its own, and what it hands back with :php:`setParameters()` is
what the backend form offers.

Affected Installations
======================

Installations that adjust the items of the contract select or of the
:guilabel:`Show only selected Fields` select of the persons plugins.

.. index:: Backend, PHP, TCA, ext:academic_persons
