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
            :yaml:`room`.
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
a contract property or a column of any entry - is set. When all of them are
empty, :bash:`academic:updateprofiles` removes the contract, together with
every address, e-mail address and phone number of it, the ones an editor added
included; :bash:`academic:createprofiles` creates it with every new profile,
as it did before the map. A map that names no source of the contract at all -
no contract property and three empty lists - does not synchronise the contract:
it is neither created nor removed nor written.

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
before 2.4 - does not break the site. The synchronisation
refuses to run on it: the default profile factory, and every factory using the
mapper below, throws an exception with the code ``1790142324`` before anything
is written, and its message names every mistake with its path.

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
    Writes the mapped contract properties and synchronises the addresses,
    e-mail addresses and phone numbers of the contract.

Creating the profile and the imported contract, and removing that contract,
stays with the factory, as :php:`\FGTCLB\AcademicPersons\Profile\ProfileFactory`
shows.
