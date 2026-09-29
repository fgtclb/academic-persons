..  index:: Configuration; Frontend user synchronisation
..  _configuration-frontend-user-sync:

=============================
Frontend user synchronisation
=============================

The commands :bash:`academic:createprofiles` and
:bash:`academic:updateprofiles` copy :sql:`fe_users` columns onto the profile
and onto one contract of it, the imported contract. Which column feeds which
property is the :yaml:`frontendUserSync` map of
:file:`Configuration/AcademicPersons/Settings.yaml`.

..  _configuration-frontend-user-sync-shipped:

The shipped map
===============

..  code-block:: yaml
    :caption: EXT:academic_persons/Configuration/AcademicPersons/Settings.yaml

    frontendUserSync:
      profile:
        title: title
        firstName: first_name
        middleName: middle_name
        lastName: last_name
        website: www
      contract:
        position: ''
        room: ''
        organisationalUnit:
          column: ''
          matchBy: uniqueName
          create: false
          storagePid: 0
        functionType:
          column: ''
          matchBy: functionName
          create: false
          storagePid: 0
      physicalAddresses:
        - street: address
          zip: zip
          city: city
          country: country
      emailAddresses:
        - column: email
      phoneNumbers:
        - column: telephone
          type: ''
        - column: fax
          type: ''

It is the mapping the synchronisation followed before the map existed, record
for record: an installation that does not ship a map of its own gets the same
profiles, contracts, contact records and import identifiers as before.

..  _configuration-frontend-user-sync-keys:

The keys
========

Every value is the name of an :sql:`fe_users` column. A property mapped to
``''`` or ``~`` is not synchronised: the synchronisation neither writes nor
clears it, and the value an editor entered stays. A mapped property is written
on every run, and an empty column clears it.

..  list-table::
    :header-rows: 1

    *   -   Key
        -   Maps
    *   -   :yaml:`profile`
        -   Profile properties: :yaml:`title`, :yaml:`firstName`,
            :yaml:`middleName`, :yaml:`lastName`, :yaml:`website`,
            :yaml:`websiteTitle`, :yaml:`publicationsLink`,
            :yaml:`publicationsLinkTitle`, :yaml:`coreCompetences`,
            :yaml:`miscellaneous`, :yaml:`supervisedThesis`,
            :yaml:`supervisedDoctoralThesis` and :yaml:`teachingArea`.
    *   -   :yaml:`contract`
        -   Properties of the imported contract: :yaml:`position` and
            :yaml:`room`, and its relations :yaml:`organisationalUnit` and
            :yaml:`functionType`, each a map of its own, see
            :ref:`configuration-frontend-user-sync-relations`.
    *   -   :yaml:`physicalAddresses`
        -   A list. Each entry is one address and maps :yaml:`street`,
            :yaml:`streetNumber`, :yaml:`additional`, :yaml:`zip`,
            :yaml:`city`, :yaml:`state` and :yaml:`country`.
    *   -   :yaml:`emailAddresses`
        -   A list. Each entry is one e-mail address, from its :yaml:`column`.
    *   -   :yaml:`phoneNumbers`
        -   A list. Each entry is one phone number, from its :yaml:`column`,
            with a :yaml:`type`.

A phone number's :yaml:`type` is one of
:confval:`types.phoneNumberTypes`. ``''`` takes
:confval:`profile.feuser.faxNumberType` for the column :sql:`fax` and
:confval:`profile.feuser.telephoneNumberType` for any other column. A type the
installation does not offer is stored as the undefined type ``''``, like a
configured one.

The gender and the two letters the list navigation files a profile under are
not part of the map: the gender is a fixed selection, and the letters are
derived from the names.

..  _configuration-frontend-user-sync-relations:

Organisational unit and function type
=====================================

The organisational unit and the function type of the imported contract are
records of their own. A column names one of them by a value the record
carries, and the synchronisation assigns the record that matches:

..  list-table::
    :header-rows: 1

    *   -   Key
        -   Meaning
    *   -   :yaml:`column`
        -   The :sql:`fe_users` column holding the value. ``''`` or ``~``: the
            relation is not synchronised, and an editor's choice stays.
    *   -   :yaml:`matchBy`
        -   The field of the record the value is compared with:
            :yaml:`uniqueName` (the default) or :yaml:`unitName` for an
            organisational unit, :yaml:`functionName` for a function type.
    *   -   :yaml:`create`
        -   ``true`` creates a missing record. ``false``, the default, leaves
            the relation empty instead.
    *   -   :yaml:`storagePid`
        -   The page a created record is stored on. Required with
            :yaml:`create: true`, a record is never created on page 0.

A record matches when its field holds exactly the value, after the blanks
around the value are removed. Case and accents count on every database, on
MySQL and MariaDB too, whose collation would ignore them. Hidden records match,
so a unit an editor hid is assigned rather than created a second time, and so
do records on any page, whatever :yaml:`storagePid` says. Deleted records,
drafts of a workspace and translations never match: the value is compared with
the default language.
When several records match, the one with the lowest uid is assigned, the same
one on every database. Keep the values unique to avoid relying on that.

A created organisational unit takes the value as its name, and as its unique
name when it is matched by the unique name. A created function type takes it as
its name. Everything else is left for an editor, including the translations.
The record is saved right away, so the next frontend user with the same value,
and the next run, find it instead of creating another one. Runs of the two
commands in parallel can still create one record twice: run them one after the
other. The names hold 255 characters. On PostgreSQL, and on MySQL and MariaDB
in their default strict mode, creating a longer value stops the command with a
database error. Without strict mode it is cut to 255 characters, never matches
again and is created anew on every run. Keep the values shorter.

A mapped relation belongs to the synchronisation. An empty column clears it,
and so does a value that matches nothing when :yaml:`create` is off. A relation
that is not mapped is never touched.

The employee type is not synchronised, and an editor's choice stays. Its
categories carry no type in :guilabel:`EXT:academic_persons`, so a title can
match categories of any purpose. A project that takes the employee type from
the frontend user data sets it in a listener of its own.

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicPersons/Settings.yaml

    frontendUserSync:
      contract:
        organisationalUnit:
          column: company
          create: true
          storagePid: 42
        functionType:
          column: tx_project_function

..  _configuration-frontend-user-sync-records:

The records of a list
=====================

Every entry of a list is one record of the imported contract, identified by an
import identifier:

*   The first address and the first e-mail address: ``fe_users:<uid>``, the
    identifier they had before the lists existed.
*   Every further address and e-mail address, and every phone number:
    ``<first column>:fe_users:<uid>``, for instance
    ``tx_project_mobile:fe_users:42``.

The synchronisation finds a record by that identifier, hidden ones included,
updates it and never changes its visibility. It creates the record when it is
missing and removes it when every column of its entry is empty. Records an
editor added carry no import identifier and are never touched.

The identifier follows the map, not the data:

*   Moving another entry to the front of the address or e-mail list makes it
    write its data onto the record ``fe_users:<uid>``. The moved entry imports
    a new record, and the one it wrote before is no longer synchronised: it
    stays as it is and is never removed by the synchronisation again.
*   Changing the first column of an entry identified by it imports a new
    record in the same way.

An entry has to map at least one column. To stop synchronising a whole list,
set it to ``[]``.

The imported contract itself exists for as long as one of its mapped sources -
a contract property, a relation or a column of any entry - is set. When all of
them are empty, :bash:`academic:updateprofiles` removes the contract, together
with every address, e-mail address and phone number of it, the ones an editor
added included; :bash:`academic:createprofiles` creates it with every new
profile, as it did before the map. A map that names no source of the contract
at all - no contract property, no relation and three empty lists - does not
synchronise the contract: it is neither created nor removed nor written.

..  _configuration-frontend-user-sync-override:

Changing the map
================

A site package ships its own :file:`Configuration/AcademicPersons/Settings.yaml`
and states what it changes. Maps are merged key by key; a list is replaced as a
whole, so a package adding a phone number repeats the shipped ones:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicPersons/Settings.yaml

    frontendUserSync:
      profile:
        # Editors maintain the website, the synchronisation leaves it alone.
        website: ''
      contract:
        position: tx_project_position
      phoneNumbers:
        - column: telephone
          type: ''
        - column: fax
          type: ''
        - column: tx_project_mobile
          type: mobile

The columns are not created by this map; they are columns of :sql:`fe_users`
the installation already has, for instance filled by an LDAP import. Flush the
TYPO3 caches after changing the map.

A mistake in the map - an unknown property, a value that is not a string, a
list where a map belongs, an entry that maps no column, two entries of one list
named by the same first column, a phone number read from the column
:sql:`phone`, whose identifier is the one of the telephone records written
before 2.4, a relation matched by a field it does not offer, or one that
creates records without a :yaml:`storagePid` - does not break the site. The
synchronisation refuses to run on it: the default profile factory, and every
factory using the mapper below, throws an exception with the code
``1790142324`` before anything is written, and its message names every mistake
with its path.

The map cannot tell a column name from a typo. The default profile factory
therefore also compares it with the frontend user record it synchronises and
refuses a column the record does not have, with the code ``1790142326``. A
misspelled column would otherwise read as empty on every run and remove the
records it imported.

..  _configuration-frontend-user-sync-factories:

Custom profile factories
========================

A profile factory that reads its data from another source - an LDAP directory,
an HR system - can apply the same map instead of copying it. It injects
:php:`\FGTCLB\AcademicPersons\Profile\FrontendUserProfileMapper` and passes an
array keyed like an :sql:`fe_users` record, with its :sql:`uid`:

:php:`applyProfile(array $frontendUserData, Profile $profile)`
    Writes the mapped profile properties.

:php:`assertColumnsExist(array $frontendUserData)`
    Refuses data that lacks its :sql:`uid` or a column the map reads. A
    factory whose data may lack a column - an LDAP entry omits empty
    attributes - does not call it, and the column reads as empty.

:php:`mapsContract()`
    Whether the map names any source of the imported contract. Without one,
    leave the contract alone.

:php:`hasContractData(array $frontendUserData)`
    Whether one mapped contract source is set.

:php:`applyContract(array $frontendUserData, Contract $contract, int $pid)`
    Writes the mapped contract properties and relations, and synchronises the
    addresses, e-mail addresses and phone numbers of the contract. When it
    creates an organisational unit or function type, it saves everything the
    persistence manager holds at that moment. A factory that adds its profile
    before calling it gets the profile saved half-written, and completed when
    it saves at the end.

Creating the profile and the imported contract, and removing that contract,
stays with the factory, as :php:`\FGTCLB\AcademicPersons\Profile\ProfileFactory`
shows.
