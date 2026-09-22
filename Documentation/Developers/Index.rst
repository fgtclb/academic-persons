..  _developers:

==============
For developers
==============

This chapter documents the programmatic surface this extension ships: the two
events that let a project narrow what the plugins show, the translation
synchronisation - the event that triggers it, the service interface behind it,
and how it behaves in workspaces - and the event that lets a project decide
what is written as the metadata of a profile image.

..  warning::

    **The translation synchronisation is experimental.**
    :php:`RecordSynchronizerInterface` and :php:`RecordSynchronizer` are marked
    :php:`@internal`, and the rest of :ref:`developers-synchronisation` and
    :ref:`developers-workspaces` - :php:`SynchronizerContext` among it - is to
    be treated the same way. It works and is covered by functional tests, but
    signatures may still change in a minor release. Depend on it deliberately.
    The events - the query events below, :php:`AfterProfileUpdateEvent` and
    :php:`ModifyProfileImageMetadataEvent` - are public API.

..  _developers-query-events:

Narrowing what a plugin shows
=============================

:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent` and
:php:`\FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent` are dispatched
immediately before a plugin query is executed, and a listener adds conditions
to it. They are the supported way to make a plugin show fewer records than it
would - a consent flag, a site the profile belongs to, an editorial state.

..  list-table::
    :header-rows: 1

    *   -   Event
        -   Dispatched for
    *   -   :php:`ModifyProfileQueryEvent`
        -   the profile query of the list, list-and-detail and card plugins,
            the letter query of the two list plugins (see
            :ref:`developers-letter-availability`), and the uid lookup of the
            selected-profiles plugin
    *   -   :php:`ModifyContractQueryEvent`
        -   the uid lookup of the selected-contracts plugin

Both carry the Extbase :php:`QueryInterface` the conditions are built on
(:php:`getQuery()`), collect them through :php:`addConstraint()` and hand them
back through :php:`getConstraints()`. :php:`getPluginControllerActionContext()` is the plugin the
query is rendered for - its name, the settings of the content element, the
request, the site, the language and the content object - and is :php:`null`
where the query has no plugin behind it. :php:`ModifyProfileQueryEvent` also
carries :php:`getDemand()`, the list demand, which is :php:`null` for the uid
lookup of the selected-profiles plugin.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ShowOnlyConsentingProfiles.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class ShowOnlyConsentingProfiles
    {
        #[AsEventListener(identifier: 'my-extension/show-only-consenting-profiles')]
        public function __invoke(ModifyProfileQueryEvent $event): void
        {
            // The two list plugins only; the card plugin of the same page keeps everything.
            if (!in_array($event->getPluginControllerActionContext()?->getPluginName(), ['List', 'ListAndDetail'], true)) {
                return;
            }
            $query = $event->getQuery();
            $event->addConstraint($query->equals('publicDisplayConsent', true));
        }
    }

:php:`getPluginName()` is the name a plugin is **registered** with, not its
content element type. There are six, and two of them render a profile list:

..  list-table::
    :header-rows: 1

    *   - Plugin name
        - Content element type
    *   - :php:`List`
        - :sql:`academicpersons_list`
    *   - :php:`ListAndDetail`
        - :sql:`academicpersons_listanddetail`
    *   - :php:`Card`
        - :sql:`academicpersons_card`
    *   - :php:`SelectedProfiles`
        - :sql:`academicpersons_selectedprofiles`
    *   - :php:`SelectedContracts`
        - :sql:`academicpersons_selectedcontracts`
    *   - :php:`Detail`
        - :sql:`academicpersons_detail`

:php:`Detail` is in the table for completeness and never reaches these events:
neither of them is dispatched for the detail action. A detail view rendered by
the :php:`ListAndDetail` plugin reports that plugin's name, not :php:`Detail`.

The collected conditions are combined with the ones the extension builds
itself - storage folders, the organisational unit and function type filters of
the content element, the letter filter, hidden records and the language
handling - with a logical **AND**, and they are applied **before pagination**.
A *constraint* therefore only ever narrows a result, and it cannot drop a
condition the extension put there.

..  warning::

    That guarantee covers the constraints, and nothing else about the query.

    :php:`setOrderings()` is called on the query *after* the event, so a
    listener that sets an ordering is overwritten without a notice. The
    ordering belongs to the plugin: it is what the editor chose in the content
    element, and a listener that changed it would override that choice for
    every element at once.

    A condition set with :php:`matching()` instead of :php:`addConstraint()` is
    **folded into the same AND** rather than dropped, so it narrows like any
    other. Use :php:`addConstraint()` anyway: :php:`matching()` replaces what
    is on the query, so two listeners doing it leave only the last one's
    condition standing, while :php:`addConstraint()` collects.

    The **query settings, the limit and the offset are live**.
    :php:`getQuerySettings()` hands out the object the query is parsed from,
    and it is read after this event, so a listener that calls
    :php:`setRespectStoragePage(false)` or :php:`setIgnoreEnableFields(true)`
    on it widens the result past the content element - and a listener that
    calls :php:`setLimit()` cuts it. Nothing guards any of this; it is the same
    object because a listener needs it to build an expression in the first
    place. Add constraints and leave the rest of the query alone.

The detail view is not covered: neither event is dispatched for it. It resolves
its profile through Extbase argument mapping, and the one finder it does use -
for a hidden profile, when the plugin's "show hidden records" is on - builds
its own query and never goes through the shared path these events sit in. So a
profile a listener hides from the lists is still reachable through its own
detail URL; a condition that has to hold there as well belongs in an access
check, not in these events.

A project that narrowed the plugins by subclassing or XCLASSing the
repositories moves that code here; see
:ref:`breaking-profile-and-contract-finder-signatures`.

..  _developers-letter-availability:

Which letters lead somewhere
============================

The letter navigation of the list and list-and-detail plugins is told which
letters lead to a list that is not empty:
:php:`ProfileRepository::findAlphabetFilterLetters()`, assigned to the view as
`alphabetFilterLetters` - the letters `a` to `z`, each mapped to a boolean. It
is assigned only while the content element has the navigation switched on and
no profiles are selected by hand; a manual selection ignores the letter filter.

The letters come from the list's own query with the letter cleared, so the
active letter never narrows the others, and both events reach it:
:php:`ModifyProfileDemandEvent` and :php:`ModifyProfileQueryEvent` are
dispatched **twice** for a list that shows the navigation - once for the list,
once for its letters - and the query event carries the same plugin context both
times. A listener that narrows the list narrows the letters in the same way, as
long as it answers both calls alike. The demand of the second call carries no
letter.

Two things the letters do not see, both shared with the list's own pagination
count, which is computed in SQL as well:

*   a listener of :php:`ModifyListProfilesEvent` that **replaces** the result -
    the letters are computed from the query, not from the records that event
    hands on. Nor can that listener assign letters of its own: the list action
    assigns `alphabetFilterLetters` after the event and would overwrite them;
*   in a workspace preview, a profile deleted or hidden only in the workspace -
    it still makes its letter available. Live, the letters are exact.

The method compares with the letter filter's own predicate - :sql:`LIKE`, or
:sql:`ILIKE` on PostgreSQL, against the last name - so a name is available
under exactly the letter the list files it under. Where a name starting with an
umlaut ends up is a question of the database collation: under O on MariaDB and
MySQL, under no letter on PostgreSQL and SQLite.

..  _developers-trigger:

The trigger: AfterProfileUpdateEvent
====================================

:php:`\FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent` is a PSR-14 event
announcing that a profile aggregate — the profile record or one of its child
records — has changed and was persisted. This extension dispatches it after a
profile is auto-created for a frontend user
(:php:`AbstractProfileFactory::createProfileForUser()`, also reached by the
:bash:`academic:createprofiles` command); `EXT:academic_persons_edit`
dispatches it after every persisting frontend edit action, and project code —
typically a :php:`DataHandler` hook reacting to backend edits — may dispatch it
as well to trigger the same synchronisation.

The dispatch contract:

*   The event carries the **persisted default language profile**: its
    :php:`getUid()` returns a real uid, and the record is not a translation
    overlay. Listeners read the database, not the object, so all changes must
    be persisted before dispatching.
*   The profile's pid must resolve to a site — the synchronisation listener of
    `EXT:academic_persons_edit` determines the site from the request or from
    the pid and skips the event silently when it cannot.

Updating an existing profile from its frontend user record
(:php:`AbstractProfileFactory::updateProfileForUser()`, command
:bash:`academic:updateprofiles`) dispatches the event per profile the update
runs through — announced even when every value already matched, exactly like
the frontend editing flow. A profile whose :sql:`skip_sync` flag is set is
neither updated nor announced.

..  _developers-synchronisation:

The synchronisation surface
===========================

:php:`\FGTCLB\AcademicPersons\Service\RecordSynchronizerInterface` declares one
method, :php:`synchronize(SynchronizerContext $context)`. The shipped
implementation (:php:`RecordSynchronizer`) routes **every write through the
TYPO3 DataHandler** — nothing in TYPO3 outside the DataHandler honours
:php:`l10n_mode=exclude` or keeps translations consistent, so going through it
is what makes the created translations indistinguishable from ones created in
the backend: inline children, file references, MM relations,
``l10n_diffsource``, reference index, history and hooks are all carried along.

:php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext`
describes one synchronisation run. Build it through
:php:`SynchronizerContext::create()`, which takes the synchronizer instance,
the :php:`Site`, the allowed language ids, the table name and the record uid —
and silently drops language ids that are not positive or that the site does not
define, so a run never targets a language the site cannot render.

For each remaining language, :php:`synchronize()`:

*   creates a missing translation with a DataHandler ``localize`` command —
    the full record, including its inline child tree;
*   for an existing translation, re-submits the default record's
    :php:`l10n_mode=exclude` column values as a datamap (core's
    ``DataMapProcessor`` propagates them into every translation) and issues an
    ``inlineLocalizeSynchronize`` command per inline column, which carries
    child records added to the default record after the translation was
    created.

A missing record, a record that is not in the default language, or a record
that is invisible in the acting workspace makes the run a silent no-op.

..  _developers-workspaces:

Workspace behaviour
===================

The synchronisation acts **in the workspace of the acting backend user**: run
from a backend context inside a workspace, it creates versioned rows only
(``t3ver_wsid`` set, ``t3ver_state=1``) and never touches the live records —
publishing the workspace publishes the translations. When no backend user is
available (frontend and CLI contexts), a synthetic in-memory admin user acting
in the workspace of the current :php:`Context` is used.

Two refusals protect the live state:

*   A **frontend request acting in a non-live workspace** (a workspace preview)
    is refused entirely; a notice is logged and nothing is written. This
    policy is currently hardcoded.
*   A uid addressing a **workspace version row** (``t3ver_oid > 0``) is
    refused: the DataHandler addresses versioned records through their live
    uid, and accepting the version uid would publish draft values as live
    translations.

..  _developers-image-metadata:

Image metadata: ModifyProfileImageMetadataEvent
===============================================

:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent` is
dispatched immediately before this extension writes the metadata of a profile
image, and a listener decides what is written: whatever it leaves in
:php:`getMetadata()` is the field map that goes to the database, and an empty
map writes nothing at all.

It is dispatched for each of the two records that carry image metadata, and
:php:`getTargetTable()` says which one:

..  list-table::
    :header-rows: 1

    *   - :php:`getTargetTable()`
        - Written when
        - Fields
    *   - :sql:`sys_file_metadata`
        - a profile image is uploaded in the frontend, once, for the file that
          upload created — and only for the fields that record has empty
        - :sql:`title`, :sql:`alternative` and, with
          :composer:`typo3/cms-filemetadata`, :sql:`copyright`
    *   - :sql:`sys_file_reference`
        - the name of a profile record changes, from a backend save, a
          localization or a frontend edit
        - :sql:`title` and :sql:`alternative`

**Both records are handed over, whichever of them is written.**
:php:`getFile()` is the file — its own metadata record is
:php:`$event->getFile()->getMetaData()` — and :php:`getFileReference()` is the
image relation of the profile, :php:`null` only for a profile that has none.
:php:`getProfileUid()` is the profile record the image belongs to, a
translation for an image of its own, and :php:`getRequest()` the request the
write happens in: the frontend request for an upload or a frontend edit, the
backend request for a save, and :php:`null` on the command line or where the
caller has no request to pass on.

Fields the target table does not declare are dropped before the write, so a
listener may set a column unconditionally: where the installation does not have
it, the value goes nowhere. :sql:`copyright` is one such column — it belongs to
:sql:`sys_file_metadata` and :composer:`typo3/cms-filemetadata`, and the
relation row has no equivalent.

..  warning::

    **System fields are refused.** The identity of the record, the relation it
    is part of, its localization, its workspace columns and its enable columns —
    :sql:`uid`, :sql:`pid`, :sql:`file`, :sql:`uid_local`, :sql:`uid_foreign`,
    :sql:`tablenames`, :sql:`fieldname`, :sql:`sys_language_uid`,
    :sql:`l10n_parent`, :sql:`t3ver_*`, :sql:`deleted`, :sql:`hidden` and the
    rest of their kind — are dropped and a warning is written to the log. This
    event writes metadata; repointing a relation or moving a record is the
    :php:`DataHandler`'s business.

The two dispatches are not interchangeable. The metadata record of the file is
written **once**, which makes it the place for a value that has to survive —
the required attributes of :composer:`typo3/cms-filemetadata` or
:composer:`fgtclb/file-required-attributes`, for instance. The reference row is
rewritten on **every** save of the profile, so a listener that wants to own a
field there has to set it on every dispatch.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddImageRightOfUse.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddImageRightOfUse
    {
        #[AsEventListener(identifier: 'my-extension/add-image-right-of-use')]
        public function __invoke(ModifyProfileImageMetadataEvent $event): void
        {
            if ($event->getTargetTable() !== 'sys_file_metadata') {
                return;
            }
            $metadata = $event->getMetadata();
            $metadata['right_of_use'] = 'Portrait, own use only';
            $event->setMetadata($metadata);
        }
    }

..  warning::

    A listener runs inside the write of the profile record — for a backend save
    from within a :php:`DataHandler` hook. Keep it short and do not write
    profile records from it.

..  _developers-see-also:

See also
========

*   The changelog entry
    :ref:`Translation sync is routed through the DataHandler <important-1788193381>`
    for the behavioural differences to versions before 3.0.
*   `EXT:academic_persons_edit`, whose ``profile.allowedLanguages`` setting
    feeds the allowed language ids and whose event listener wires the pieces
    together.
*   The changelog entry
    :ref:`Narrow the profiles and contracts a plugin shows <feature-profile-and-contract-query-events>`
    for the query events, and
    :ref:`The plugins call different repository finders <breaking-profile-and-contract-finder-signatures>`
    for what they replace.
