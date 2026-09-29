..  _feature-1790709368:

========================================================
Feature: Crop variant and placeholder of a profile image
========================================================

Description
===========

Which crop of a profile image a view renders, and which placeholder stands in
for a profile without an image, are site settings now. Until now a project
had to copy the item and the detail templates to change either of them.

..  list-table::
    :header-rows: 1

    *   -   Setting
        -   Default
        -   Read by
    *   -   `plugin.tx_academicpersons.image.list.cropVariant`
        -   `default`
        -   The list, list and detail, selected profiles and selected contracts
            elements, and the contacts of a page
    *   -   `plugin.tx_academicpersons.image.card.cropVariant`
        -   `default`
        -   The card element
    *   -   `plugin.tx_academicpersons.image.detail.cropVariant`
        -   `default`
        -   The public profile
    *   -   `plugin.tx_academicpersons.image.placeholder.mr`,
            `plugin.tx_academicpersons.image.placeholder.ms` and
            `plugin.tx_academicpersons.image.placeholder.diverse`
        -   (empty)
        -   A profile of that gender without an image

`square` and `portrait` are the other crop variants the profile image offers,
see :ref:`configuration-crop-variants`. An empty value renders `default`. An
image without a crop stored for the requested variant is shown uncropped, and
so is every image for a name it does not define.

A gender placeholder wins over
`plugin.tx_academicpersons.image.placeholder.default` for the profiles of its
gender. An empty one, and a profile without a gender, fall back to the default
placeholder. Every placeholder is an `EXT:` path to a public file, and a path
that does not resolve fails the rendering.

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicpersons.image {
      card.cropVariant = square
      placeholder.ms = EXT:my_sitepackage/Resources/Public/Images/PersonMs.svg
    }

The widths are not a setting: they come from the presets of the image partial
of :guilabel:`EXT:academic_base`, see :ref:`templates-profile-image-override`.

Impact
======

With the defaults every view renders what it rendered before.

A site that sets `square` or `portrait` shows an image uncropped, at its own
ratio, until an editor crops it for that variant in the backend. That includes
every image uploaded through the frontend editor of
:composer:`fgtclb/academic-persons-edit`, which stores no crop area.

All content elements of this extension share one settings array, so the card
template tells the item which view it renders: it passes `imageView: 'card'`
to :file:`Partials/Profile/List/Items.html`, which hands it on to
:file:`Partials/Profile/Item.html` and from there to the item image. A project
copy of any of the three that predates this change does not hand it on, and
its card reads the list crop variant:

*   A copy of :file:`Templates/Profile/Card.html` adds `imageView: 'card'` to
    the arguments of :file:`Profile/List/Items`.
*   A copy of :file:`Partials/Profile/List/Items.html` adds
    `imageView: imageView` to both :html:`<f:render partial="Profile/Item">`
    calls.
*   A copy of :file:`Partials/Profile/Item.html` that renders
    :file:`Profile/Item/Image` with arguments of its own adds
    `imageView: imageView` to them.

A site that leaves the card crop variant unset needs none of that.

The contacts of a page of :composer:`fgtclb/academic-contacts4pages` follow
the list crop variant and the gender placeholders, as they already followed
the default placeholder.

..  index:: Frontend, TypoScript, ext:academic_persons, ext:academic_contacts4pages
