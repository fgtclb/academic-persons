..  _integrator-person-details:

==============
Person Details
==============

..  image:: /Images/Icons/persons.svg
    :width: 48px
    :alt: Icon of the profile detail

:CType:
    ``academicpersons_detail``

:Plugin:
    `AcademicPersons`, plugin `Detail`, action
    :php:`ProfileController::detailAction()`

:Set:
    `fgtclb/academic-persons-detail`, or the aggregate set
    `fgtclb/academic-persons`

:Templates:
    :file:`Templates/Profile/Detail.html`, see :ref:`templates`

The public profile of one person: the name, the positions, the image, the
contact data of the contracts and the entries of the profile, as
:file:`Settings.yaml` lays them out, see :ref:`configuration-sections`. The
element loads the script of the profile, see :ref:`configuration-javascript`,
and its markup is described in :ref:`styling-detail`.

..  figure:: /Images/Screenshots/person-details-default.png
    :alt: Profile of Jane Doe with section navigation, name, positions, portrait, the contact data of two contracts and folded profile entries
    :class: with-border with-shadow

    The public profile of the detail element.

It belongs on the detail page the other elements link to: the
:ref:`Person List <integrator-person-list>`, the
:ref:`Person Contact Card <integrator-person-contact-card>`, the
:ref:`Selected Person’s Profiles <integrator-selected-profiles>` and the
:ref:`Selected Person’s Contracts <integrator-selected-contracts>`, through
their :guilabel:`Detail Page` or the site setting
:typoscript:`plugin.tx_academicpersons.detailPid`, and the contacts of a page
of :guilabel:`EXT:academic_contacts4pages`. Which profile it shows is the
argument of such a link. The profile is found wherever it is stored, so the
element has no :guilabel:`Startingpoint`.

Opened without a profile, with a profile that does not exist, or with a hidden
one while :guilabel:`Show hidden records` is off, the page ends with the "page
not found" handling of the site, see
:ref:`important-profile-detail-answers-not-found-without-a-profile`. Hide the
detail page in menus, a menu link carries no profile.

..  _integrator-person-details-configuration:

Settings
========

..  confval:: PageTitle display format
    :name: person-details-page-title-format
    :type: text, with a value picker
    :Default: empty

    The page title of the detail view, built from the profile. A placeholder
    ``%%FIELD_NAME%%`` is replaced with the field of the profile, the field
    name in capitals with underscores: ``%%TITLE%%``, ``%%FIRST_NAME%%``,
    ``%%MIDDLE_NAME%%``, ``%%LAST_NAME%%``. A placeholder
    ``%%LLL:EXT:my_sitepackage/Resources/Private/Language/locallang.xlf:key%%``
    is replaced with the label in the language of the page. Spaces of empty
    parts are collapsed. A placeholder that matches neither is left in the
    title as it is written, so a typing error is visible.

    The value picker offers :guilabel:`Default page title`, which empties the
    field, :guilabel:`Title Firstname Middlename Lastname` and
    :guilabel:`Lastname, Firstname Middlename`. Empty uses
    :typoscript:`plugin.tx_academicpersons.settings.pageTitleFormat` when a site
    sets it in TypoScript, and ``%%TITLE%% %%FIRST_NAME%% %%MIDDLE_NAME%%
    %%LAST_NAME%%`` otherwise.

    A placeholder of a project, for example the organisational unit, is added
    by a listener of :php:`ModifyProfileTitlePlaceholderReplacementEvent`, see
    :ref:`developers-event-modify-profile-title-placeholder-replacement-event`.

..  confval:: Show hidden records
    :name: person-details-show-hidden-records
    :type: checkbox
    :Default: off

    Shows a profile whose :guilabel:`Visible` switch is off instead of the
    "page not found" answer. Switch it on together with the list that links
    hidden profiles, on a page restricted to a frontend user group. See
    :ref:`configuration-hidden-profiles`.
