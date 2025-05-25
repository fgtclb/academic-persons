# TYPO3 Extension `Academic person database` (READ-ONLY)

|                  | URL                                                                   |
|------------------|-----------------------------------------------------------------------|
| **Repository:**  | https://github.com/fgtclb/academic-persons                            |
| **Read online:** | https://docs.typo3.org/p/fgtclb/academic/academic-persons/main/en-us/ |
| **TER:**         | https://extensions.typo3.org/extension/academic_persons/              |

## Description

This TYPO3 extension adds a personal database to TYPO3, with requirements that
are usually used for colleges, universities or public institutions. The profile
data records can be automatically created and linked in conjunction with the
LDAP extension on the basis of FE user data records. The data can be enriched
with data via separate HiS-in-One synchronization. Data records can be created,
edited and displayed in the front end and in different display modes.

The following profile data is available to users after installation:

* Master data:
    * Salutation / Gender
    * title
    * First name
    * Last name
    * Middle name
    * Website + website link
    * Image
    * URL
* Contracts:
    * Each person can receive any amount of contract data in order to be displayed
      in individual roles, functions or organizational units.
    * Position
    * Organizational unit / department - link to own data type
    * Contract start / end
    * Location - e.g. for campus Link to own data type
    * Room information
    * Office hours
* Address data
* Email addresses
* Telephone addresses
* Linked pages (in combination with the Contact-For-Pages extension)
* Employment category based on system categories
* Profile text data:
    * All textual content can be freely designed using the standard text editor.
    * Learning areas/fields of activity
    * Research areas
    * Supervised dissertations
    * Supervised doctoral theses
    * Miscellaneous information
* Profile timeline entries
    * All timeline entries allow the chronological presentation of content, usually
      with a start and/or end year, a title, a short description and a link
    * Research projects
    * Academic career
    * Memberships/committee activities
    * Networks and cooperation's
    * Publications
    * Lectures
    * Press/Media Publications

The extension also provides some plugins to display the persons in the frontend
as a list view and detail view for each person.

> [!NOTE]
> This extension is currently in beta state - please notice that there might be changes to the structure

## Compatibility

| Branch | Version       | TYPO3       | PHP                                     |
|--------|---------------|-------------|-----------------------------------------|
| main   | 2.0.x-dev     | ~v12 + ~v13 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |
| 2      | ^2, 2.0.x-dev | ~v12 + ~v13 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |
| 1      | ^1, 1.2.x-dev | v11 + ~v12  | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |

## Installation

Install with your flavour:

* [TER](https://extensions.typo3.org/extension/academic_persons/)
* Extension Manager
* composer

We prefer composer installation:

```bash
composer require 'fgtclb/academic-persons':'^2'
```

> [!IMPORTANT]
> `2.x.x` is still in development and not all academics extension are fully tested in v12 and v13,
> but can be installed in composer instances to use, test them. Testing and reporting are welcome.

**Testing 2.x.x extension version in projects (composer mode)**

It is already possible to use and test the `2.x` version in composer based instances,
which is encouraged and feedback of issues not detected by us (or pull-requests).

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring each extension but still use stable versions over development versions:

```shell
composer config minimum-stability "dev" \
&& composer config "prefer-stable" true
```

and installed with:

```shell
composer require \
  'fgtclb/academic-persons':'2.*.*@dev'
```

## Upgrade from `1.x`

Upgrading from `1.x` to `2.x` includes breaking changes, which needs to be
addressed manualy in case not automatic upgrade path is available. See the
[UPGRADE.md](./UPGRADE.md) file for details.

## Credits

This extension was created by [FGTCLB GmbH](https://www.fgtclb.com/).

[Find more TYPO3 extensions we have developed](https://github.com/fgtclb/).
