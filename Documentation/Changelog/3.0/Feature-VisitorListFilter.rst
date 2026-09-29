..  _feature-1790802602:

==============================================================
Feature: Visitors filter the list by function type and by unit
==============================================================

Description
===========

The list and list-and-detail elements offer two new fields in their plugin
options, :guilabel:`Visitors may filter by function type` and
:guilabel:`Visitors may filter by organisational unit`. With a field on, the
list takes a function type or an organisational unit from the request and shows
only the profiles with a contract that carries it. The pagination and the
letter navigation count only those profiles, and their links keep the filter.

With both filters set, the function type and the unit have to be on the same
contract. The options of a filter are the records the element is restricted to,
or all of them, ordered by name, so a visitor never widens what the editor
chose. A value that is not one of the options is ignored, and so is every value
while its field is off or the element shows a manual selection.

The list template receives the options as `filterOptions`, and the demand
carries the active values as `functionTypeFilter` and
`organisationalUnitFilter`. The shipped templates do not render a filter form
yet.

See :ref:`configuration-visitor-filters` and :ref:`templates-visitor-filters`.

Impact
======

Both fields are off by default, so a list renders what it rendered before until
an editor switches one on. The card element shares the plugin options with the
list and hides both fields.

A listener of :php:`ModifyProfileDemandEvent` finds the two new demand
properties, each an offered uid or `0`. A project class that extends
:php:`ProfileDemand` and declares a property or method of the same name with
another type fails to load, and has to follow the new declaration or rename
its own.

..  index:: Backend, Frontend, FlexForm, ext:academic_persons
