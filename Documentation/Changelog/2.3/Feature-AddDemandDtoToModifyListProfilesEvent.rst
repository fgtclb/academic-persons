.. include:: /Includes.rst.txt

.. _feature-1746706100:

====================================================
Feature: Add demand DTO to `ModifyListProfilesEvent`
====================================================

Description
===========

PSR-14 Event :php:`\FGTCLB\AcademicPersons\Event\ModifyListProfilesEvent` got
following new methods:

* :php:`getProfileDemand(): ProfileDemand`
* :php:`setProfileDemand(ProfileDemand $profileDemand): void`

This gives event listeners the ability to redo a query with the same
or further enriched demand object to replace the retrieved profiles
result already existing in the event and/or enrich data in the view.

..  note::

    Extension supporting earlier versions of the extension need to
    check for the existence of the getter and setter method before
    calling them.

.. index:: PHP, Frontend, PSR-14
