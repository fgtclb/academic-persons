.. _important-ace-915-academic-persons:

=============================================================
Important: Page title placeholders take only text and numbers
=============================================================

Description
===========

A placeholder of the page title format of the profile detail view, the field
:guilabel:`PageTitle display format`, is replaced with the result of the
profile getter it names: ``%%FIRST_NAME%%`` with :php:`getFirstName()`. A
getter that returns no string ended every detail page using the format with a
PHP type error, for example ``%%UID%%``, which returns an integer,
``%%IMAGE%%``, which returns :php:`null` for a profile without an image and a
file reference otherwise, or ``%%CONTRACTS%%``, which returns a list of
records. The listeners of
:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileTitlePlaceholderReplacementEvent`
were never reached.

The page title now takes the result of a getter when it is a string, as
before, or a number, which is written as it is: ``%%UID%%`` gives the uid of
the profile, on a translated page the uid of the profile in the default
language. Every other result, :php:`null`, a boolean, an object or an
array, leaves the placeholder in the title as it is written, the same as a
placeholder that names no getter. A boolean is not written as ``1`` or as an
empty text, neither reads as a title. A getter that is not public or that
requires an argument is not called and leaves the placeholder as well.

Impact
======

A detail page whose format names such a getter now renders. The placeholder
stays in the page title, with its ``%%``, unless a listener of
:php:`ModifyProfileTitlePlaceholderReplacementEvent` replaces it. The listener
receives it unreplaced, :php:`getReplacement()` returns the same text as
:php:`getPlaceholder()`, and an empty replacement removes it from the title.

Affected Installations
======================

Installations whose page title format names a getter that returns no string.
Nothing has to be done on update. A site that wants a text for such a
placeholder, the number of contracts for example, sets it in a listener of the
event, see :ref:`feature-1746706500`.

.. index:: Frontend, PHP-API, ext:academic_persons
