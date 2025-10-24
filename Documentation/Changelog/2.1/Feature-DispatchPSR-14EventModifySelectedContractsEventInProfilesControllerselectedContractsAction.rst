.. include:: /Includes.rst.txt

.. _feature-1746706200:

================================================================================================================
Feature: Dispatch PSR-14 event `ModifySelectedContractsEvent` in `ProfilesController::selectedContractsAction()`
================================================================================================================

Description
===========

`ProfileController::selectedContractsAction()` dispatches now the new PSR-14
`ModifySelectedContractsEvent`.

The event provides following methods:

* `getContracts(): QueryResultInterface` return current result set.
* `setContracts(QueryResultInterface $contracts): void` to allow setting a custom
  resultset.
* `getView(): FluidViewInterface|CoreViewInterface` return the current view to
  allow assigning custom values to the view.
* `getPluginControllerActionContext(): PluginControllerActionContextInterface`
  to provide more context information

.. index:: Frontend
