..  _developers-event-modify-tca-select-field-items-event:

==============================
ModifyTcaSelectFieldItemsEvent
==============================

:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` belongs to
:guilabel:`academic_base`. The item providers of several academic extensions
dispatch it when the backend builds the items of a select field, so one
listener reorders, removes or adds items of any of them. The event and its
parameters are documented in the :ref:`manual of academic_base
<fgtclb/academic-base:developers-event-modify-tca-select-field-items-event>`.
This page describes the fields of this extension that dispatch it.

When it is dispatched
=====================

Two item providers of this extension dispatch the event, after they added
their own items to the ones the field declares:

..  list-table::
    :header-rows: 1

    *   -   Item provider
        -   Table and field
        -   Field in the backend
    *   -   :php:`\FGTCLB\AcademicPersons\Backend\FormEngine\ProfileShowFieldsItems`
        -   :sql:`tt_content`, FlexForm field ``settings.showFields`` of the
            list, list-and-detail, card, selected-profiles and
            selected-contracts plugins
        -   :guilabel:`Show only selected Fields`
    *   -   :php:`\FGTCLB\AcademicPersons\Backend\FormEngine\ContractItems`
        -   :sql:`tt_content`, FlexForm field ``settings.selectedContracts``
            of the selected-contracts plugin
        -   :guilabel:`Selected Contracts`
    *   -   :php:`\FGTCLB\AcademicPersons\Backend\FormEngine\ContractItems`
        -   :sql:`tx_academiccontacts4pages_domain_model_contact`, field
            ``contract``, the contract of a page contact of
            :guilabel:`academic_contacts4pages`
        -   :guilabel:`Contract`

The event is dispatched each time the backend form renders the field, not when
the record is saved.

What this extension passes in
=============================

:php:`getParameters()` returns the parameters of the item provider, the ones
TYPO3 hands an :php:`itemsProcFunc`, and :php:`setParameters()` replaces them.
What a listener of these fields finds in them:

*   ``table`` and ``field`` name the field. For a FlexForm field, ``table`` is
    :sql:`tt_content` and ``field`` the name of the FlexForm element, for
    example ``settings.showFields``.
*   ``items`` holds the items the field declares, followed by the ones of the
    provider. The shown fields offer ``profile.image``,
    ``contracts.position``, ``contracts.functionType``,
    ``contracts.organisationalUnit``, ``contracts.room``,
    ``contracts.officeHours``, ``contracts.physicalAddresses``,
    ``contracts.emailAddresses``, ``contracts.phoneNumbers`` and
    ``contracts.location``, each in the group ``profile`` or ``contracts``.
    The contract selects offer one item per contract, its uid as the value,
    sorted by the last and first name of the profile.
*   The contract selects are already restricted to the storage folders page
    TSconfig names for the field, see
    :ref:`configuration-contract-select-storage-scope`. A contract the record
    references is offered all the same.
*   ``row`` is the record being edited, and for a FlexForm field the values of
    the FlexForm. ``flexParentDatabaseRow`` is the content element then.

In the backend form, TYPO3 v13 and v14 hand the static items of a field to the
item provider as :php:`\TYPO3\CMS\Core\Schema\Struct\SelectItem` objects,
and the items a provider adds are arrays. A :php:`SelectItem` implements
:php:`\ArrayAccess`, so a closure typed :php:`array|SelectItem` that reads
:php:`$item['value']` works for both.

Example
=======

A site does not show rooms, so editors are not offered the room in
:guilabel:`Show only selected Fields` of the persons plugins:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/RemoveRoomFromShownFields.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Schema\Struct\SelectItem;

    final class RemoveRoomFromShownFields
    {
        #[AsEventListener(identifier: 'my-extension/remove-room-from-shown-fields')]
        public function __invoke(ModifyTcaSelectFieldItemsEvent $event): void
        {
            $parameters = $event->getParameters();
            if ($parameters['table'] !== 'tt_content' || $parameters['field'] !== 'settings.showFields') {
                return;
            }
            $parameters['items'] = array_values(array_filter(
                $parameters['items'],
                static fn(array|SelectItem $item): bool => ($item['value'] ?? null) !== 'contracts.room',
            ));
            $event->setParameters($parameters);
        }
    }

Rules and pitfalls
==================

*   **Check the table and the field.** The event is shared: the item
    providers of :guilabel:`academic_jobs` and
    :guilabel:`academic_contacts4pages` dispatch it as well, and the same
    listener is called for all of them.
*   **A value that is not among the items is lost on the next save.** The
    fields of this page store plain values, so the backend form drops a
    stored value the listener removed from the items, and saving the record
    writes it away. Remove only items no record uses, or keep the ones the
    record in ``row`` references.
*   The event decides what the backend offers. What the plugins render is
    decided by what the content element stored.

Introduced in
=============

*   :ref:`feature-1783613368` (2.4) made the item providers of this extension
    dispatch the event.
*   :ref:`breaking-1783613367` (2.4) moved the item providers to the
    :php:`Backend\FormEngine` namespace.
*   :ref:`feature-restrict-the-backend-contract-selects` (2.4) restricts the
    contract selects before the event.
