..  index:: Configuration; Labels
..  _configuration-labels:

======
Labels
======

The labels this extension shows in the frontend come from
:file:`EXT:academic_persons/Resources/Private/Language/locallang.xlf` and its
translations; the table below names the ones that come from another file. A site
changes a label without copying a template, in TypoScript:
under :typoscript:`plugin.tx_academicpersons._LOCAL_LANG` for every content element
of the extension, or under :typoscript:`plugin.tx_academicpersons_<plugin>._LOCAL_LANG`
for one of them. A label set for the plugin wins over one set for the extension.

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    plugin.tx_academicpersons._LOCAL_LANG {
      default.detail.contact = Get in touch
      de.detail.contact = Kontakt
    }

    # Only in one content element:
    plugin.tx_academicpersons_detail._LOCAL_LANG.default.detail.contact = Get in touch

The dots of a key need no escaping: TypoScript reads them as levels of its tree,
and TYPO3 joins the levels to the key again. A label of another language goes
under its language key, :typoscript:`de` for German.

..  list-table:: The path of each content element
    :header-rows: 1

    *   - Content element
        - Path
    *   - :guilabel:`Profile list` (:typoscript:`academicpersons_list`)
        - :typoscript:`plugin.tx_academicpersons_list._LOCAL_LANG`
    *   - :guilabel:`Selected profiles` (:typoscript:`academicpersons_selectedprofiles`)
        - :typoscript:`plugin.tx_academicpersons_selectedprofiles._LOCAL_LANG`
    *   - :guilabel:`Selected contracts` (:typoscript:`academicpersons_selectedcontracts`)
        - :typoscript:`plugin.tx_academicpersons_selectedcontracts._LOCAL_LANG`
    *   - :guilabel:`Profile detail` (:typoscript:`academicpersons_detail`)
        - :typoscript:`plugin.tx_academicpersons_detail._LOCAL_LANG`
    *   - :guilabel:`Profile list and detail` (:typoscript:`academicpersons_listanddetail`)
        - :typoscript:`plugin.tx_academicpersons_listanddetail._LOCAL_LANG`
    *   - :guilabel:`Profile card` (:typoscript:`academicpersons_card`)
        - :typoscript:`plugin.tx_academicpersons_card._LOCAL_LANG`

A language file override works as well, and replaces the label of the file
itself: :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` on
TYPO3 v13, :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` on
TYPO3 v14.

Earlier versions of this extension read these overrides on TYPO3 v13 from
:typoscript:`plugin.tx_academic_persons` instead, see
:ref:`the changelog <important-label-overrides-use-the-documented-path>`.

The subline of the public profile, the help texts of the fields and the headings of
the document sections take a full ``LLL:EXT:`` reference; the help texts of contract
and contact fields and of document sections also literal text. A translation domain
reference of TYPO3 v14, ``LLL:my_sitepackage.messages:key``, does not resolve there:
the translations pass the name of their extension, which TYPO3 then prefers.

The help texts and the section headings are shown by the profile editor, which
translates them with its own name: a site overrides them under
:typoscript:`plugin.tx_academicpersonsedit._LOCAL_LANG`, keyed by the id of the label
in its file.

Where the labels are shown
==========================

Placeholders in angle brackets stand for a part of the key that the template
or the code fills in, a category type or a field name for example.

..  list-table::
    :header-rows: 1
    :widths: 45 55

    *   - Key
        - Shown by
    *   - :xml:`contracts.<field>`
        - :file:`Partials/Profile/Contract/Field.html`
    *   - :xml:`detail.<field>`
        - :file:`Partials/Profile/PublicProfile/Links.html`, :file:`Partials/Profile/PublicProfile/MenuSections.html`, :file:`Partials/Profile/PublicProfile/MenuSectionsDatas.html`, :file:`Partials/Profile/PublicProfile/ProfileEntries.html`
    *   - :xml:`detail.contact`
        - :file:`Partials/Profile/PublicProfile/Contact.html`
    *   - :xml:`detail.navigation`
        - :file:`Partials/Profile/PublicProfile/MenuSections.html`
    *   - :xml:`detail.physicalAddress.<address type>`
        - :file:`Partials/Profile/PublicProfile/Contact.html`
    *   - :xml:`detail.room`
        - :file:`Partials/Profile/PublicProfile/Contact.html`
    *   - :xml:`detail.since`
        - :file:`Partials/Profile/PublicProfile/TimelineItem.html`
    *   - :xml:`detail.till`
        - :file:`Partials/Profile/PublicProfile/TimelineItem.html`
    *   - :xml:`list.alphabetFilter.navigation`
        - :file:`Partials/Profile/List/AlphabetPagination.html`
    *   - :xml:`list.alphabetFilter.noProfiles`
        - :file:`Partials/Profile/List/AlphabetPagination.html`
    *   - :xml:`list.alphabetFilter.showAll`
        - :file:`Partials/Profile/List/AlphabetPagination.html`
    *   - :xml:`list.noContractsFound`
        - :file:`Partials/Profile/List/EmptyState.html`
    *   - :xml:`list.noProfilesFound`
        - :file:`Partials/Profile/List/EmptyState.html`
    *   - :xml:`list.table.<column>`
        - :file:`Partials/Profile/ViewMode/Table.html`
    *   - :xml:`list.viewMode.<view mode>`
        - :file:`Partials/Profile/ViewMode/Switch.html`
    *   - :xml:`list.viewMode.navigation`
        - :file:`Partials/Profile/ViewMode/Switch.html`
    *   - The subline the setting :typoscript:`publicProfile.details.subline` names, a full reference into a language file: the override is keyed by the id of the label in that file
        - :file:`Partials/Profile/PublicProfile/Subline.html`
    *   - :xml:`widget.pagination.first`, :xml:`widget.pagination.previous`, :xml:`widget.pagination.next`, :xml:`widget.pagination.last` of :file:`EXT:fluid/Resources/Private/Language/locallang.xlf`, read with the extension name of the core extension fluid: from :typoscript:`plugin.tx_fluid._LOCAL_LANG`, shared with every other template that shows them
        - :file:`Partials/Profile/List/Pagination.html`
