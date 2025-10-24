.. include:: /Includes.rst.txt

.. _breaking-1746705700:

================================================================================
Breaking: Replace constructor DI with inject-methods in `AbstractProfileFactory`
================================================================================

Description
===========

Using constructor dependency injection in abstract classes defines the constructor
as API, which should be avoided by using the inject-method approach and allows to
implement classes using constructor DI without the requirement to deal and align
with parent (abstract) class constructor and passing it down.

`AbstractProfileFactory` used constructor DI and therefore violated the above
described design pattern.

Constructor DI arguments are now replaced with inject-methods in the abstract
`\FGTCLB\AcademicPersons\Profile\AbstractProfileFactory`.

See `Autowiring other Methods (e.g. Setters and Public Typed Properties) <https://symfony.com/doc/current/service_container/autowiring.html#autowiring-other-methods-e-g-setters-and-public-typed-properties>`__

Impact
======

`AbstractProfileFactory` used constructor DI and therefore violated the above
described design pattern.

Constructor DI arguments are now replaced with inject-methods in the abstract
`\FGTCLB\AcademicPersons\Profile\AbstractProfileFactory`.


Affected Installations
======================

Installations using the abstract and defining own constructor DI arguments.


Migration
=========

Implementation using the abstract and defining own constructor DI arguments
needs to remove the removed parent arguments and avoid calling the parent
constructor.

Additionally, the `\Symfony\Contracts\Service\Attribute\Required` attribute is
used for the inject methods to tell symfony DI that these inject methods needs
to be called and are mandatory - beside having a visually glue for developers.

.. index:: Frontend
