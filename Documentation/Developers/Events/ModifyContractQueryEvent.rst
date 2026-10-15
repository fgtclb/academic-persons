..  _developers-event-modify-contract-query-event:

========================
ModifyContractQueryEvent
========================

:php:`\FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent` is dispatched
immediately before the contract query of the selected-contracts plugin is
executed, and a listener adds conditions to it. It is the contract counterpart
of :ref:`developers-event-modify-profile-query-event`, and the rules of that
page apply to it unchanged.

When it is dispatched
=====================

:php:`ContractRepository` dispatches the event once each time the
selected-contracts plugin renders and an editor selected at least one
contract, for the lookup of the selected contracts by their uids. The plugin
dispatches nothing of the kind while its selection is empty.

Code that calls :php:`ContractRepository::findByUids()` outside the plugin
dispatches the event as well, without a plugin context. No other plugin queries
contracts through it: the other plugins reach the contracts of a profile
through the profile.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   What it does
    *   -   :php:`getQuery()`
        -   The Extbase :php:`QueryInterface` of the contracts, to build the
            conditions on.
    *   -   :php:`addConstraint()`
        -   Adds a condition. Every listener may add any number of them.
    *   -   :php:`getConstraints()`
        -   The conditions the listeners added so far.
    *   -   :php:`getPluginControllerActionContext()`
        -   The plugin the query is rendered for, see
            :ref:`developers-plugin-action-context`. :php:`null` where the
            query has no plugin behind it.

There is no demand: the query only ever looks up the contracts an editor
selected.

Example
=======

A site shows the contracts of a selected-contracts element only when they are
of one employee type, for example in a directory of the teaching staff. The
category of that employee type is set in TypoScript, as a setting of the
plugins:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/Sets/MyExtension/setup.typoscript

    plugin.tx_academicpersons.settings.myExtension.employeeType = 12

The listener reads it from the settings of the context, which hold the
TypoScript settings and the ones of the content element:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/RestrictContractsToEmployeeType.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class RestrictContractsToEmployeeType
    {
        #[AsEventListener(identifier: 'my-extension/restrict-contracts-to-employee-type')]
        public function __invoke(ModifyContractQueryEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context === null) {
                return;
            }
            $employeeType = (int)($context->getSettings()['myExtension']['employeeType'] ?? 0);
            if ($employeeType <= 0) {
                return;
            }
            $query = $event->getQuery();
            $event->addConstraint($query->equals('employeeType', $employeeType));
        }
    }

Rules and pitfalls
==================

*   The conditions are combined with the selection of the editor and the
    hidden records setting with a logical **AND**. A listener narrows the
    selection, it cannot add contracts to it.
*   The ordering is set after the event, so an ordering a listener sets is
    overwritten. The plugin renders the contracts in the order of the
    selection.
*   The query settings are live, as for the profile query: a listener that
    changes them widens the result past what the content element asked for.
    Add constraints and leave the rest of the query alone.
*   The contracts a profile shows in the other plugins are not queried here.
    Which of them a profile shows is decided by the contract options of the
    content element, see :ref:`configuration-contract-display`.

Introduced in
=============

*   :ref:`feature-profile-and-contract-query-events` (2.4) introduced the
    event.
*   :ref:`feature-ace-715-academic-persons-query-event-context` added the
    plugin context in 3.0.
*   :ref:`breaking-profile-and-contract-finder-signatures` describes the
    finder that hands the context over.
