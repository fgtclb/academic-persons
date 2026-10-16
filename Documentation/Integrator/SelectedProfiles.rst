..  _integrator-selected-profiles:

==========================
Selected Person’s Profiles
==========================

..  image:: /Images/Icons/selected-profiles.svg
    :width: 48px
    :alt: Icon of the selected profiles

:CType:
    ``academicpersons_selectedprofiles``

:Plugin:
    `AcademicPersons`, plugin `SelectedProfiles`, action
    :php:`ProfileController::selectedProfilesAction()`

:Set:
    `fgtclb/academic-persons-selected-profiles`, or the aggregate set
    `fgtclb/academic-persons`

:Templates:
    :file:`Templates/Profile/SelectedProfiles.html`, see :ref:`templates`

The profiles an editor picks, in the order they are picked, as tiles or as a
table: the board of a faculty, the team of a project, the contact persons of a
department across several storage folders. Each profile shows its contracts,
as the list does. The names link to the detail page.

..  figure:: /Images/Screenshots/selected-profiles-default.png
    :alt: Links Tiles and Table above four profile tiles with their contract fields
    :class: with-border with-shadow

    Four hand-picked profiles from two storage folders, in the picked order,
    with the view mode switch.

Unlike the :ref:`Person List <integrator-person-list>` with a
:guilabel:`List of profiles`, the element has no pagination, no letter
navigation and no filters, and needs no :guilabel:`Startingpoint`: the
profiles are found wherever they are stored. A profile that should appear in
one of its roles only belongs into the :ref:`Selected Person’s Contracts
<integrator-selected-contracts>` element.

..  _integrator-selected-profiles-configuration:

Settings
========

..  confval:: Detail Page
    :name: selected-profiles-detail-pid
    :type: page
    :Default: empty

    The page the names link to, see :confval:`Detail Page
    <person-list-detail-pid>`.

..  confval:: Link the names to the detail view
    :name: selected-profiles-detail-link
    :type: select
    :Default: Use the site setting

    See :confval:`Link the names to the detail view <person-list-detail-link>`.

..  confval:: Selected Profiles
    :name: selected-profiles-selected-profiles
    :type: select, several values
    :Default: empty

    The profiles the element shows, in the order of the right-hand list. The
    left-hand list offers every profile of the installation in the default
    language, ordered by last and first name. Empty shows the text that no
    profile was found.

    On a translated page a profile shows its translation. Whether one without
    a translation is shown in the default language follows the fallback type
    of the site language, see
    :ref:`important-88886-academic-persons-fixed-on-v14`.

..  confval:: Show only selected Fields
    :name: selected-profiles-show-fields
    :type: select, several values
    :Default: empty

    The fields each profile shows, see :confval:`Show only selected Fields
    <person-list-show-fields>`.

..  confval:: Contracts per profile
    :name: selected-profiles-contracts-display
    :type: select
    :Default: All contracts

    See :ref:`configuration-contract-display`.

..  confval:: Only contracts valid today
    :name: selected-profiles-contracts-only-valid
    :type: checkbox
    :Default: off

    Leaves out the contracts that have ended or not started yet. A picked
    profile is shown whatever its contracts are. See
    :ref:`configuration-contract-display`.

..  confval:: View Mode Toggle
    :name: selected-profiles-view-mode-toggle
    :type: checkbox
    :Default: off

    Offers the visitor a switch between the allowed view modes, see
    :confval:`View Mode Toggle <person-list-view-mode-toggle>`. The chosen mode
    is a plugin argument of its own here, keyed by the uid of the content
    element in the default language, for example
    ``tx_academicpersons_selectedprofiles[viewMode][12]=table``. Each element
    reads only its own key, so two elements of this kind on one page keep
    their modes apart, and a link reaches the same element in every language
    of the page. A translation without a record in the default language, an
    element created in another language for example, is keyed by its own uid,
    so a link of the default language does not reach it. A link of the switch
    carries the mode of its own element and nothing else.

..  confval:: View Mode Default
    :name: selected-profiles-view-mode-default
    :type: select
    :Default: Tiles

    See :confval:`View Mode Default <person-list-view-mode-default>`. The table
    has one row per profile.

..  confval:: Show hidden records
    :name: selected-profiles-show-hidden-records
    :type: checkbox
    :Default: off

    Shows a picked profile whose :guilabel:`Visible` switch is off as well.
    See :ref:`configuration-hidden-profiles`.
