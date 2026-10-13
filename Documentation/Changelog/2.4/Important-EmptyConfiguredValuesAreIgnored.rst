..  _important-ace-438-academic-persons:

==============================================
Important: Empty configured values are ignored
==============================================

Description
===========

The extension configuration options :confval:`demand.allowedSortByValues`,
:confval:`demand.allowedGroupByValues`, :confval:`types.emailAddressTypes`,
:confval:`types.phoneNumberTypes` and :confval:`types.physicalAddressTypes`
are comma separated lists of ``value=label`` entries.

An emptied option, a trailing comma or two commas in a row used to add an
item with an empty value. For the sort and group values that item is also
the value of "none" in a persons list element, so "none" passed the check
against the allowed values. Every persons list with its sorting or grouping
on "none" then ordered by a column without a name, which SQLite, MariaDB and
PostgreSQL reject with a database error. The backend selects of the list
element and of email addresses, phone numbers and addresses offered an item
without a label. An entry with an empty side, such as ``lastName=`` or
``=Last name``, raised a PHP warning every time the list was read.

Empty entries are ignored now. An entry without a label, such as
``lastName=``, is labelled with its value, like an entry without ``=``. An
entry without a value, such as ``=Last name``, is ignored. When the extension
configuration does not declare the sort or group values, or holds no text for
them, the list renders in the order the profiles were created and the select
offers only "none".

Impact
======

With such a configuration, persons lists on "none" render instead of
failing, and the selects no longer offer an unlabelled item. A record whose
type was stored from an entry without a value, such as ``=Private``, kept
the label as its type. That item is gone, so the backend shows the stored
type as an invalid value until the record is saved with another one.

Affected Installations
======================

Installations whose configured lists contain an empty entry or an entry with
an empty side.

..  index:: Backend, Frontend, ext:academic_persons
