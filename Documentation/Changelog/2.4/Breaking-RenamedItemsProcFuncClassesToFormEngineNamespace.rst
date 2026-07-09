.. _breaking-1783613367:

============================================================================
Breaking: Renamed `itemsProcFunc` handler classes to `FormEngine` namespace
============================================================================

Description
===========

While introducing the shared PSR-14 event
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` to allow
modifying the select items of fields populated by an `itemsProcFunc`, the two
`itemsProcFunc` handler classes shipped by `EXT:academic_persons` received a
proper namespace and class names suitable for classes providing
`itemsProcFunc` implementations.

The following classes have been renamed and moved:

*  :php:`\FGTCLB\AcademicPersons\Backend\Form\ContractItemsProcFunc` is now
   :php:`\FGTCLB\AcademicPersons\Backend\FormEngine\ContractItems`.

*  :php:`\FGTCLB\AcademicPersons\Backend\Form\ProfileShowFieldsItemProcFunc`
   is now :php:`\FGTCLB\AcademicPersons\Backend\FormEngine\ProfileShowFieldsItems`.

Additionally the entry method of the profile show fields handler has been
streamlined from :php:`showFields()` to :php:`itemsProcFunc()` to match the
other handlers.

The references to these classes in the shipped plugin FlexForms have been
adjusted accordingly.

Impact
======

Referencing the old class names or the old :php:`showFields()` method throws
a PHP error. This affects TCA/FlexForm `itemsProcFunc` configurations,
projects extending or replacing these classes and any code instantiating them
directly.

Affected Installations
======================

Installations that reference the old classes in own TCA/FlexForm
configuration, extend or replace them, or call them directly.

Migration
=========

Replace all usages of the old class names with the new ones and use the
:php:`itemsProcFunc()` method for both handlers:

.. code-block:: text

    FGTCLB\AcademicPersons\Backend\Form\ContractItemsProcFunc->itemsProcFunc
    => FGTCLB\AcademicPersons\Backend\FormEngine\ContractItems->itemsProcFunc

    FGTCLB\AcademicPersons\Backend\Form\ProfileShowFieldsItemProcFunc->showFields
    => FGTCLB\AcademicPersons\Backend\FormEngine\ProfileShowFieldsItems->itemsProcFunc

Projects that only replaced the shipped items should consider using the new
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` event listener
instead of a custom `itemsProcFunc`.

.. index:: Backend, PHP, TCA, ext:academic_persons
