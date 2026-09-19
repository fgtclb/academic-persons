.. _important-contract-contact-data-breaks-sorting-ties-by-uid:

===========================================================
Important: Contract contact data breaks sorting ties by uid
===========================================================

Description
===========

:php:`AddressRepository::findByContractIncludingHidden()` and
:php:`EmailRepository::findByContractIncludingHidden()` ordered by the manual
backend :sql:`sorting` alone. Records can share one :sql:`sorting` value, and
within it their relative order was whatever the database yielded — on PostgreSQL
not the same list twice. Both now append :sql:`uid` ascending as a tiebreaker,
as :php:`PhoneNumberRepository::findByContractIncludingHidden()` already did.

The result feeds the contact lists of the frontend profile editing, the
address records of :php:`academic_contacts4pages` and the frontend user
synchronisation, which adopts the first record carrying its import identifier.

Impact
======

No visible change is expected on SQLite, MySQL and MariaDB: within equal
:sql:`sorting` values, :sql:`uid` ascending is the order they return in
practice, and it is guaranteed now rather than coincidental. PostgreSQL promises
no order without one, so an installation on it may see such a list change once.
Records the editor reordered keep their order.

Affected Installations
======================

Every installation of this extension.

.. index:: Frontend, PHP-API, ext:academic_persons
