..  _upgrade-from-2-3-to-2-4:

=========================
Upgrading from 2.3 to 2.4
=========================

Version 2.4 hides the six content elements of :guilabel:`academic_persons` in
the backend until a site includes the page TSconfig of their components, which
is the biggest change for an existing site. Work through the sections in order.
Skip a step that does not apply to the installation.

..  _upgrade-from-2-3-to-2-4-before-you-start:

Before you start
================

Version 2.4 runs on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4, with PHP 8.1
or later. That is the same as for 2.3, so the TYPO3 core does not have to
change for this upgrade.

:guilabel:`academic_persons` 2.4 needs these packages. Upgrade them first or in
the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 1.x, new in 2.4, which this
    extension and :guilabel:`academic_base` 2.4 require. It is declared in
    :file:`composer.json` as `^1.0` and in :file:`ext_emconf.php` as
    `1.0.0-1.99.99`, see :ref:`important-ace-254-academic-persons`. Composer
    installs it on its own. A classic installation installs
    :guilabel:`environment_state_manager` before it activates
    :guilabel:`academic_base` 2.4 or this extension.
*   :composer:`fgtclb/academic-base` 2.4, the same release as this extension.

The extensions that require this one follow it in the same deployment, where
installed: :guilabel:`academic_persons_edit`,
:guilabel:`academic_contacts4pages` and :guilabel:`academic_persons_sync`.

..  _upgrade-from-2-3-to-2-4-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~2.4.0' \
        'fgtclb/academic-persons':'~2.4.0'

A project that requires branch ``2`` as ``2.x-dev`` keeps an inline alias such
as ``2.x-dev as 2.4.x-dev`` working, see :ref:`important-1790796666`.

On a classic installation, install :guilabel:`environment_state_manager` 1.x
from the TER in :guilabel:`Admin Tools > Extensions` first, because neither
:guilabel:`academic_base` 2.4 nor this extension can be activated without it.
Then update :guilabel:`academic_base`, :guilabel:`academic_persons` and the
extensions that require it there, in this order. Run the commands of this page
with :bash:`typo3/sysext/core/bin/typo3` instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-2-4-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

In the backend the same is :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`. Version 2.4 adds one column to the contract table,
:sql:`tx_academicpersons_domain_model_contract.organisational_unit_sorting`.
Nothing is changed or dropped. The upgrade wizard of the next step fills the
new column.

..  _upgrade-from-2-3-to-2-4-wizards:

Run the upgrade wizards
=======================

..  code-block:: bash

    vendor/bin/typo3 upgrade:run academicPersons_seedContractOrganisationalUnitSorting

The wizard :guilabel:`Seed the contract sort order of academic organisational
units`, ``academicPersons_seedContractOrganisationalUnitSorting``, is new in
2.4. A contract is an inline child of its profile and of its organisational
unit, and both relations wrote the same :sql:`sorting` column until 2.3, so
saving an organisational unit rearranged the contracts of every profile that
owns one of them. From 2.4 on the unit relation has a column of its own, which
the previous step adds and this wizard fills with the order the unit forms show
today. See :ref:`important-organisational-unit-sorts-its-contracts`.

Run it after the database update, the wizard requires an up to date schema.
Without it every contract carries :sql:`0` in the new column, so an
organisational unit form lists its contracts in whatever order the database
returns until an editor saves that unit once. Profiles keep ordering their
contracts by :sql:`sorting`, so the frontend is not affected either way.

Running the wizard again is harmless. It appends what has no position yet and
never renumbers what has one. Once TYPO3 has recorded it as done, it is offered
again only after it is marked undone. TYPO3 13 marks a wizard undone with
``vendor/bin/typo3 upgrade:mark:undone academicPersons_seedContractOrganisationalUnitSorting``.
TYPO3 12 has no such command: open
:guilabel:`Admin Tools > Upgrade > Upgrade Wizard`, find the wizard under
:guilabel:`Wizards marked as done` and choose :guilabel:`Mark undone`.

:guilabel:`academic_partners` and :guilabel:`academic_contacts4pages` ship the
same repair for their own tables, as
``academicPartners_seedPartnershipRoleSorting`` and
``academicContact4pages_seedContactSecondarySorting``. Run them in this step
where those extensions are installed.

The other wizards of this extension,
``academicPersons_MigrateListTypeToCTypeContentElements`` and
``academicPersons_MigrateProfileAutoCreateExtensionsConfiguration``, shipped
with 2.3 already. An installation that ran them there has nothing more to do.

..  _upgrade-from-2-3-to-2-4-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-3-to-2-4-page-tsconfig:

Make the content elements selectable
------------------------------------

The site sets and static templates were cut per content element, and the six
content elements are now hidden for the whole installation until the page
TSconfig of their component brings them back. See
:ref:`breaking-site-sets-and-static-templates-restructured` for the complete
change.

*   **TYPO3 12, and TYPO3 13 without a site set:** add
    :guilabel:`Academic Persons: All components (academic_persons)` in the page
    record of every site root, tab :guilabel:`Resources`, field
    :guilabel:`Include static Page TSconfig (from extensions)`, or the entries
    of the single content elements a site should offer, see
    :ref:`static-pagetsconfig`. This entry did not
    exist in 2.3. Without it the content elements are no longer offered in the
    backend.
*   **TYPO3 13 with a site set:** nothing to do. The sets
    `fgtclb/academic-persons-default` and `fgtclb/academic-persons-standalone`
    keep their names and deliver the page TSconfig of all six content elements.
    A site may name the new aggregate `fgtclb/academic-persons` or single
    component sets instead.

Use one mechanism per site, never a site set and a static template together,
see :ref:`one-mechanism-per-site`.

Existing content elements keep rendering in the frontend either way. The
breaking change entry warns that saving such a content element in the backend,
on a page that does not include the page TSconfig of its component, writes
another content type. That no longer happens in 2.4.0:
:guilabel:`academic_base` 2.4 keeps the stored type selected and labels it
:guilabel:`(not enabled on this page)`, so saving the record keeps it. A content
element whose type was already replaced before the update, for example on a
page that restricted the content types with
:typoscript:`TCEFORM.tt_content.CType.keepItems`, is not repaired. Its record
history shows the former type.

The :guilabel:`Profile Card` now offers the field :guilabel:`Detail Page`,
which the page TSconfig of 2.3 disabled. To keep it hidden, see
:ref:`important-ace-833-academic-persons`.

..  _upgrade-from-2-3-to-2-4-static-templates:

Static templates and imports
----------------------------

A :sql:`sys_template` record keeps working without a change. Both values its
field :guilabel:`Include TypoScript sets` on the tab :guilabel:`Advanced
Options` can store, `EXT:academic_persons/Configuration/TypoScript/Default` and
`EXT:academic_persons/Configuration/TypoScript/Standalone`, still resolve.
Only their labels changed, to :guilabel:`Academic Persons: Shared plugin
settings` and :guilabel:`Academic Persons: Standalone page`.

An :typoscript:`@import` of a shipped file in a site package has to be checked.
These files were removed:

*   :file:`Configuration/TypoScript/constants.typoscript` and
    :file:`Configuration/TypoScript/setup.typoscript`, the deprecated forwards
    to :file:`Configuration/TypoScript/Default/`,
*   :file:`Configuration/TypoScript/Standalone/constants.typoscript` and
    :file:`Configuration/TypoScript/Standalone/setup.typoscript`,
*   :file:`Configuration/TSconfig/page.tsconfig`, now
    :file:`Configuration/TSconfig/Full/page.tsconfig`,
*   the files below :file:`Configuration/Sets/AcademicPersonsDefault/` and
    :file:`Configuration/Sets/AcademicPersonsStandalone/`.

An :typoscript:`@import` of a missing file fails silently and shows up as
missing configuration. The breaking change entry linked above has the table of
old and new paths.

..  _upgrade-from-2-3-to-2-4-grouping-and-sorting:

Grouping and sorting of the profile list
----------------------------------------

The defaults of :typoscript:`plugin.tx_academicpersons.demand.groupBy` and
:typoscript:`plugin.tx_academicpersons.demand.sortBy` are now the same with and
without a site set, `lastNameAlpha` and `lastName`.

*   A site without a site set, which on TYPO3 12 is every site, now groups a
    profile list by the first letter of the last name unless it sets
    :typoscript:`demand.groupBy` itself. Set the constant to an empty value to
    keep an ungrouped list.
*   A site on a site set now sorts a profile list by last name unless it sets
    :typoscript:`demand.sortBy` itself. The value `title` the sets shipped up
    to 2.3 was rejected by the extension, so these lists had no ordering at
    all.

..  _upgrade-from-2-3-to-2-4-settings-file:

The settings file
-----------------

A site package that ships its own
:file:`Configuration/AcademicPersons/Settings.yaml` with a :yaml:`validations`
map replaces the shipped map as a whole. Remove the :yaml:`number` flag from
:yaml:`zip` and :yaml:`streetNumber` of :yaml:`physicalAddress` there, as the
shipped file does now, so that postcodes and street numbers are stored as
entered. Flush all caches afterwards. See
:ref:`important-ace-841-academic-persons`.

..  _upgrade-from-2-3-to-2-4-extension-configuration:

Extension configuration
-----------------------

Two options are new, ``profile.feuser.telephoneNumberType`` and
``profile.feuser.faxNumberType``. They decide which phone number type
the telephone and fax numbers of a frontend user are imported as, and default
to `business`. The next synchronization run writes `business` where an
imported record still carries the type `phone` or `fax` and the installation
does not offer it. Set the options in :guilabel:`Admin Tools > Settings >
Extension Configuration` to type the imported numbers differently. See
:ref:`feature-configurable-frontend-user-phone-number-types`.

Check the lists ``demand.allowedSortByValues``,
``demand.allowedGroupByValues``, ``types.emailAddressTypes``,
``types.phoneNumberTypes`` and ``types.physicalAddressTypes`` for an entry
without a value, such as `=Private`. Such an entry is
ignored now, and a record stored with it shows its type as an invalid value in
the backend until it is saved with another one. See
:ref:`important-ace-438-academic-persons`.

..  _upgrade-from-2-3-to-2-4-routing:

Routing
-------

A site configuration that imports more than one of the route enhancer files
:file:`List.yaml`, :file:`ListAndDetail.yaml` and :file:`Detail.yaml` has to
limit each enhancer to the pages that carry its plugin with
:yaml:`limitToPages`. Otherwise the enhancer imported first takes the URLs of
the others, and the detail page answers ``404``. See
:ref:`important-route-enhancers-limit-to-pages` and
:ref:`configuration-route-enhancers`.

..  _upgrade-from-2-3-to-2-4-labels:

Labels
------

*   A frontend label override under
    :typoscript:`plugin.tx_academic_persons._LOCAL_LANG` no longer has an
    effect. Move it to :typoscript:`plugin.tx_academicpersons._LOCAL_LANG`, or
    to the path of the one plugin it is meant for. See
    :ref:`important-label-overrides-use-the-documented-path` and
    :ref:`configuration-labels`.
*   The backend labels of the relation between frontend users and profiles
    moved from :guilabel:`academic_persons_edit` into this extension, to
    :file:`EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf`.
    An override through
    :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` has to
    target the new file. The tab of the profile form is keyed
    :xml:`tx_academicpersons_domain_model_profile.div.frontend_users.label`
    there, no longer
    :xml:`tx_academicpersons_domain_model_profile.tabs.frontend_users.label`.
    See :ref:`important-frontend-user-labels-moved`.
*   A further translation of :file:`locallang.xlf` that copied the unit
    :xml:`contracts.emailAdresses` renames it to
    :xml:`contracts.emailAddresses`. See
    :ref:`important-contract-email-label-unit-id`.

..  _upgrade-from-2-3-to-2-4-profile-synchronization:

Profile synchronization
-----------------------

:bash:`academic:createprofiles` and :bash:`academic:updateprofiles` now process
hidden profiles and disabled frontend users as well, and ignore the start and
end time of profiles and frontend users and the frontend user groups of
profiles. They update the data of such a profile and leave its visibility
alone. A profile that was
kept out of the synchronization by hiding it, or by disabling its frontend
user, is overwritten by the next run.

Before the first run, set :guilabel:`Disable profile sync` (:sql:`skip_sync`)
on every profile that must not be synchronized. See
:ref:`breaking-1782285582` and :ref:`important-ace-667-academic-persons`.

:bash:`academic:createprofiles` now also creates a profile for a disabled
frontend user, and for one outside its start and end time, that has none yet.
:guilabel:`Disable profile sync` cannot prevent that, because it is a field of
the profile that does not exist yet. Exclude the page of such frontend users
with :bash:`--exclude-pids`, or delete the profiles created for them.

Both commands now refuse a value of :bash:`--include-pids` or
:bash:`--exclude-pids` with a part that is not a page uid, and exit with code
``2`` instead of running with another list. Check the arguments of scheduler
tasks and scripts. See :ref:`important-ace-845-academic-persons`.

:bash:`academic:updateprofiles` now stores new records next to their profile or
contract instead of on the page of the frontend user. Records an earlier run
stored there stay where they are. Moving them is optional,
:ref:`important-ace-843-academic-persons` has the queries that find them.

..  _upgrade-from-2-3-to-2-4-templates:

Adapt templates and assets
==========================

An override keeps its own copy and does not receive a change of the shipped
file. Seven files changed. None was renamed, moved or removed, and no asset
changed. All of them except :file:`Partials/Profile/Item.html` translate their
labels with the extension name :html:`AcademicPersons` now instead of
:html:`academic_persons`, which moves the path of a label override, see
:ref:`upgrade-from-2-3-to-2-4-labels`. The other changes follow. The paths
below are relative to :file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Partials/Profile/Contract/Field.html`
        -   Renders the organisational unit and the title of the location, and
            a :html:`tel:` target without spaces.
        -   :ref:`important-contract-fields-render-the-unit-and-tel-targets`
    *   -   :file:`Templates/Profile/Detail.html`
        -   The same :html:`tel:` target, and a name without empty parts.
        -   :ref:`important-ace-853-academic-persons`
    *   -   :file:`Partials/Profile/Item.html`
        -   The name without empty parts.
        -   :ref:`important-ace-853-academic-persons`
    *   -   :file:`Partials/Profile/List/ItemList.html`,
            :file:`Templates/Profile/Card.html`,
            :file:`Templates/Profile/SelectedContracts.html`,
            :file:`Templates/Profile/SelectedProfiles.html`
        -   Nothing else.
        -   :ref:`important-label-overrides-use-the-documented-path`

Compare every override with the shipped file of 2.4 and take the changes over.
:ref:`templates-override` describes how the overrides are set up.

..  _upgrade-from-2-3-to-2-4-code:

Adapt your code
===============

*   The two :php:`itemsProcFunc` classes moved to the namespace
    :php:`\FGTCLB\AcademicPersons\Backend\FormEngine`, as :php:`ContractItems`
    and :php:`ProfileShowFieldsItems`, and the method of the latter is
    :php:`itemsProcFunc()` now. Own TCA or FlexForm configuration, and a class
    that extends one of them, uses the new names. See
    :ref:`breaking-1783613367`. A project that only changed the items can
    listen to :php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent`
    instead, see :ref:`feature-1783613368`.
*   A project that extends or replaces :php:`ProfileDemand`,
    :php:`ProfileRepository`, :php:`ContractRepository` or
    :php:`ProfileController` takes the new optional parameters and the new
    demand flag into account. See :ref:`important-ace-250-academic-persons`.
    Narrowing what a plugin shows no longer needs such a subclass, see
    :ref:`feature-profile-and-contract-query-events`.
*   A project that extends or replaces the models :php:`Address`, :php:`Email`
    or :php:`PhoneNumber` adds the :php:`hidden` property with its getter and
    setter. See :ref:`feature-1782285578`.
*   Code outside this extension that looks up imported telephone records by
    the import identifier ``phone:fe_users:<uid>`` uses
    ``telephone:fe_users:<uid>`` now. See
    :ref:`important-imported-telephone-records-use-a-stable-identifier`.

..  _upgrade-from-2-3-to-2-4-check:

Check the result
================

#.  The new content element wizard offers the content elements on the pages of
    every site that should have them, and an existing persons content element
    opens in the backend with its own type.
#.  A profile list groups and sorts its profiles as intended.
#.  An organisational unit form lists its contracts in the order it showed
    before the update.
#.  The detail page is hidden in menus. A request of it without a profile now
    ends with the "page not found" handling of the site, see
    :ref:`important-profile-detail-answers-not-found-without-a-profile`.
#.  Where organisational units are translated, no contract shows twice on a
    profile. Contracts that an earlier localization of a unit created stay.
    Delete them in the backend,
    :ref:`important-localizing-an-organisational-unit-keeps-its-contracts`
    has the query that lists them.
#.  Where :guilabel:`academic_persons_edit` synchronizes profiles into
    translations, check the profiles :bash:`academic:createprofiles` created
    before the update, and translate the ones without a translation in the
    backend. See :ref:`important-ace-610-academic-persons`.
#.  A run of :bash:`academic:updateprofiles` exits with code ``0`` and reports
    the number of updated frontend users, and a profile with
    :guilabel:`Disable profile sync` set stays unchanged.
