.. include:: /Includes.rst.txt

.. _feature-1746706100:

=================================================================
Feature: `pageTitleFormat` FlexForm option for person detail view
=================================================================

Description
===========

It's now possible to define the format used to generate the HTML PageTitle for
the detail view of persons in the frontend, using the TYPO3 PageTitle API.

The default format used based on `Profile` extbase model data is:

`%%TITLE%% %%FIRST_NAME%% %%MIDDLE_NAME%% %%LAST_NAME%%`

To allow easier customization in project, a new FlexForm option `pageTitleFormat`
has been added to `listanddetail` plugin and as single new option for the `detail`
plugin, which uses `TCA type=input` combined with a ValuePicker to allow picking
from a list of pre-defined formats while still making it possible to define own
custom format directly on plugin usage.

The mapping from placeholder to extbase model is based on transforming the
placeholder to camelcase using first character after separators and prefix
it with `get`, and if the getter exists it is called to retrieve the value.

For example:

.. code-block::text
    PLACEHOLDER...: %%FIRST_NAME%%
    CAMEL_CASE....: FirstName
    PREFIXED......: getFirstName

which calls `Profile->getFirstName()` to retrieve the replacement value from
the detail view profile.


The whole process contains some behaviour, which needs to be kept in mind:

* Leading and trailing spaces are trimmed from each value(placeholder).
* Multiple spaces are removed from the whole format string.
* Leading and trailing spaces are trimmed from the whole format pattern, after
  placeholder resolving has been processed.

Example for allowed characters as placeholder identifier:

.. code-block::text
    %%SOME.IDENTIFIER%%
    %%SOME:IDENTIFIER%%
    %%SOME;IDENTIFIER%%
    %%SOME-IDENTIFIER%%
    %%SOME_IDENTIFIER%%
    %%SOME/IDENTIFIER%%
    %%SOME\IDENTIFIER%%
    %%SOME IDENTIFIER%%

Note that most of them has no handling for matching person profile getters, but
are use-full for advanced replacement using the experimental PSR-14 event.

.. index:: FlexForm, Frontend
