.. include:: /Includes.rst.txt

.. _feature-1746706000:

===========================================================
Feature: Introduce `PluginControllerActionContext` suitable
===========================================================

Description
===========

A new readonly DTO object `PluginControllerActionContext` is introduced and is
attached to dispatched PSR-14 events in `ProfileController` actions.


Impact
======

Following main getters are provided:

* `getApplicationType(): ApplicationType` to return the TYPO3 application type
 for the current request.
* `getExtbaseRequestParameters(): ?ExtbaseRequestParameters` to retrieve extbase
 attribute from request as a simple accessor.
* `getRequest(): ServerRequestInterface` to return the current request.
* `getSettings(): array` to retrieve raw plugin settings (TypoScript, FlexForm).
* `getSite(): ?Site` to retrieve resolved site configuration.
* `getLanguage(): ?SiteLanguage` to retrieve resolves site language.

Following getters dispatches to `ExtbaseRequestParameters` methods and returning
null in case the request attribute is not set in the request:

* `getActionName(): ?string`
* `getControllerName(): ?string`
* `getControllerObjectName(): ?string`
* `getControllerExtensionKey(): ?string`
* `getControllerExtensionName(): ?string`
* `getPluginName(): ?string`

.. index:: Frontend
