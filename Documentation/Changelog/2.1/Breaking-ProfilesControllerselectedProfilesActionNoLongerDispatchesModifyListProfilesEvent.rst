.. include:: /Includes.rst.txt

.. _breaking-1746705900:

=======================================================================================================
Breaking: `ProfilesController::selectedProfilesAction()` no longer dispatches `ModifyListProfilesEvent`
=======================================================================================================

Description
===========

`ProfilesController::selectedProfilesAction()` dispatched the `ModifyListProfilesEvent`
PSR14 event accidentally due to copy&paste when introducing the new plugin and action
for `2.0.x`. This event is no longer dispatched for this action, instead the new and
correct event `ModifySelectedProfilesEvent` is now dispatched.


Affected Installations
======================

`EXT:academic_partners` installations listening to the `ModifyListProfilesEvent` event.


Migration
=========

Adapt any listeners/subscribers accordingly to the correct event.

.. index:: Frontend
