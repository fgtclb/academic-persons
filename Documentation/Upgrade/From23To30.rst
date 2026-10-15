..  _upgrade-from-2-3-to-3-0:

=========================
Upgrading from 2.3 to 3.0
=========================

This page takes an installation of :guilabel:`academic_persons` 2.3 straight to
3.0, without the step to 2.4. Version 3.0 drops TYPO3 12, changes the shape of
the settings file and rebuilds the public detail view. From 2.4 it takes over
the content elements that stay hidden until a site includes the page TSconfig
of their components. The page leaves out the 2.4 states that 3.0 replaces:
version 1.x of :guilabel:`environment_state_manager`, see
:ref:`important-ace-254-academic-persons`, and the settings file in the shape
of 2.4, see :ref:`feature-1790531591` and
:ref:`important-ace-841-academic-persons`, which the page migrates once, to the
shape of 3.0. Work through the sections in order. Skip a step that does not
apply to the installation.

..  _upgrade-from-2-3-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_persons` 3.0 needs these packages. Upgrade them first or in
the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, new since 2.4, which this
    extension and :guilabel:`academic_base` 3.0 require. It is declared in
    :file:`composer.json` as `^2.0.1` and in :file:`ext_emconf.php` as
    `2.0.1-2.99.99`. Composer installs it on its own. A classic installation
    installs :guilabel:`environment_state_manager` 2.x before it activates
    :guilabel:`academic_base` 3.0 or this extension.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.

The extensions that require this one follow it in the same deployment, where
installed: :guilabel:`academic_persons_edit`,
:guilabel:`academic_contacts4pages` and :guilabel:`academic_persons_sync`.
Where :guilabel:`academic_persons_edit` is installed, its upgrade chapter is
due in the same deployment, because its editor is replaced and one of its
wizards repairs the profile images of this extension.

These changes have to ship in the same deployment as the update:

*   The contract rows the database update cannot convert are corrected first,
    see :ref:`upgrade-from-2-3-to-3-0-database`.
*   A site without a site set includes the page TSconfig of the components, or
    the content elements are no longer offered, see
    :ref:`upgrade-from-2-3-to-3-0-page-tsconfig`.
*   Project code that extends a class, implements an interface or overrides a
    method of this extension whose signature changed follows it, or it fails
    with a fatal error, see :ref:`upgrade-from-2-3-to-3-0-code`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-3-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.3, which supports
TYPO3 13.4. Version 3.0 no longer installs on TYPO3 12, and Composer and the
Extension Manager refuse it next to an older patch release of TYPO3 13.

Stay on TYPO3 13 until the upgrade is complete. The content element wizard
``academicPersons_MigrateListTypeToCTypeContentElements`` of this extension
reads the column :sql:`tt_content.list_type`, which TYPO3 14 removes, so it
only runs on TYPO3 13. The same holds for the content element wizards of
:guilabel:`academic_jobs`, :guilabel:`academic_persons_edit` and
:guilabel:`academic_projects` when the installation also has them. Move to
TYPO3 14.3.7 or later once they have run.

See :ref:`breaking-removed-typo3-v12-support` and
:ref:`important-minimum-typo3-versions`.

..  _upgrade-from-2-3-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/academic-persons':'~3.0.0'

On a classic installation, install :guilabel:`environment_state_manager` 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, because neither
:guilabel:`academic_base` 3.0 nor this extension can be activated without it.
Then update :guilabel:`academic_base`, :guilabel:`academic_persons` and the
extensions that require it there, in this order. Run the commands of this page
with :bash:`typo3/sysext/core/bin/typo3` instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-3-0-database:

Update the database
===================

Three of the changes cannot be applied to every database as it is. Check them
before the update:

*   The contract columns :sql:`organisational_unit`, :sql:`function_type` and
    :sql:`location` become :sql:`NOT NULL`. The change fails on PostgreSQL and
    on MySQL in strict mode while a row holds :sql:`NULL`, which a contract
    saved with an empty relation by 2.3 does. Update those rows to :sql:`0`
    first, :ref:`important-1788195400` has the statements.
*   The year columns of the timeline entries become unsigned. Correct a
    negative year first, which nothing of the extension ever wrote. See
    :ref:`important-profile-information-year-range`.
*   The contract column :sql:`publish` is removed. Do not accept its removal
    yet, :ref:`upgrade-from-2-3-to-3-0-publish` decides whether its values are
    carried over first.

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

Versions 2.4 and 3.0 change these tables, and the comparison also applies the
changes of the index definitions TYPO3 13.4.35 and 14.3.7 bring:

*   The contract table gets the column
    :sql:`organisational_unit_sorting`, which an upgrade wizard of the next
    step fills.
*   The nine record tables of this extension are workspace aware now and get
    the columns :sql:`t3ver_oid`, :sql:`t3ver_wsid`, :sql:`t3ver_state` and
    :sql:`t3ver_stage` with an index. Until they exist, the backend record
    lists of the extension and the frontend rendering of a translated profile
    fail with a database error. On SQLite, check afterwards that the nine
    tables really carry the four columns, the command line path of TYPO3 adds
    only the index there. See
    :ref:`breaking-person-records-are-workspace-aware`.
*   :sql:`tx_academicpersons_domain_model_function_type` and
    :sql:`tx_academicpersons_domain_model_organisational_unit` get a
    :sql:`slug` column, which an upgrade wizard of the next step fills.
*   The three contract relation columns and the three year columns change as
    described above.
*   :sql:`tx_academicpersons_domain_model_address` gets an index on
    :sql:`import_identifier`, see :ref:`feature-1790866812`.

The analyzer offers :sql:`tx_academicpersons_domain_model_contract.publish`
for removal. It never drops a column on its own.

..  _upgrade-from-2-3-to-3-0-wizards:

Run the upgrade wizards
=======================

Run the wizards in this order, after the database update, and on TYPO3 13:

#.  ``academicPersons_MigrateListTypeToCTypeContentElements``,
    :guilabel:`Migrate "EXT:academic_persons" from list elements to content
    elements (list_type => CType)`, only if it is still offered.
#.  ``academicPersons_seedContractOrganisationalUnitSorting``,
    :guilabel:`Seed the contract sort order of academic organisational units`.
#.  ``academicPersons_fillFilterSlugs``, :guilabel:`Generate the URL segments
    of academic function types and organisational units`.
#.  ``academicPersonsEdit_repairLocalizedProfileImages``,
    :guilabel:`Repair the profile image relations of academic profiles`, of
    :guilabel:`academic_persons_edit`, where that extension is installed.

..  code-block:: bash

    vendor/bin/typo3 upgrade:run academicPersons_MigrateListTypeToCTypeContentElements
    vendor/bin/typo3 upgrade:run academicPersons_seedContractOrganisationalUnitSorting
    vendor/bin/typo3 upgrade:run academicPersons_fillFilterSlugs

A wizard that TYPO3 recorded as done is offered again once it is marked
undone. TYPO3 13 marks a wizard undone with
``vendor/bin/typo3 upgrade:mark:undone <identifier>``.
The two repeatable wizards of this step are never recorded as done.

``academicPersons_MigrateProfileAutoCreateExtensionsConfiguration`` shipped
with 2.3 already. An installation that ran it there has nothing more to do.

..  _upgrade-from-2-3-to-3-0-ctype:

The content element wizard
--------------------------

``academicPersons_MigrateListTypeToCTypeContentElements`` shipped with 2.x
already and moves content elements stored as ``list`` with a
:sql:`list_type` of this extension onto their own :sql:`CType`. An
installation that ran it there has nothing more to do. TYPO3 14 no longer has
:sql:`tt_content.list_type`, so the wizard has to run on TYPO3 13, before the
core update. Run it by its identifier and not with a bare
:bash:`vendor/bin/typo3 upgrade:run`: the wizard is not repeatable, and a run
on TYPO3 14 that finds nothing records it as done although it migrated
nothing. Replace own TypoScript, TSconfig or SQL that matches on
:sql:`list_type` with a condition on :sql:`CType`. See
:ref:`breaking-plugins-require-ctype-on-typo3-v14`.

..  _upgrade-from-2-3-to-3-0-sorting:

The sort order of the organisational unit contracts
---------------------------------------------------

A contract is an inline child of its profile and of its organisational unit, and
both relations wrote the same :sql:`sorting` column in 2.3, so saving an
organisational unit rearranged the contracts of every profile that owns one of
them. The unit relation has a column of its own now, which the database update
adds and the wizard :guilabel:`Seed the contract sort order of academic
organisational units`,
``academicPersons_seedContractOrganisationalUnitSorting``, fills with the order
the unit forms show today. Without it every contract carries :sql:`0` in the new
column until an editor saves its unit. Profiles keep ordering their contracts by
:sql:`sorting`, so the frontend is not affected either way. See
:ref:`important-organisational-unit-sorts-its-contracts`.

:guilabel:`academic_partners` and :guilabel:`academic_contacts4pages` ship the
same repair for their own tables, as
``academicPartners_seedPartnershipRoleSorting`` and
``academicContact4pages_seedContactSecondarySorting``. Run them in this step
where those extensions are installed.

..  _upgrade-from-2-3-to-3-0-filter-slugs:

The URL segments of the filter records
--------------------------------------

The wizard :guilabel:`Generate the URL segments of academic function types and
organisational units`, ``academicPersons_fillFilterSlugs``, is new in 3.0. It
gives every live function type and organisational unit without a slug the one
a backend save would generate from its name, and leaves a slug that is set
alone. The filters of the profile list read the slug, so a list filtered by a
function type reads :file:`/persons/function/professor`, see
:ref:`configuration-route-enhancers-filters`. Without it a filter keeps
working as a query argument. See :ref:`feature-1791043401`.

The wizard is repeatable. It is offered again whenever a record has no slug,
for example after an import of a project wrote one without it.

..  _upgrade-from-2-3-to-3-0-profile-images:

The profile images of translations
----------------------------------

The profile image is translatable now. A translation follows the image of the
default language until it gets an image of its own. The image relations that
:guilabel:`academic_persons_edit` wrote before 3.0 do not always fit that, and
its repeatable wizard ``academicPersonsEdit_repairLocalizedProfileImages``
repairs them through the DataHandler, without deleting a file. Run it where
that extension is installed and profiles have images, before editors use the
new editor of a multilingual site. Its upgrade chapter describes the wizard.
See :ref:`breaking-profile-image-is-translatable`.

Code that read the image of a translation through the record of the default
language resolves the reference of the translation now.

..  _upgrade-from-2-3-to-3-0-publish:

The contract field "publish"
----------------------------

The contract toggle :guilabel:`Show this contract online?` is removed with its
column :sql:`publish`. No view of the extension has ever read it, so the
visibility of every contract stays as it is. See
:ref:`breaking-contract-publish-field-removed`.

Only a project whose own code gave the flag a meaning, and left contracts out
that were not published, carries it over to the visibility of the contract.
The wizard ``academicPersons_migrateContractPublishToHidden`` does that, and it
ships unregistered, because on every other installation it would hide all
contracts. Register it in the :file:`Configuration/Services.yaml` of the site
package, flush the caches, run it, flush the frontend caches, and remove the
registration again. :ref:`important-contract-publish-to-hidden-wizard` has the
registration and the order.

Then let the database analyzer drop the column. Once it is dropped, nothing can
be migrated any more.

..  _upgrade-from-2-3-to-3-0-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-3-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. For this extension it reports a TypoScript
record or an import of a file that is gone, two fields of the settings that
write one column, and a site that still depends on the set
`fgtclb/academic-persons-default` or `fgtclb/academic-persons-standalone`,
which keep working as aliases of `fgtclb/academic-persons`. Run it on the
staging copy right after the package update, and again once the steps of this
section are done:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also finds the template overrides of the
site whose file is gone, and copies that are identical to the shipped file:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_persons --site=<site identifier>

Each finding, and what to do about it, is described in the `upgrade check
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/UpgradeCheck/Index.html>`__
of :guilabel:`academic_base`.

..  _upgrade-from-2-3-to-3-0-page-tsconfig:

Make the content elements selectable
------------------------------------

The site sets and static templates are cut per content element since 2.4, and
the six content elements are hidden for the whole installation until the page
TSconfig of their component brings them back. See
:ref:`breaking-site-sets-and-static-templates-restructured` for the complete
change.

*   **Without a site set:** add
    :guilabel:`Academic Persons: All components (academic_persons)` in the page
    record of every site root, tab :guilabel:`Resources`, field
    :guilabel:`Include static Page TSconfig (from extensions)`, or the entries
    of the single content elements a site should offer, see
    :ref:`static-pagetsconfig`. This entry did not exist in 2.3. Without it the
    content elements are no longer offered in the backend.
*   **With a site set:** nothing to do. The sets
    `fgtclb/academic-persons-default` and `fgtclb/academic-persons-standalone`
    keep their names and deliver the page TSconfig of all six content elements.
    A site may name the aggregate `fgtclb/academic-persons` or single
    component sets instead.

Use one mechanism per site, never a site set and a static template together,
see :ref:`one-mechanism-per-site`.

Until a component is included, an existing content element of it keeps
rendering and keeps its type: :guilabel:`academic_base` shows the stored type
in the field :guilabel:`Type` followed by
:guilabel:`(not enabled on this page)`, and saving keeps it. The warning of the
2.4 changelog entry that saving such a record rewrites its type describes the
state before a fix that :guilabel:`academic_base` 2.4 carries as well, see
`its changelog entry <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/2.4/Important-HiddenContentTypeStaysSelectable.html>`__.
A content element that a page TSconfig of your own already turned into another
type is not repaired. Correct it by hand from its record history.

The :guilabel:`Person Contact Card` offers the field :guilabel:`Detail Page`,
which the page TSconfig of 2.3 disabled. To keep it hidden, see
:ref:`important-ace-833-academic-persons`.

..  _upgrade-from-2-3-to-3-0-static-templates:

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

..  _upgrade-from-2-3-to-3-0-grouping-and-sorting:

Grouping and sorting of the profile list
----------------------------------------

The defaults of :typoscript:`plugin.tx_academicpersons.demand.groupBy` and
:typoscript:`plugin.tx_academicpersons.demand.sortBy` are the same with and
without a site set now, `lastNameAlpha` and `lastName`.

*   A site without a site set now groups a profile list by the first letter of
    the last name unless it sets :typoscript:`demand.groupBy` itself. Set the
    constant to an empty value to keep an ungrouped list.
*   A site on a site set now sorts a profile list by last name unless it sets
    :typoscript:`demand.sortBy` itself. The value `title` the sets shipped up
    to 2.3 was rejected by the extension, so these lists had no ordering at
    all.

..  _upgrade-from-2-3-to-3-0-settings:

The settings file
-----------------

A site package that ships its own
:file:`Configuration/AcademicPersons/Settings.yaml` has to be migrated. The
file is built from four maps now, :yaml:`profile`, :yaml:`special`,
:yaml:`contracts` and :yaml:`documentSections`, and the top-level keys
:yaml:`validations` and :yaml:`profileInformationsTypes` of 2.3 are gone. See
:ref:`breaking-section-based-academic-persons-settings`.

A file that still ships the old keys keeps working for now. They are mapped
onto the new maps at runtime, with a warning in the log, until version 4.0.
The migration command prints the maps that replace them:

..  code-block:: bash

    vendor/bin/typo3 academic:persons:settings:migrate

It never writes the file, and it exits with ``1`` while a package ships an old
key. Paste the printed maps into the site package in place of the old keys,
review them and flush all caches. Read the notes the command prints: a changed
:yaml:`type` or :yaml:`fieldName` of a timeline type is not applied, a
timeline type of a project needs its own column in a TCA override of the
profile table. See :ref:`feature-legacy-settings-overlay-and-migration-command`
and :ref:`configuration-validations-migration`.

The settings files are merged recursively now. A key the override leaves out
is inherited from :guilabel:`academic_persons`, at every level, where leaving
it out removed it before. An entry that is to be gone is set to :yaml:`~`, a
flag list that is to be empty to :yaml:`[]`. Compare the override with the
shipped file key by key, ``--delta`` prints the keys a copied map leaves out:

..  code-block:: bash

    vendor/bin/typo3 academic:persons:settings:migrate --delta

See :ref:`breaking-settings-files-merge-recursively` and
:ref:`feature-settings-override-report-and-delta`.

The shipped defaults changed as well: the gender of a profile and the start
date of a contract are required, the website, the publications link and the
links of the timeline entries carry the :yaml:`url` flag, the type of an email
address or a phone number is no longer required, and the postcode and the
street number of an address lose the :yaml:`number` flag, so they are stored
as entered. Change them in the override where the installation wants the old
behaviour, :ref:`configuration-sections` documents every key.

..  _upgrade-from-2-3-to-3-0-tca-overrides:

TCA overrides of the person tables
----------------------------------

The settings set ``required`` and ``readOnly`` of a column after every TCA
override of a site package now, not before. A site package that sets either of
them on a configured column in :file:`Configuration/TCA/Overrides` loses that
value. Move the lock or the requirement into the settings file. See
:ref:`breaking-settings-apply-after-tca-overrides`.

A site package that names a FlexForm data structure of this extension by path
checks the path. :file:`Core12/` is gone, :file:`SelectedProfiles.xml` and
:file:`SelectedContracts.xml` are directly in
:file:`Configuration/FlexForms/` now, :file:`List.xml` and :file:`Detail.xml`
have a :file:`Core13/` and a :file:`Core14/` variant. See
:ref:`important-plugin-flexforms-are-split-per-core-version`.

..  _upgrade-from-2-3-to-3-0-extension-configuration:

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
without a value, such as `=Private`. Such an entry is ignored now, and a record
stored with it shows its type as an invalid value in the backend until it is
saved with another one. See :ref:`important-ace-438-academic-persons`.

..  _upgrade-from-2-3-to-3-0-routing:

Routing
-------

A site configuration that imports more than one of the route enhancer files
:file:`List.yaml`, :file:`ListAndDetail.yaml` and :file:`Detail.yaml` has to
limit each enhancer to the pages that carry its plugin with
:yaml:`limitToPages`. Otherwise the enhancer imported first takes the URLs of
the others, and the detail page answers ``404``. See
:ref:`important-route-enhancers-limit-to-pages` and
:ref:`configuration-route-enhancers`.

..  _upgrade-from-2-3-to-3-0-labels:

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
    there. See :ref:`important-frontend-user-labels-moved`.
*   A further translation of :file:`locallang.xlf` renames the unit
    :xml:`contracts.emailAdresses` to :xml:`contracts.emailAddresses`, see
    :ref:`important-contract-email-label-unit-id`, and takes over the units
    the detail view adds, :xml:`detail.subline`, :xml:`detail.contact` and
    :xml:`detail.navigation`, see :ref:`important-public-profile-label-changes`.

..  _upgrade-from-2-3-to-3-0-profile-synchronization:

Profile synchronization
-----------------------

:bash:`academic:createprofiles` and :bash:`academic:updateprofiles` process
hidden profiles and disabled frontend users as well now, and ignore the start
and end time of profiles and frontend users and the frontend user groups of
profiles. They update the data of such a profile and leave its visibility
alone. A profile that was kept out of the synchronization by hiding it, or by
disabling its frontend user, is overwritten by the next run.

Before the first run, set :guilabel:`Disable profile sync` (:sql:`skip_sync`)
on every profile that must not be synchronized. See
:ref:`breaking-1782285582` and :ref:`important-ace-667-academic-persons`.

:bash:`academic:createprofiles` also creates a profile for a disabled frontend
user, and for one outside its start and end time, that has none yet. Exclude
the page of such frontend users with :bash:`--exclude-pids`, or delete the
profiles created for them.

Both commands refuse a value of :bash:`--include-pids` or
:bash:`--exclude-pids` with a part that is not a page uid, and exit with code
``2``. Check the arguments of scheduler tasks and scripts. See
:ref:`important-ace-845-academic-persons`.

:bash:`academic:updateprofiles` stores new records next to their profile or
contract instead of on the page of the frontend user. Moving the records an
earlier run stored there is optional, :ref:`important-ace-843-academic-persons`
has the queries that find them.

..  _upgrade-from-2-3-to-3-0-templates:

Adapt templates and assets
==========================

An override keeps its own copy and does not receive a change of the shipped
file. The upgrade check with the extension key and a site finds overrides of
files that are gone, and copies that are identical to the shipped file, see
:ref:`upgrade-from-2-3-to-3-0-upgrade-check`. Compare every other override with
the shipped file of 3.0 and take the changes over. :ref:`templates-override`
describes how the overrides are set up. Every template translates its labels
with the extension name :html:`AcademicPersons` now, which moves the path of a
label override, see :ref:`upgrade-from-2-3-to-3-0-labels`. The paths below are
relative to :file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Templates/Profile/Detail.html`
        -   Rewritten. It dispatches the configured elements to the partials
            below :file:`Partials/Profile/PublicProfile/`. An override keeps
            rendering its own copy and loses the configurable layout.
        -   :ref:`feature-configurable-public-profile`
    *   -   :file:`Partials/Profile/DataHeader.html`
        -   Deleted. A template that still renders it fails at render time.
            :file:`Partials/Profile/Header.html` and
            :file:`Partials/Profile/SectionHeader.html` are no longer rendered
            by the detail view.
        -   :ref:`breaking-public-profile-detail-partials`
    *   -   :file:`Partials/Profile/Header.html`,
            :file:`Partials/Profile/SectionHeader.html`
        -   Take the class of the heading as the argument ``class`` in place
            of ``positionClass``.
        -   :ref:`breaking-persons-header-partials-take-a-class-argument`
    *   -   :file:`Partials/Profile/Item.html`,
            :file:`Partials/Profile/List/ItemList.html`
        -   Built from small partials below :file:`Partials/Profile/Item/` and
            :file:`Partials/Profile/List/`. A copy of the two files does not
            reach the small ones, and a copy of the contract partial reads
            ``detailLink`` in place of ``detailUri``. The name leaves out
            empty parts.
        -   :ref:`important-profile-item-and-list-partials`,
            :ref:`important-ace-853-academic-persons`
    *   -   :file:`Partials/Profile/Contract/Field.html`
        -   Renders the organisational unit and the title of the location, a
            :html:`tel:` target without spaces, and the office hours as HTML.
        -   :ref:`important-contract-fields-render-the-unit-and-tel-targets`,
            :ref:`important-contract-field-office-hours-render-as-html`
    *   -   :file:`Partials/Profile/List/AlphabetPagination.html`,
            :file:`Templates/Profile/List.html`
        -   Letters without profiles are disabled, and a list with a manual
            selection renders no letter navigation.
        -   :ref:`important-letter-navigation-markup`,
            :ref:`important-no-letter-navigation-for-a-manual-selection`

..  _upgrade-from-2-3-to-3-0-images:

The profile image
-----------------

The profile card and the detail view render the image as a
:html:`<picture>` through the image partial of :guilabel:`academic_base`, and
a card of a profile without an image shows a placeholder. Adjust CSS that
selected the image as a direct child. Set
:typoscript:`plugin.tx_academicpersons.image.placeholder.default` to an empty
value to keep such cards without an image. A view whose partial root paths are
replaced as a whole adds
:typoscript:`-1 = EXT:academic_base/Resources/Private/Partials/`. See
:ref:`breaking-profile-images-render-as-picture` and
:ref:`templates-profile-image`.

..  _upgrade-from-2-3-to-3-0-classes:

Classes and styles
------------------

The templates carry the speaking ``ace-*`` classes the academic extensions
share, in place of the classes of the Bootstrap card, the list group and the
pagination. Only the grid classes, the button classes and
``visually-hidden`` of Bootstrap stay. Move the selectors of the site
stylesheet to the new classes, the chapter :ref:`Styling <styling>` shows the
markup of every view. See :ref:`breaking-persons-speaking-frontend-classes`.

The extension ships no stylesheet, as in 2.3. Only the development versions
of 3.0 shipped one for the profile views. The detail view needs two rules of
the site to work: one that hides the expand or the collapse glyph of a
fold-out entry by its :html:`aria-expanded` state, and one that makes the
navigation of the left column sticky. The stylesheet of the development
instances is an example, see :ref:`styling-example` and
:ref:`breaking-public-profile-ships-no-stylesheet`.

The detail view loads the module
``@fgtclb/academic-persons/frontend/profile.js``. A site that brings a script
of its own switches it off with
:typoscript:`plugin.tx_academicpersons.assets.js`, see
:ref:`feature-1791566503`.

..  _upgrade-from-2-3-to-3-0-icons:

Icons
-----

Every icon of this extension is renamed, without an alias. The record icons are
``tx-academicpersons-record-*`` now, the content element icon ``persons_icon``
has three successors ``tx-academicpersons-plugin-*``, and the icon files of 2.3
below :file:`Resources/Public/Icons/` are deleted. An old identifier renders
TYPO3's ``default-not-found`` placeholder. Replace the identifiers in templates,
TSconfig, TCA, PHP and CSS of the site package, and move a replacement icon to
the new identifier. See
:ref:`breaking-persons-public-profile-icons-moved-to-the-frontend-icon-registry`.

..  _upgrade-from-2-3-to-3-0-code:

Adapt your code
===============

*   The two :php:`itemsProcFunc` classes moved to the namespace
    :php:`\FGTCLB\AcademicPersons\Backend\FormEngine`, as :php:`ContractItems`
    and :php:`ProfileShowFieldsItems`, and the method of the latter is
    :php:`itemsProcFunc()` now. Own TCA or FlexForm configuration, and a class
    that extends one of them, uses the new names. See
    :ref:`breaking-1783613367`.
*   The list, detail, selected profiles and selected contracts plugins no
    longer dispatch :php:`ModifyListProfilesEvent`,
    :php:`ModifyDetailProfileEvent`, :php:`ModifySelectedProfilesEvent` and
    :php:`ModifySelectedContractsEvent`, and the classes are removed. A
    listener of one of them is never called again, without an error. Search
    the project code for the four class names. A listener that assigns view
    variables moves to :php:`ModifyPluginViewEvent` of
    :guilabel:`academic_base`, a change of the demand to
    :php:`ModifyProfileDemandEvent`. See
    :ref:`breaking-removed-profile-view-events` and
    :ref:`developers-event-modify-plugin-view-event`.
*   A project that extends or replaces :php:`ProfileDemand`,
    :php:`ProfileRepository`, :php:`ContractRepository` or
    :php:`ProfileController` takes the parameters and the demand flag of 2.4
    into account, see :ref:`important-ace-250-academic-persons`. An override of
    :php:`ProfileRepository::findByDemand()` in a subclass or an XCLASS is a
    fatal error until it takes the new trailing context parameter of 3.0. An
    override of either :php:`findByUids()` keeps loading and is no longer
    called by the selected profiles and selected contracts plugins. Move both
    to a listener of the query events. See
    :ref:`breaking-profile-and-contract-finder-signatures` and
    :ref:`developers-query-events`.
*   A class of a project that implements
    :php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface`
    adds :php:`getContentObjectRenderer()`, or is a fatal error. See
    :ref:`breaking-plugin-controller-action-context-interface-extends-base`.
    The interface and its class are deprecated and removed in 4.0. Type new
    code against the interface of :guilabel:`academic_base`, see
    :ref:`deprecation-persons-plugin-controller-action-context`.
*   A project that extends or replaces the models :php:`Address`, :php:`Email`
    or :php:`PhoneNumber` adds the :php:`hidden` property with its getter and
    setter. See :ref:`feature-1782285578`.
*   :php:`SynchronizerContext` moved from the namespace
    :php:`Dto\Syncronizer` to :php:`Dto\Synchronizer`, and its property and
    named argument lost the same misspelling. See
    :ref:`breaking-synchronizer-context-namespace-corrected`.
*   A DataHandler hook of the project that dispatched
    :php:`AfterProfileUpdateEvent` after a backend save is removed. The
    extension announces a backend save itself now, and the hook would announce
    it a second time. See
    :ref:`important-backend-saves-announce-profile-updates`.
*   Code outside this extension that looks up imported telephone records by
    the import identifier ``phone:fe_users:<uid>`` uses
    ``telephone:fe_users:<uid>`` now. See
    :ref:`important-imported-telephone-records-use-a-stable-identifier`.
*   Code that calls :php:`setPublish()`, :php:`isPublish()` or
    :php:`getPublish()` of a contract, or reads or writes :sql:`publish`, uses
    the visibility of the contract, see
    :ref:`upgrade-from-2-3-to-3-0-publish`.
*   A query of the project with its own :php:`QueryBuilder` on one of the nine
    tables sees workspace versions as rows now. Add the workspace restriction
    and the version overlay, as the entry linked in
    :ref:`upgrade-from-2-3-to-3-0-database` shows.
*   Code that uses :php:`Validation`, :php:`ValidationSet` or the
    ``validationEnsure`` ViewHelper of this extension imports them from
    :guilabel:`academic_base`, where they moved without an alias. See
    :ref:`important-validation-primitives-moved-to-academic-base`.

..  _upgrade-from-2-3-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension
    key, see :ref:`upgrade-from-2-3-to-3-0-upgrade-check`.
*   :bash:`vendor/bin/typo3 academic:persons:settings:migrate` exits with
    ``0``, and the log carries no warning about legacy settings.
*   The new content element wizard offers the content elements on the pages of
    every site that should have them, under the titles of 3.0, see
    :ref:`important-content-element-titles-and-descriptions`, and an existing
    persons content element opens in the backend with its own type.
*   The backend record lists of the extension open, and a translated profile
    renders in the frontend.
*   A profile list groups and sorts its profiles as intended, and a list
    filtered by a function type or an organisational unit shows the slug in
    the URL.
*   An organisational unit form lists its contracts in the order it showed
    before the update.
*   The detail page is hidden in menus. A request of it without a profile ends
    with the "page not found" handling of the site, see
    :ref:`important-profile-detail-answers-not-found-without-a-profile`.
*   Where organisational units are translated, no contract shows twice on a
    profile.
    :ref:`important-localizing-an-organisational-unit-keeps-its-contracts`
    has the query that lists the contracts an earlier localization created.
*   A translated profile shows an image in every language, and a timeline
    entry shows its year in the frontend and in the backend form.
*   The detail view folds its entries in and out, and the site stylesheet
    reaches the lists, the card and the detail view.
*   A run of :bash:`academic:updateprofiles` exits with ``0``, and a
    profile with :guilabel:`Disable profile sync` set stays unchanged.
