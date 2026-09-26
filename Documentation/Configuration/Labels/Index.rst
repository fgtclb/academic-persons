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
      default.list.noProfilesFound = Nobody found
      de.list.noProfilesFound = Keine Personen gefunden
    }

    # Only in one content element:
    plugin.tx_academicpersons_list._LOCAL_LANG.default.list.noProfilesFound = Nobody found

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
itself: :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']`.

Earlier versions of this extension read these overrides on TYPO3 v12 and v13 from
:typoscript:`plugin.tx_academic_persons` instead, see
:ref:`the changelog <important-label-overrides-use-the-documented-path>`.

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
    *   - :xml:`detail.<name>`
        - :file:`Templates/Profile/Detail.html`
    *   - :xml:`detail.additionalInformation`
        - :file:`Templates/Profile/Detail.html`
    *   - :xml:`detail.contracts`
        - :file:`Templates/Profile/Detail.html`
    *   - :xml:`detail.emailAddresses`
        - :file:`Templates/Profile/Detail.html`
    *   - :xml:`detail.phoneNumbers`
        - :file:`Templates/Profile/Detail.html`
    *   - :xml:`detail.physicalAddress.<type>`
        - :file:`Templates/Profile/Detail.html`
    *   - :xml:`detail.publicationsLink`
        - :file:`Templates/Profile/Detail.html`
    *   - :xml:`detail.room`
        - :file:`Templates/Profile/Detail.html`
    *   - :xml:`detail.since`
        - :file:`Templates/Profile/Detail.html`, :file:`EXT:academic_persons_edit/Resources/Private/Partials/Profile/List/ProfileInformation.html` (from :typoscript:`plugin.tx_academicpersons` only)
    *   - :xml:`detail.till`
        - :file:`Templates/Profile/Detail.html`, :file:`EXT:academic_persons_edit/Resources/Private/Partials/Profile/List/ProfileInformation.html` (from :typoscript:`plugin.tx_academicpersons` only)
    *   - :xml:`detail.website`
        - :file:`Templates/Profile/Detail.html`
    *   - :xml:`list.noContractsFound`
        - :file:`Templates/Profile/SelectedContracts.html`
    *   - :xml:`list.noProfilesFound`
        - :file:`Partials/Profile/List/ItemList.html`, :file:`Templates/Profile/Card.html`, :file:`Templates/Profile/SelectedProfiles.html`
    *   - :xml:`widget.pagination.first`, :xml:`widget.pagination.previous`, :xml:`widget.pagination.next`, :xml:`widget.pagination.last` of :file:`EXT:fluid/Resources/Private/Language/locallang.xlf`, read with the extension name of the core extension fluid: from :typoscript:`plugin.tx_fluid._LOCAL_LANG`, shared with every other template that shows them
        - :file:`Partials/Profile/List/Pagination.html`
