..  index:: Templates; Profile image
..  _templates-profile-image:

=============
Profile image
=============

The profile card and the public profile detail render the profile image through
the responsive image partial of `EXT:academic_base`,
:file:`Academic/Image.html`. The card is the one of the list, card, selected
profiles and selected contracts content elements, and of the contacts content
element of `EXT:academic_contacts4pages`. The partial, its arguments and its
presets are described in the :guilabel:`Templates` chapter of
`EXT:academic_base`.

..  list-table::
    :header-rows: 1

    *   -   View
        -   Preset
        -   Crop variant
        -   Without an image
    *   -   Profile card of the card element,
            :file:`Partials/Profile/Item/Image.html`
        -   `card`
        -   `image.card.cropVariant`
        -   The gender placeholder or the default one, unless both are empty
    *   -   Profile card of every other element,
            :file:`Partials/Profile/Item/Image.html`
        -   `card`
        -   `image.list.cropVariant`
        -   The gender placeholder or the default one, unless both are empty
    *   -   Public profile, :file:`Partials/Profile/PublicProfile/ProfileImage.html`
        -   `detail`, with the title and the names as alternative text
        -   `image.detail.cropVariant`
        -   No image

When a content element limits the shown fields and leaves out the profile
image, the card shows neither the image nor the placeholder.

..  _templates-profile-image-crop-variant:

The crop variant of a view
==========================

Three site settings of the set `fgtclb/academic-persons`, also declared as
TypoScript constants with the same default, choose the crop variant a view
renders:

..  confval:: plugin.tx_academicpersons.image.list.cropVariant
    :name: plugin-tx-academicpersons-image-list-cropvariant
    :type: string
    :Default: default

    The profile cards of the list, list and detail, selected profiles and
    selected contracts elements, and of the contacts of a page.

..  confval:: plugin.tx_academicpersons.image.card.cropVariant
    :name: plugin-tx-academicpersons-image-card-cropvariant
    :type: string
    :Default: default

    The profile cards of the card element.

..  confval:: plugin.tx_academicpersons.image.detail.cropVariant
    :name: plugin-tx-academicpersons-image-detail-cropvariant
    :type: string
    :Default: default

    The profile image of the public profile.

The profile image offers the variants `default`, `square` and `portrait`, see
:ref:`configuration-crop-variants`. A project that defines crop variants of its
own sets their names here. An empty value renders `default`.

An image is shown uncropped, at its own ratio, when it has no crop stored for
the variant a view requests, whatever crop it has for `default`. That is the
case for a name the image does not define, which is therefore not an error, and
for every image nobody opened and saved in the backend form since the variants
were added. It is also the case for every image uploaded through the frontend
editor of :guilabel:`EXT:academic_persons_edit`, which stores no crop area. A
site that sets `square` or `portrait` shows those images uncropped until an
editor crops them in the backend.

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicpersons.image.card.cropVariant = square

All content elements share one settings array, so the card template tells the
item which view it renders: :file:`Templates/Profile/Card.html` passes
`imageView: 'card'` to :file:`Partials/Profile/List/Items.html`, which hands it
on to :file:`Partials/Profile/Item.html` and from there, with all its
arguments, to :file:`Partials/Profile/Item/Image.html`. Every other caller
leaves it out and gets the list crop variant.

A project copy of :file:`Templates/Profile/Card.html` or
:file:`Partials/Profile/List/Items.html`, and a copy of
:file:`Partials/Profile/Item.html` that renders the image with arguments of its
own, has to hand `imageView` on. Without it the card renders the list crop
variant.

..  _templates-profile-image-placeholder:

The placeholder
===============

..  confval:: plugin.tx_academicpersons.image.placeholder.default
    :name: plugin-tx-academicpersons-image-placeholder-default
    :type: string
    :Default: EXT:academic_persons/Resources/Public/Images/ProfilePlaceholder.svg

    The placeholder of a profile without an image, a neutral silhouette by
    default. An empty value switches it off: a card of a profile without an
    image then shows no image, unless a gender placeholder applies.

..  confval:: plugin.tx_academicpersons.image.placeholder.mr
    :name: plugin-tx-academicpersons-image-placeholder-mr
    :type: string
    :Default: (empty)

    The placeholder of a profile of the gender :guilabel:`Mr.` without an
    image. `placeholder.ms` and `placeholder.diverse` do the same for the
    other two genders. An empty value, and a profile without a gender, use
    the default placeholder.

The four are TypoScript constants and site settings of the set
`fgtclb/academic-persons`, with the same defaults. The generic one is called
`default` rather than `placeholder` itself because a site setting either holds
a value or has children, never both.

A project that adds a gender value of its own to the profile maps a placeholder
for it into the plugin settings itself, since only the three shipped values are
settings. The contacts of a page of :guilabel:`EXT:academic_contacts4pages`
have plugin settings of their own and need the same line:

..  code-block:: typoscript
    :caption: TypoScript setup

    plugin.tx_academicpersons.settings.image.placeholder.other = EXT:my_sitepackage/Resources/Public/Images/Person.svg
    plugin.tx_academiccontacts4pages.settings.image.placeholder.other = EXT:my_sitepackage/Resources/Public/Images/Person.svg

*   An `EXT:` path to a public file of another extension, usually the site
    package, replaces the placeholder. A combined file identifier such as
    `1:/user_upload/placeholder.svg` is not supported.
*   A path that does not resolve fails the rendering, so a missing file is
    noticed when the site package is deployed rather than hidden.
*   The placeholder has an empty alternative text. It shows nobody, and the
    name is the heading of the same card.

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicpersons.image.placeholder {
      default = EXT:my_sitepackage/Resources/Public/Images/Person.svg
      ms = EXT:my_sitepackage/Resources/Public/Images/PersonMs.svg
    }

..  _templates-profile-image-override:

Changing the image markup
=========================

The plugin view registers :file:`EXT:academic_base/Resources/Private/Partials/`
with the partial root path key `-1`, below its own partials at `0` and the
project path at `1`. A project changes the markup, the breakpoints and the
widths of every profile image by placing its own :file:`Academic/Image.html`
in the partial root path it configures through
`plugin.tx_academicpersons.view.partialRootPath` - there is no need to
override :file:`Profile/Item.html` for it.

The widths are deliberately not a setting. Each preset is a section of that
partial, :html:`Preset-card` and :html:`Preset-detail` for the profile images,
and a project that overrides the partial changes them for every academic
extension at once.

The card renders it through :file:`Partials/Profile/Item/Image.html`, which is
where the preset, the crop variant, the placeholder and the classes are
decided. Overriding that one file changes them for the card alone, without
touching the shared partial and without copying the item - see
:ref:`templates-item-and-list-partials`.
