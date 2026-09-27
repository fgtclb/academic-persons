..  _feature-plugin-view-event:

===================================================
Feature: The plugins dispatch the plugin view event
===================================================

Description
===========

The list, list and detail, detail, card, selected profiles and selected
contracts plugins of :guilabel:`academic_persons` dispatch
:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` of
:guilabel:`academic_base`, once each time they render, after assigning their
own variables. A listener adds a variable to their templates without replacing
the controller.

The context of the event names the extension :php:`AcademicPersons` and the
plugin names :php:`List`, :php:`ListAndDetail`, :php:`Detail`, :php:`Card`,
:php:`SelectedProfiles` and :php:`SelectedContracts`, which a listener checks
for the plugin it means.

It replaces the list, detail, selected profiles and selected contracts events
of this extension, which are removed, see
:ref:`breaking-removed-profile-view-events`.

The event, with an example listener, is described in the `changelog of
academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/3.0/Feature-ModifyPluginViewEvent.html>`__.

Impact
======

A project adds a variable to the templates of these plugins with an event
listener instead of a subclass or a copy of the controller. The behaviour is
the same on TYPO3 v13 and v14.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_persons
