..  _upgrade-from-2-4-to-3-0:

=========================
Upgrading from 2.4 to 3.0
=========================

Version 3.0 drops TYPO3 12, changes the shape of
:file:`Configuration/AcademicPersons/Settings.yaml`, rebuilds the public
detail view and makes the person records workspace aware. Work through the
sections in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-4-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_persons` 3.0 needs these packages. Upgrade them first or in
the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, in place of 1.x, which
    this extension and :guilabel:`academic_base` 3.0 require. It is declared in
    :file:`composer.json` as `^2.0.1` and in :file:`ext_emconf.php` as
    `2.0.1-2.99.99`. Composer updates it on its own. A classic installation
    updates :guilabel:`environment_state_manager` to 2.x before it activates
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
    see :ref:`upgrade-from-2-4-to-3-0-database`.
*   Project code that extends a class, implements an interface or overrides a
    method of this extension whose signature changed follows it, or it fails
    with a fatal error, see :ref:`upgrade-from-2-4-to-3-0-code`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-4-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.4, which supports
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

..  _upgrade-from-2-4-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/academic-persons':'~3.0.0'

On a classic installation, update :guilabel:`environment_state_manager` to 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, because neither
:guilabel:`academic_base` 3.0 nor this extension can be activated without it.
Then update :guilabel:`academic_base`, :guilabel:`academic_persons` and the
extensions that require it there, in this order. Run the commands of this page
with :bash:`typo3/sysext/core/bin/typo3` instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-4-to-3-0-database:

Update the database
===================

Three of the changes cannot be applied to every database as it is. Check them
before the update:

*   The contract columns :sql:`organisational_unit`, :sql:`function_type` and
    :sql:`location` become :sql:`NOT NULL`. The change fails on PostgreSQL and
    on MySQL in strict mode while a row holds :sql:`NULL`, which rows saved
    before 2.4 or by Extbase on TYPO3 12 do. Update those rows to :sql:`0`
    first, :ref:`important-1788195400` has the statements.
*   The year columns of the timeline entries become unsigned. Correct a
    negative year first, which nothing of the extension ever wrote. See
    :ref:`important-profile-information-year-range`.
*   The contract column :sql:`publish` is removed. Do not accept its removal
    yet, :ref:`upgrade-from-2-4-to-3-0-publish` decides whether its values are
    carried over first.

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

Version 3.0 changes these tables, and the comparison also applies the changes
of the index definitions TYPO3 13.4.35 and 14.3.7 bring:

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

..  _upgrade-from-2-4-to-3-0-wizards:

Run the upgrade wizards
=======================

Run the wizards in this order, after the database update, and on TYPO3 13:

#.  ``academicPersons_MigrateListTypeToCTypeContentElements``,
    :guilabel:`Migrate "EXT:academic_persons" from list elements to content
    elements (list_type => CType)`, only if it is still offered.
#.  ``academicPersons_fillFilterSlugs``, :guilabel:`Generate the URL segments
    of academic function types and organisational units`.
#.  ``academicPersonsEdit_repairLocalizedProfileImages``,
    :guilabel:`Repair the profile image relations of academic profiles`, of
    :guilabel:`academic_persons_edit`, where that extension is installed.

..  code-block:: bash

    vendor/bin/typo3 upgrade:run academicPersons_MigrateListTypeToCTypeContentElements
    vendor/bin/typo3 upgrade:run academicPersons_fillFilterSlugs

A wizard that TYPO3 recorded as done is offered again once it is marked
undone. TYPO3 13 marks a wizard undone with
``vendor/bin/typo3 upgrade:mark:undone <identifier>``.
The two repeatable wizards of this step are never recorded as done.

..  _upgrade-from-2-4-to-3-0-ctype:

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

..  _upgrade-from-2-4-to-3-0-filter-slugs:

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

..  _upgrade-from-2-4-to-3-0-profile-images:

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

..  _upgrade-from-2-4-to-3-0-publish:

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

..  _upgrade-from-2-4-to-3-0-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-4-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. For this extension it reports two fields of
the settings that write one column, and a site that still depends on the set
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

..  _upgrade-from-2-4-to-3-0-settings:

The settings file
-----------------

A site package that ships its own
:file:`Configuration/AcademicPersons/Settings.yaml` has to be migrated. The
file is built from four maps now, :yaml:`profile`, :yaml:`special`,
:yaml:`contracts` and :yaml:`documentSections`, and the top-level keys
:yaml:`validations` and :yaml:`profileInformationsTypes` of 2.4 are gone. See
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
links of the timeline entries carry the :yaml:`url` flag, and the type of an
email address or a phone number is no longer required.
Change them in the override where the installation wants the old behaviour,
:ref:`configuration-sections` documents every key.

A file that still names ``contracts.fields.publish`` logs a warning, remove
the key, see :ref:`upgrade-from-2-4-to-3-0-publish`.

..  _upgrade-from-2-4-to-3-0-tca-overrides:

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

..  _upgrade-from-2-4-to-3-0-labels:

Labels
------

A translation of :file:`locallang.xlf` into a further language takes over the
units the detail view adds, :xml:`detail.subline`, :xml:`detail.contact` and
:xml:`detail.navigation`. :xml:`detail.contracts` and
:xml:`detail.additionalInformation` are no longer rendered by the shipped
templates. See :ref:`important-public-profile-label-changes`.

..  _upgrade-from-2-4-to-3-0-templates:

Adapt templates and assets
==========================

An override keeps its own copy and does not receive a change of the shipped
file. The upgrade check with the extension key and a site finds overrides of
files that are gone, and copies that are identical to the shipped file, see
:ref:`upgrade-from-2-4-to-3-0-upgrade-check`. Compare every other override with
the shipped file of 3.0 and take the changes over. :ref:`templates-override`
describes how the overrides are set up. The paths below are relative to
:file:`Resources/Private/`:

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
            ``detailLink`` in place of ``detailUri``.
        -   :ref:`important-profile-item-and-list-partials`
    *   -   :file:`Partials/Profile/Contract/Field.html`
        -   Renders the office hours as HTML.
        -   :ref:`important-contract-field-office-hours-render-as-html`
    *   -   :file:`Partials/Profile/List/AlphabetPagination.html`,
            :file:`Templates/Profile/List.html`
        -   Letters without profiles are disabled, and a list with a manual
            selection renders no letter navigation.
        -   :ref:`important-letter-navigation-markup`,
            :ref:`important-no-letter-navigation-for-a-manual-selection`

..  _upgrade-from-2-4-to-3-0-images:

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

..  _upgrade-from-2-4-to-3-0-classes:

Classes and styles
------------------

The templates carry the speaking ``ace-*`` classes the academic extensions
share, in place of the classes of the Bootstrap card, the list group and the
pagination. Only the grid classes, the button classes and
``visually-hidden`` of Bootstrap stay. Move the selectors of the site
stylesheet to the new classes, the chapter :ref:`Styling <styling>` shows the
markup of every view. See :ref:`breaking-persons-speaking-frontend-classes`.

The extension ships no stylesheet, as in 2.4. Only the development versions
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

..  _upgrade-from-2-4-to-3-0-icons:

Icons
-----

Every icon of this extension is renamed, without an alias. The record icons are
``tx-academicpersons-record-*`` now, the content element icon ``persons_icon``
has three successors ``tx-academicpersons-plugin-*``, and the icon files of 2.4
below :file:`Resources/Public/Icons/` are deleted. An old identifier renders
TYPO3's ``default-not-found`` placeholder. Replace the identifiers in templates,
TSconfig, TCA, PHP and CSS of the site package, and move a replacement icon to
the new identifier. See
:ref:`breaking-persons-public-profile-icons-moved-to-the-frontend-icon-registry`.

..  _upgrade-from-2-4-to-3-0-code:

Adapt your code
===============

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
*   An override of :php:`ProfileRepository::findByDemand()` in a subclass or
    an XCLASS is a fatal error until it takes the new trailing context
    parameter. An override of either :php:`findByUids()` keeps loading and is
    no longer called by the selected profiles and selected contracts plugins.
    Move both to a listener of the query events. See
    :ref:`breaking-profile-and-contract-finder-signatures` and
    :ref:`developers-query-events`.
*   A class of a project that implements
    :php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface`
    adds :php:`getContentObjectRenderer()`, or is a fatal error. See
    :ref:`breaking-plugin-controller-action-context-interface-extends-base`.
    The interface and its class are deprecated and removed in 4.0. Type new
    code against the interface of :guilabel:`academic_base`, see
    :ref:`deprecation-persons-plugin-controller-action-context`.
*   :php:`SynchronizerContext` moved from the namespace
    :php:`Dto\Syncronizer` to :php:`Dto\Synchronizer`, and its property and
    named argument lost the same misspelling. See
    :ref:`breaking-synchronizer-context-namespace-corrected`.
*   A DataHandler hook of the project that dispatched
    :php:`AfterProfileUpdateEvent` after a backend save is removed. The
    extension announces a backend save itself now, and the hook would announce
    it a second time. See
    :ref:`important-backend-saves-announce-profile-updates`.
*   Code that calls :php:`setPublish()`, :php:`isPublish()` or
    :php:`getPublish()` of a contract, or reads or writes :sql:`publish`, uses
    the visibility of the contract, see
    :ref:`upgrade-from-2-4-to-3-0-publish`.
*   A query of the project with its own :php:`QueryBuilder` on one of the nine
    tables sees workspace versions as rows now. Add the workspace restriction
    and the version overlay, as the entry linked in
    :ref:`upgrade-from-2-4-to-3-0-database` shows.
*   Code that uses :php:`Validation`, :php:`ValidationSet` or the
    ``validationEnsure`` ViewHelper of this extension imports them from
    :guilabel:`academic_base`, where they moved without an alias.
    See :ref:`important-validation-primitives-moved-to-academic-base`.

..  _upgrade-from-2-4-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension
    key, see :ref:`upgrade-from-2-4-to-3-0-upgrade-check`.
*   :bash:`vendor/bin/typo3 academic:persons:settings:migrate` exits with
    ``0``, and the log carries no warning about legacy settings.
*   The new content element wizard offers the content elements under the
    titles of 3.0, see
    :ref:`important-content-element-titles-and-descriptions`.
*   The backend record lists of the extension open, and a translated profile
    renders in the frontend.
*   A timeline entry shows its year in the frontend and in the backend form.
*   A profile list filtered by a function type or an organisational unit shows
    the slug in the URL.
*   A translated profile shows an image in every language.
*   The detail view folds its entries in and out, and the site stylesheet
    reaches the lists, the card and the detail view.
*   A list and a selected profiles content element show what the code of the
    project intends, where that code was a repository subclass, an XCLASS or
    a listener of a removed event.
