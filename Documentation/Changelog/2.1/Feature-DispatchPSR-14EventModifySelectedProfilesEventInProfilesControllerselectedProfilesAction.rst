.. include:: /Includes.rst.txt

.. _feature-1746706300:

==============================================================================================================
Feature: Dispatch PSR-14 event `ModifySelectedProfilesEvent` in `ProfilesController::selectedProfilesAction()`
==============================================================================================================

Description
===========

`ProfileController::selectedProfilesAction()` dispatches now the new PSR-14
`ModifySelectedProfilesEvent` instead of erroneous copied listAction event
`ModifyListProfilesEvent`, which is no longer dispatched. That should not
be that of an issue for most implementations.

The event provides following methods:

* `getProfiles(): QueryResultInterface` return current result set.
* `setProfiles(QueryResultInterface $profiles): void` to allow setting a custom
  resultset.
* `getView(): FluidViewInterface|CoreViewInterface` return the current view to
  allow assigning custom values to the view.
* `getPluginControllerActionContext(): PluginControllerActionContextInterface`
  to provide more context information

.. index:: Frontend
