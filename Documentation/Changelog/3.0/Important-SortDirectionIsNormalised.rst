..  _important-ace-439-academic-persons:

=======================================
Important: Sort direction is normalised
=======================================

Description
===========

A persons list applied its sort field only when the sort direction was
exactly ``asc`` or ``desc``. The FlexForm offers just these two, but the
direction also comes from the site setting and TypoScript constant
``plugin.tx_academicpersons.demand.sortByDirection``, which are free text,
and from listeners of :php:`ModifyProfileDemandEvent`. A value such as
``DESC`` or ``Asc`` dropped the sort field, and the list came out in the
order the profiles were created.

:php:`FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileDemand::setSortByDirection()`
now reads the direction in any letter case and without surrounding
whitespace, and stores it as ``asc`` or ``desc``. Any other value keeps the
direction the demand had, ``asc`` for a fresh demand, so the sort field still
applies. The program list of ``academic_programs`` treats an unknown sorting
the same way.

Impact
======

A direction written in another letter case is applied now. A listener that
sets a direction and reads it back gets the lowercase form, and a listener
that sets an unknown direction leaves the previous one in place instead of
removing the sort field. ``asc`` and ``desc`` behave as before.

Affected Installations
======================

Installations that set the sort direction in another letter case, or that
set it from a listener of :php:`ModifyProfileDemandEvent`.

..  index:: Frontend, PHP-API, ext:academic_persons
