.. include:: /Includes.rst.txt

.. _feature-1746706600:

=====================================================================
Feature: Introduce localized pageTitleFormat placeholder (`LLL:EXT:`)
=====================================================================

Description
===========

It's a valid use-case to use a pageTitleFormat for the person
profile detail page view as HTML page title including localized
text as placeholders and could be implemented using the PSR-14
event `ModifyProfileTitlePlaceholderReplacementEvent` dispatched
in the `ProfileTitleProvider`.

Localization is a generic feature and it's most likely that it's
use-full for a broader audience this change adds now support for
localization placeholder in the format:

`%%LLL:EXT:<extension-key>/Resources/.../locallang.xlf:identifier%%`

Note that no context fallback detection is made like within fluid
templates or extbase context areas and a valid relative path for
the default language file within a extension needs to be provided.

Functional tests are added to cover the new feature basically and
provide some examples, using a dedicated test fixture extension.

.. index:: Frontend
