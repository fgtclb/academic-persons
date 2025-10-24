.. include:: /Includes.rst.txt

.. _feature-1746706500:

===================================================================================================
Feature: Introduce PSR-14 `ModifyProfileTitlePlaceholderReplacementEvent` in `ProfileTitleProvider`
===================================================================================================

Description
===========

With recent changes a series of features has been implemented to
make the HTML title tag for person profile pages more flexible,
with a placeholder based FlexForm options and also allowing to
influence the default and the setting pageTitleFormat.

The used `ProfileTitleProvider` already looks into the format
string and provides the ability to replace placeholders, which
matches getters in the profile.

To provide even more flexibility, this change introduces a new
PSR-14 Event `ModifyProfileTitlePlaceholderReplacementEvent`,
which is dispatched for each placeholder enriched with quite a
handfull of use-full context information.

Following methods are available on the event:

* `getPluginControllerActionContext(): PluginControllerActionContextInterface`
  containing the request along with easy access methods to site,
  siteLanguage and extbase plugin information and the plugin
  settings.
* `getProfile(): Profile` the current person profile to display.
* `getPlaceholder()` the original/raw placeholder identifier.
* `getReplacement()` the value to replace the placeholder with,
  which may differ already if a earlier event listener changed
  the value using `setReplacement()`.
* `setReplacement(string $replacement): void` to set the value
  used to replace the placeholder.

This event allows project to implement custom placeholders and
the replacement without using old-school xclassing technique.

.. index:: Frontend
