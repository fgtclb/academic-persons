..  _developers:

==============
For developers
==============

This chapter documents the programmatic surface this extension ships: the
events that let a project narrow what the plugins show and add to what they
render, the events of the frontend user synchronisation and of the import
writer, the event that announces every change of a profile, and the one that
decides what is written as the metadata of a profile image. Each event has a
page of its own. The topics behind them, the profile lists, the frontend user
synchronisation, the import of persons, the translation synchronisation and
the plugin action context, have pages of their own as well.

Which classes of this extension are public API, and what that promises, is
stated for all academic extensions on the `extension points page of
academic_base <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Developers/ExtensionPoints/Index.html>`__.
Every event a listener may rely on is public API. A listener registers with
the attribute of TYPO3, :php:`TYPO3\CMS\Core\Attribute\AsEventListener`.
Symfony's attribute of the same name registers nothing, and the listener never
runs.

..  _developers-events:

Events
======

..  list-table::
    :header-rows: 1
    :widths: 25 35 25 15

    *   -   Event
        -   Dispatched
        -   What a listener can change
        -   Owner
    *   -   :ref:`ModifyProfileDemandEvent <developers-event-modify-profile-demand-event>`
        -   in the list, list-and-detail and card plugins, before the profile
            query, the query of the letter navigation and the cache lifetime
            query are built from the demand
        -   replace the demand
        -   this extension
    *   -   :ref:`ModifyProfileQueryEvent <developers-event-modify-profile-query-event>`
        -   right before the profile query of the list, list-and-detail and
            card plugins, of the letter navigation and of the
            selected-profiles plugin is executed
        -   add conditions
        -   this extension
    *   -   :ref:`ModifyContractQueryEvent <developers-event-modify-contract-query-event>`
        -   right before the contract query of the selected-contracts plugin
            is executed
        -   add conditions
        -   this extension
    *   -   :ref:`ModifyProfileTitlePlaceholderReplacementEvent <developers-event-modify-profile-title-placeholder-replacement-event>`
        -   in the detail view, once per placeholder of the page title format
        -   replace the value of the placeholder
        -   this extension
    *   -   :ref:`ModifyPluginViewEvent <developers-event-modify-plugin-view-event>`
        -   in every plugin, once each time an action renders its view
        -   assign further view variables
        -   :guilabel:`academic_base`
    *   -   :ref:`ModifyTcaSelectFieldItemsEvent <developers-event-modify-tca-select-field-items-event>`
        -   when the backend builds the items of the shown fields of the
            plugins and of the contract selects
        -   reorder, remove or add items
        -   :guilabel:`academic_base`
    *   -   :ref:`ModifyProfileImageMetadataEvent <developers-event-modify-profile-image-metadata-event>`
        -   right before the metadata of a profile image is written, for the
            file and for the file reference
        -   change or empty the fields that are written
        -   this extension
    *   -   :ref:`AfterProfileUpdateEvent <developers-event-after-profile-update-event>`
        -   after a profile was created or updated: by a backend save or an
            import, by the frontend user commands, and by the profile editing
            of :guilabel:`academic_persons_edit`
        -   react to it, the event is read only
        -   this extension
    *   -   :ref:`BeforeImportedRecordWriteEvent <developers-event-before-imported-record-write-event>`
        -   in the import writer, once for every record of a person before it
            is written
        -   change the values that are written, veto the record
        -   this extension
    *   -   :ref:`ChooseProfileFactoryEvent <developers-event-choose-profile-factory-event>`
        -   in the commands :bash:`academic:createprofiles` and
            :bash:`academic:updateprofiles`, once per frontend user
        -   choose the profile factory
        -   this extension
    *   -   :ref:`BeforeProfileMappedFromFrontendUserEvent <developers-event-before-profile-mapped-from-frontend-user-event>`
        -   in the same commands, before the data of a frontend user is mapped
            onto a profile
        -   add or change values, skip the frontend user or the profile
        -   this extension
    *   -   :ref:`AfterProfileMappedFromFrontendUserEvent <developers-event-after-profile-mapped-from-frontend-user-event>`
        -   in the same commands, after the mapping and before the profile is
            saved
        -   change the profile
        -   this extension
    *   -   :php:`ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent`
        -   in the same commands, before the environment of a frontend user's
            site is set up
        -   nothing a project should rely on: the event is :php:`@internal`
            and experimental, like the environment handling it belongs to
        -   this extension, internal

..  toctree::
    :hidden:
    :titlesonly:

    Events/ModifyProfileDemandEvent
    Events/ModifyProfileQueryEvent
    Events/ModifyContractQueryEvent
    Events/ModifyProfileTitlePlaceholderReplacementEvent
    Events/ModifyPluginViewEvent
    Events/ModifyTcaSelectFieldItemsEvent
    Events/ModifyProfileImageMetadataEvent
    Events/AfterProfileUpdateEvent
    Events/BeforeImportedRecordWriteEvent
    Events/ChooseProfileFactoryEvent
    Events/BeforeProfileMappedFromFrontendUserEvent
    Events/AfterProfileMappedFromFrontendUserEvent

..  _developers-topics:

Topics
======

:ref:`developers-profile-list`
    Which letters of the letter navigation lead somewhere, and what the links
    of the pagination and the navigation carry.

:ref:`developers-frontend-user-sync-events`
    The order in which the commands :bash:`academic:createprofiles` and
    :bash:`academic:updateprofiles` dispatch their events.

:ref:`developers-import`
    Finding what an import wrote by its identifier, and writing the persons of
    another source with the import writer.

:ref:`developers-translation-synchronisation`
    The synchronizer that keeps the translations of a profile in step, and how
    it behaves in workspaces. Experimental.

:ref:`developers-plugin-action-context`
    The context the plugin events carry, and the names of the plugins.

..  toctree::
    :hidden:
    :titlesonly:

    ProfileList
    FrontendUserSync
    Import
    TranslationSynchronisation
    PluginActionContext

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
