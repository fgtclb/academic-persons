..  _important-1790607226:

========================================================================
Important: The profile queries see a letter switching off the pagination
========================================================================

Description
===========

A letter of the letter navigation switches the pagination of the profile list
off. The list decided that after its profile query, so a listener of
:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent` read the
pagination as switched on while the plugin view event read it as switched off.

The list now decides it before its query and builds its plugin action context
afterwards. The profile query, the query of the letter navigation and the
plugin view event receive the same context, as the 3.0 changelog of
:guilabel:`academic_base` describes for every academic plugin.

Impact
======

Under a letter, :php:`getSettings()['paginationEnabled']` of the context is
:php:`'0'` for every event of the rendering. The query itself does not read the
setting.

The letter the visitor chose decides the pagination now, not the letter the
demand carries after the query. A listener of
:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileDemandEvent` that changed the
letter on the demand it was handed also moved the pagination switch, because
the list read the letter from the same object after the query. It no longer
does: a listener that clears the letter in place gets the whole list without
pagination, where it got a paginated list before. A listener that hands back a
demand of its own through :php:`setDemand()`, the documented way, sees no
difference.

The plugin view event receives the persons context the query events receive.
It implements the :guilabel:`academic_base` interface the event declares.

..  index:: Frontend, PHP-API, ext:academic_persons
