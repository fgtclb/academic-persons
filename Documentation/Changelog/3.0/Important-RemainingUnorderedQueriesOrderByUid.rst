.. _important-remaining-unordered-queries-order-by-uid:

=======================================================
Important: Remaining unordered queries now order by uid
=======================================================

Description
===========

The sweep that ordered the unordered profile queries (see
:ref:`important-unordered-queries-order-by-uid`) missed five query paths, which
kept returning rows in whatever order the database yielded:

*   :php:`ContractRepository::findAll()`, which builds every contract select
    item in the backend (TCA :php:`itemsProcFunc` and FlexForm)
*   :php:`ContractRepository::findByUids()`, which resolves the contracts of
    the "selected contracts" plugin
*   :php:`ProfileRepository::findByUids()`, which resolves the profiles of the
    "selected profiles" plugin
*   :php:`ProfileRepository::findByFrontendUser()`, which resolves the profiles
    of a frontend user for the frontend editing of
    :php:`academic_persons_edit`
*   :php:`ProfileRepository::findByDemand()` with a non-empty demanded ordering
    — the list plugin's :guilabel:`Sort by` — which carried no tiebreaker, so
    profiles equal in it (two people sharing a last name) had no defined
    relative order

The first four now order by :sql:`uid` ascending; the demanded ordering keeps
winning and gets :sql:`uid` ascending appended as a tiebreaker.

Three further methods ordered by :sql:`sorting` alone:
:php:`AddressRepository::findByContractIncludingHidden()`,
:php:`EmailRepository::findByContractIncludingHidden()` and
:php:`PhoneNumberRepository::findByContractIncludingHidden()`, which list the
contact records of a contract for the frontend editing. Records an editor
never reordered share a :sql:`sorting` value, and their relative order was
whatever the database yielded. All three now append :sql:`uid` ascending as
the tiebreaker.

Impact
======

No visible change is expected: :sql:`uid` ascending is the order every
supported database returned in practice, it is simply guaranteed now rather
than coincidental.

For the two uid selection methods the order of the editor's selection is
deliberately **not** reproduced — :php:`in()` does not preserve it, and it was
never delivered before. Honouring the selection order would be a behaviour
change beyond making the lists reproducible.

Affected Installations
======================

Every installation of this extension.

.. index:: Frontend, Backend, PHP-API, ext:academic_persons
