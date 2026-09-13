..  _breaking-profile-images-render-as-picture:

=======================================================
Breaking: Profile images render as a responsive picture
=======================================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

The profile card - :file:`Partials/Profile/Item.html`, rendered by the list,
card, selected profiles and selected contracts content elements and by
`EXT:academic_contacts4pages` - and the public profile detail -
:file:`Partials/Profile/PublicProfile/ProfileImage.html` - render the profile
image through the responsive image partial of `EXT:academic_base`,
:file:`Academic/Image.html`.

The image is no longer a single :html:`<img>`. It is a :html:`<picture>` with
WebP sources per breakpoint and a lazily loading fallback :html:`<img>`, which
keeps the classes of the previous image: `card-img-top img-fluid` in the card,
`academic-persons-detail__image img-fluid rounded-0` in the detail, where the
alternative text stays the title and the names of the profile.

A card of a profile without an image shows a neutral placeholder,
:file:`EXT:academic_persons/Resources/Public/Images/ProfilePlaceholder.svg`,
taken from the new setting `plugin.tx_academicpersons.image.placeholder.default`
(a TypoScript constant and a site setting of the set
`fgtclb/academic-persons`). The detail view shows no image for such a profile,
as before. When the content element limits the shown fields and leaves the
image out, the card shows neither the image nor the placeholder, as before.

The plugin view registers the partials of `EXT:academic_base` with the partial
root path key `-1`, below the keys `0` and `1` of this extension and of the
project.

Impact
======

*   CSS that selects the image as a direct child of the card, for example
    :css:`.academic-persons-item > img`, no longer matches: the image is a
    child of the :html:`<picture>` now.
*   Cards of profiles without an image show the placeholder where they showed
    nothing.
*   A project that replaces the partial root paths of the plugin completely,
    and a project page object that renders `Profile/Item` through a view of
    its own, fail with an exception on the partial `Academic/Image` that the
    view cannot resolve.
*   An installation that removed `webp` from
    :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']` fails with the
    exception 1618992262 of the core image view helpers on every profile with
    an image.

A project override of :file:`Partials/Profile/Item.html` or of
:file:`Partials/Profile/PublicProfile/ProfileImage.html` keeps rendering its
own markup and is not affected.

Affected Installations
======================

Every installation that renders profile cards or the public profile detail of
this extension or of `EXT:academic_contacts4pages`.

Migration
=========

#.  Adjust CSS that addresses the card or detail image: select the
    :html:`<img>` by its class, or through the :html:`<picture>` element.
#.  To keep cards of profiles without an image empty, set
    `plugin.tx_academicpersons.image.placeholder.default` to an empty value;
    to show a placeholder of the project, set it to an `EXT:` path of a public
    file of the site package.
#.  A view whose partial root paths are replaced completely adds the path of
    `EXT:academic_base`:

    ..  code-block:: typoscript

        plugin.tx_academicpersons.view.partialRootPaths {
            -1 = EXT:academic_base/Resources/Private/Partials/
        }

#.  An override of :file:`Profile/Item.html` or
    :file:`Profile/PublicProfile/ProfileImage.html` that exists only to
    render a :html:`<picture>`, a crop variant or a placeholder can be
    removed. Breakpoints and widths are changed in an override of
    :file:`Academic/Image.html` instead, see :ref:`templates-profile-image`.
#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, TypoScript, ext:academic_persons
