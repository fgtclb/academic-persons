.. include:: /Includes.rst.txt

.. _feature-1746706400:

=============================================================================
Feature: Allow modifying default and settings pageTitleFormat for detail view
=============================================================================

Description
===========

It's possible to set a pageTitleFormat in the plugin settings for
plugins using the `ProfileController::detailAction()`, which is
used in `ProfileTitleProvider` to set the HTML page title for the
person profile pages.

This change extends the existing `ModifyDetailProfileEvent`, which
is dispatched in the `ProfileController::detailAction()`, to make
the default and the setting pageTitleFormat changeable using an
PSR-14 event listener.

This gives developers the ability to implement a wide range of
use-cases in projects, for example adding a prefix to the format
based on the site configuration or similar.

New `ModifyDetailProfileEvent` methods:

* `getDefaultPageTitleFormat(): string`
* `setDefaultPageTitleFormat(string $defaultPageTitleFormat): void`
* `getSettingsPageTitleFormat(): string`
* `setSettingsPageTitleFormat(string $settingsPageTitleFormat): void`
* `getPageTitleFormatToUse(): string`

The `getPageTitleFormatToUse(): string` is a calculated function to
get the aggregated format to use, which allows checking the result
in event listeners and determine the format finally used as detail
view page title.

The original default pageTitleFormat is `ProfileTitleProvider::DETAIL_PAGE_TITLE_FORMAT`.

.. index:: Frontend
