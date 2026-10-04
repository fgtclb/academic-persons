..  _breaking-persons-public-profile-icons-moved-to-the-frontend-icon-registry:

====================================================
Breaking: Renamed icons and a frontend icon registry
====================================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

Every icon of this extension is replaced by a Font Awesome Free solid icon,
drawn in ``currentColor`` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
which inlines the file, so the icon takes the text colour of the backend or of
the page (ACE-585). The identifiers follow the scheme the academic extensions
share, ``tx-<extension key without underscores>-<group>-<name>``. The old
identifiers are removed, there are no aliases.

Which registry an icon is in depends on where it is shown:

*   **Backend icons** stay in :file:`Configuration/Icons.php`, the icon
    registry of the TYPO3 backend. The nine record icons become
    ``tx-academicpersons-record-*``, five of them drawn by a shared file of
    :guilabel:`academic_base`. The content element icons become
    ``tx-academicpersons-plugin-*``, in the TCA and in the new content element
    wizard alike. The plugin icon ``persons_icon`` was a brand mark in fixed
    colours with the core provider, its successors follow the text colour like
    every other icon. All six content elements showed ``persons_icon``. The
    list, the list and detail and the detail element now show
    ``tx-academicpersons-plugin-persons``, the profile card
    ``tx-academicpersons-plugin-card``, and the selected profiles and the
    selected contracts element share
    ``tx-academicpersons-plugin-selected-profiles``, in the page module and in
    the wizard alike. Up to 2.3, the profile card and the two selected elements
    showed the core icon ``actions-user`` in the wizard and the ``tt_content``
    default in the page module.
*   **Frontend icons** are in :file:`Configuration/FrontendIcons.php`, the
    frontend icon registry of :guilabel:`academic_base`, and are rendered with
    its ``ab:icon`` ViewHelper. The seven icons of the public profile detail
    view, the email, phone, address, room and office hours icons of the contact
    block and the expand and collapse icons of the fold-out entries, are the
    shared ``tx-academicbase-*`` icons of :guilabel:`academic_base` now. This
    extension registers no frontend icon of its own any more, its
    :file:`Configuration/FrontendIcons.php` is removed. The partials
    :file:`Partials/Profile/PublicProfile/Contact.html` and
    :file:`Partials/Profile/PublicProfile/ProfileEntries.html` render them with
    ``ab:icon``, with the arguments they had.

During the 3.0 development the seven icons of the public profile had
identifiers of this extension, ``academic-persons-*``, first in
:file:`Configuration/Icons.php` with ``core:icon``, then in
:file:`Configuration/FrontendIcons.php`. No 2.x release shipped them.

..  list-table:: Old and new identifiers
    :header-rows: 1

    *   -   2.x identifier
        -   3.0 identifier
        -   Registry
    *   -   ``tx_academicpersons_domain_model_address``
        -   ``tx-academicpersons-record-address``
        -   :file:`Icons.php`
    *   -   ``tx_academicpersons_domain_model_contract``
        -   ``tx-academicpersons-record-contract``
        -   :file:`Icons.php`
    *   -   ``tx_academicpersons_domain_model_email``
        -   ``tx-academicpersons-record-email``
        -   :file:`Icons.php`
    *   -   ``tx_academicpersons_domain_model_function_type``
        -   ``tx-academicpersons-record-function-type``
        -   :file:`Icons.php`
    *   -   ``tx_academicpersons_domain_model_location``
        -   ``tx-academicpersons-record-location``
        -   :file:`Icons.php`
    *   -   ``tx_academicpersons_domain_model_organisational_unit``
        -   ``tx-academicpersons-record-organisational-unit``
        -   :file:`Icons.php`
    *   -   ``tx_academicpersons_domain_model_phone_number``
        -   ``tx-academicpersons-record-phone-number``
        -   :file:`Icons.php`
    *   -   ``tx_academicpersons_domain_model_profile``
        -   ``tx-academicpersons-record-profile``
        -   :file:`Icons.php`
    *   -   ``tx_academicpersons_domain_model_profile_information``
        -   ``tx-academicpersons-record-profile-information``
        -   :file:`Icons.php`
    *   -   ``persons_icon``
        -   ``tx-academicpersons-plugin-persons`` for the content elements
            ``academicpersons_list``, ``academicpersons_listanddetail`` and
            ``academicpersons_detail``, ``tx-academicpersons-plugin-card``
            for ``academicpersons_card``, and
            ``tx-academicpersons-plugin-selected-profiles`` for
            ``academicpersons_selectedprofiles`` and
            ``academicpersons_selectedcontracts``
        -   :file:`Icons.php`
    *   -   ``academic-persons-envelope`` (3.0 development only)
        -   ``tx-academicbase-info-email``
        -   :file:`FrontendIcons.php` of :guilabel:`academic_base`
    *   -   ``academic-persons-phone`` (3.0 development only)
        -   ``tx-academicbase-info-phone``
        -   :file:`FrontendIcons.php` of :guilabel:`academic_base`
    *   -   ``academic-persons-address`` (3.0 development only)
        -   ``tx-academicbase-info-location``
        -   :file:`FrontendIcons.php` of :guilabel:`academic_base`
    *   -   ``academic-persons-room`` (3.0 development only)
        -   ``tx-academicbase-info-room``
        -   :file:`FrontendIcons.php` of :guilabel:`academic_base`
    *   -   ``academic-persons-clock`` (3.0 development only)
        -   ``tx-academicbase-info-time``
        -   :file:`FrontendIcons.php` of :guilabel:`academic_base`
    *   -   ``academic-persons-detail-plus`` (3.0 development only)
        -   ``tx-academicbase-action-expand``
        -   :file:`FrontendIcons.php` of :guilabel:`academic_base`
    *   -   ``academic-persons-detail-minus`` (3.0 development only)
        -   ``tx-academicbase-action-collapse``
        -   :file:`FrontendIcons.php` of :guilabel:`academic_base`

The files below :file:`Resources/Public/Icons/` are replaced as well. The nine
:file:`tx_academicpersons_domain_model_*.svg` and :file:`persons_icon.svg` are
deleted, and so are the Bootstrap Icons files of the 3.0 development,
:file:`address.svg`, :file:`clock.svg`, :file:`detail-minus.svg`,
:file:`detail-plus.svg`, :file:`envelope.svg`, :file:`phone.svg`,
:file:`room.svg`, and their :file:`LICENSE-bootstrap-icons.txt`. The files
this extension ships now are below :file:`record/` and :file:`plugin/`, listed
with their origin and licence (CC BY 4.0) in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.
:file:`Extension.svg` is unchanged.

Impact
======

An old identifier is no error anywhere. The icon API of the backend and the
``ab:icon`` ViewHelper both answer it with the ``default-not-found``
placeholder, so the page renders and the icon is wrong.

*   A template, TSconfig entry, TCA override or PHP call that names an old
    identifier shows the placeholder.
*   A site package that replaced an icon of this extension under its old
    identifier, in its :file:`Configuration/Icons.php` or its
    :file:`Configuration/FrontendIcons.php`, sees the shipped drawing again.
*   An override of :file:`Contact.html` or :file:`ProfileEntries.html` that
    renders one of the seven icons with ``core:icon`` shows the placeholder,
    because the icon registry of the backend does not know the shared frontend
    icons.
*   CSS that selects on the class TYPO3 renders for an icon, such as
    :css:`.icon-academic-persons-envelope` or
    :css:`.icon-tx_academicpersons_domain_model_profile`, matches nothing.
*   A reference to one of the deleted files, such as
    :file:`EXT:academic_persons/Resources/Public/Icons/persons_icon.svg`, points
    at nothing.
*   The Font Awesome drawings leave more room inside their square than the
    files they replace, so an icon sized for the old drawing looks smaller.

The rendered wrapper of a frontend icon keeps its shape: the classes
``icon``, ``icon-size-small``, ``icon-state-default`` and
``icon-<identifier>``, the attributes ``data-identifier`` and
``aria-hidden="true"``, and the inner ``icon-markup`` with the inlined
:html:`<svg>`. Only the identifier in it changes. Records are not affected, an
icon identifier is not stored in the database.

Affected Installations
======================

Installations that name one of the old identifiers or files in their own
templates, TSconfig, TCA, PHP, :file:`Configuration/Icons.php`,
:file:`Configuration/FrontendIcons.php` or CSS, or that override
:file:`Contact.html` or :file:`ProfileEntries.html`. The shipped templates, TCA
and TSconfig are migrated.

Migration
=========

#.  Search the site package for ``persons_icon``, for ``academic-persons-`` in
    icon identifiers and CSS classes, for the table names above where they are
    used as an icon identifier (``iconIdentifier``, ``typeicon_classes``,
    ``identifier=``, ``.icon-``) and for the deleted file names, and replace
    each with its 3.0 identifier from the table. ``persons_icon`` has three
    successors: take the one of the content element it is named for, and
    register a replacement of ``persons_icon`` under all three.
#.  Move a replacement of a backend icon to the new identifier in the
    :file:`Configuration/Icons.php` of the site package. The site package has
    to depend on :guilabel:`academic_persons`, so its entry is read after the
    shipped one.
#.  Move a replacement of a public profile icon to the shared identifier in
    the :file:`Configuration/FrontendIcons.php` of the site package, never to
    its :file:`Configuration/Icons.php`, which the frontend does not read. The
    file has the format of :file:`Icons.php`, and the site package has to
    depend on :guilabel:`academic_base`, so its entry is read after the shipped
    one:

    ..  code-block:: php
        :caption: EXT:mysitepackage/Configuration/FrontendIcons.php

        <?php

        use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

        return [
            'tx-academicbase-info-email' => [
                'provider' => CurrentColorSvgIconProvider::class,
                'source' => 'EXT:mysitepackage/Resources/Public/Icons/email.svg',
            ],
        ];

    A replacement of a shared identifier applies wherever an academic extension
    renders it, the expand and collapse icons also in the study plan of
    :guilabel:`academic_study_plan`. To replace an icon on the public profile
    only, override the partial and render an identifier of the site package.
#.  In an override of :file:`Contact.html` or :file:`ProfileEntries.html`,
    render the seven icons with ``<ab:icon`` and their new identifiers, keep
    every argument, and declare the namespace in the :html:`<html>` tag of the
    partial:
    ``xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"``. An icon
    of the project in the same partial either stays on ``core:icon`` or is
    registered in the :file:`Configuration/FrontendIcons.php` of the site
    package and rendered with ``ab:icon`` too.
#.  Adapt CSS that selects on :css:`.icon-<old identifier>` to
    :css:`.icon-<new identifier>`, and CSS that addressed an :html:`<img>` of a
    record or content element icon to the inlined :html:`<svg>`.
#.  Flush the TYPO3 caches, so the icon registries and the Fluid template cache
    are rebuilt.

..  index:: Backend, Fluid, Frontend, TCA, TSConfig, ext:academic_persons
