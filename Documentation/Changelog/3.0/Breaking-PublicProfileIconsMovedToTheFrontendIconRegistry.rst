..  _breaking-persons-public-profile-icons-moved-to-the-frontend-icon-registry:

======================================================================
Breaking: The public profile icons moved to the frontend icon registry
======================================================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

The seven icons of the public profile detail view are shown in the frontend
only: ``academic-persons-envelope``, ``academic-persons-phone``,
``academic-persons-address``, ``academic-persons-room``,
``academic-persons-clock``, ``academic-persons-detail-plus`` and
``academic-persons-detail-minus``. They were registered in
:file:`Configuration/Icons.php`, for the icon registry of the TYPO3 backend,
and the partials rendered them with ``core:icon``.

They are now registered in :file:`Configuration/FrontendIcons.php`, for the
frontend icon registry of :guilabel:`academic_base`, and are no longer
registered in :file:`Configuration/Icons.php`. The partials
:file:`Partials/Profile/PublicProfile/Contact.html` and
:file:`Partials/Profile/PublicProfile/ProfileEntries.html` render them with the
``ab:icon`` ViewHelper of :guilabel:`academic_base`, with the arguments they
had. Identifiers, files and icon provider are unchanged.

The record icons of the nine tables and the plugin icon ``persons_icon`` stay
in :file:`Configuration/Icons.php`.

Impact
======

Two things change for a site, and neither shows an error:

*   A site package that replaced one of the seven icons in its own
    :file:`Configuration/Icons.php` sees the shipped drawing again on the
    detail page. The frontend icon registry does not read that file.
*   An override of :file:`Contact.html` or :file:`ProfileEntries.html` that
    still renders one of the seven icons with ``core:icon`` shows TYPO3's
    not-found icon in its place, because the icon registry of the backend no
    longer knows the identifier.

PHP or backend code that asks the :php:`IconFactory` of TYPO3 for one of the
seven identifiers gets the not-found icon as well.

The rendered markup of the icons is the same as before, with the same classes,
attributes and inlined SVG, so a site stylesheet needs no change.

Affected Installations
======================

Every installation whose site package replaces one of the seven icons, or
overrides one of the two partials, or renders one of the seven identifiers in a
template or PHP code of its own.

Migration
=========

#.  Move a replacement of one of the seven icons from the
    :file:`Configuration/Icons.php` of the site package to its
    :file:`Configuration/FrontendIcons.php`. The file has the format of
    :file:`Icons.php`. The site package has to depend on
    :guilabel:`academic_persons`, so its entry is read after the shipped one:

    ..  code-block:: php
        :caption: EXT:mysitepackage/Configuration/FrontendIcons.php

        <?php

        use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

        return [
            'academic-persons-envelope' => [
                'provider' => CurrentColorSvgIconProvider::class,
                'source' => 'EXT:mysitepackage/Resources/Public/Icons/envelope.svg',
            ],
        ];

#.  In an override of :file:`Contact.html` or :file:`ProfileEntries.html`,
    replace ``<core:icon`` with ``<ab:icon`` for the seven identifiers, keep
    every argument, and declare the namespace in the :html:`<html>` tag of the
    partial:
    ``xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"``. An icon
    of the project in the same partial either stays on ``core:icon`` or is
    registered in the :file:`Configuration/FrontendIcons.php` of the site
    package and rendered with ``ab:icon`` too.
#.  Flush the TYPO3 caches, so the icon registries and the Fluid template cache
    are rebuilt.

..  index:: Fluid, Frontend, ext:academic_persons
