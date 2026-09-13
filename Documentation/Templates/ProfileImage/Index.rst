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
        -   Without an image
    *   -   Profile card, :file:`Partials/Profile/Item.html`
        -   `card`
        -   The placeholder, unless it is empty
    *   -   Public profile, :file:`Partials/Profile/PublicProfile/ProfileImage.html`
        -   `detail`, with the title and the names as alternative text
        -   No image

When a content element limits the shown fields and leaves out the profile
image, the card shows neither the image nor the placeholder.

..  _templates-profile-image-placeholder:

The placeholder
===============

`plugin.tx_academicpersons.image.placeholder.default` is a TypoScript constant
and a site setting of the set `fgtclb/academic-persons`. Its default is
:file:`EXT:academic_persons/Resources/Public/Images/ProfilePlaceholder.svg`, a
neutral silhouette.

*   An `EXT:` path to a public file of another extension, usually the site
    package, replaces the placeholder. A combined file identifier such as
    `1:/user_upload/placeholder.svg` is not supported.
*   An empty value switches the placeholder off: a card of a profile without
    an image shows no image.
*   A path that does not resolve fails the rendering, so a missing file is
    noticed when the site package is deployed rather than hidden.

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicpersons.image.placeholder.default = EXT:my_sitepackage/Resources/Public/Images/Person.svg

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
