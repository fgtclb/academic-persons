..  _feature-configurable-frontend-user-phone-number-types:

======================================================
Feature: Configurable frontend user phone number types
======================================================

Description
===========

Telephone and fax numbers imported from frontend users no longer carry the
hard-coded values ``phone`` and ``fax``, which are not selectable in the shipped
phone-number type list. Two new extension configuration options say which type
each source field imports as:

..  code-block:: none

    profile.feuser.telephoneNumberType = business
    profile.feuser.faxNumberType = business

Both default to ``business``, which is part of the shipped
:confval:`types.phoneNumberTypes`. Each value is validated against that list
before it is written: a value the installation does not offer falls back to the
undefined type ``''``, which the backend select ships as its first item. The
synchronization therefore never writes a type the backend cannot resolve.

An existing selectable type is an editor decision and is left alone. Only the
two historical values are corrected, and only where they are not selectable on
that installation: an installation that genuinely offers ``phone`` or ``fax`` as
types keeps them.

See :ref:`important-imported-telephone-records-use-a-stable-identifier` for the
changed import identifier of telephone records.

Impact
======

New and updated imports carry a configured, selectable type. Without any
configuration, the next :bash:`academic:createprofiles` or
:bash:`academic:updateprofiles` run writes ``business`` where an imported
record still carries an unselectable ``phone`` or ``fax``.

Installations that want the imported numbers typed differently, or not typed
at all, set the two options in :guilabel:`Admin Tools > Settings > Extension
Configuration` for :guilabel:`academic_persons`. No other configuration
changes.

Affected Installations
======================

Installations that import profiles from frontend users with
:bash:`academic:createprofiles` or :bash:`academic:updateprofiles`.

.. index:: Backend, CLI, ext:academic_persons
