..  _breaking-persons-header-partials-take-a-class-argument:

===============================================
Breaking: Header partials take a class argument
===============================================

Description
===========

The academic extensions name the argument that hands the class of a heading
to their header partials `class` now, in place of `positionClass`. This
extension follows for :file:`Profile/Header.html` and
:file:`Profile/SectionHeader.html`.

The profile card, the list and the detail keep their markup and their
classes, apart from the images: the shared image partial of
`EXT:academic_base` renders them with `ace-image`, `ace-picture` and
`ace-figure` now, see the breaking changelog "Form and image partials carry
speaking classes" of `EXT:academic_base`.

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Before
        -   After
    *   -   Argument of the header partials
        -   `positionClass`
        -   `class`

Impact
======

*   An override that renders one of the two header partials with
    `positionClass` renders the heading without that class.

Affected Installations
======================

Every installation whose own templates render :file:`Profile/Header.html` or
:file:`Profile/SectionHeader.html` with a class.

Migration
=========

#.  Replace `positionClass` with `class` where a template of the site
    package renders :file:`Profile/Header.html` or
    :file:`Profile/SectionHeader.html`.
#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, ext:academic_persons
