..  _breaking-public-profile-ships-no-stylesheet:

==============================================
Breaking: The profile views ship no stylesheet
==============================================

Description
===========

The views of profiles no longer bring a stylesheet. Two files and their
sources are removed:

*   :file:`EXT:academic_persons/Resources/Public/Css/frontend/profile-detail.css`
    of the public profile, the detail view of a profile, which
    :file:`Templates/Profile/Detail.html` registered. The template registers
    only its module, ``@fgtclb/academic-persons/frontend/profile.js``, now.
*   :file:`EXT:academic_persons/Resources/Public/Css/frontend/profile-list.css`
    of the list, the card, the selected profiles and the selected contracts,
    which their templates and :file:`Partials/Profile/Item.html` registered.

The markup carries the speaking ``ace-*`` classes and the Bootstrap grid
classes, and the site package styles it.

Both stylesheets came with the development of 3.0, see
:ref:`feature-configurable-public-profile` for the detail view, and no release
of version 2 shipped them. Changelog entries of 3.0 that speak of the
stylesheet of a profile view describe the ones this entry removes.

The rules are kept as an example in the mono repository the extension is
developed in: the stylesheet of its development instances,
`_academic-persons.scss
<https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-persons.scss>`__
for the detail view and `_academic-persons-list.scss
<https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-persons-list.scss>`__
for the lists, both of EXT:academics_dev_site, which is never shipped with an
extension. The detail partial also declares the custom properties
``--academic-persons-detail-*`` the view was themed through, and reads
``--academic-persons-detail-scroll-offset``, which the module writes.

Impact
======

The detail view renders with the styling of the site alone. Beyond its
appearance, two parts depend on rules of a stylesheet:

*   A fold-out entry shows its expand and its collapse glyph at the same time.
    Which one is hidden depends on the :html:`aria-expanded` state the module
    writes.
*   The navigation of the left column is only sticky when a rule makes it so.
    The module keeps it below the page header, it does not position it.

The shipped icons carry a size of their own, `1em`, and stay visible. A
replacement drawing a site package registers without a width and a height of
its own collapses to nothing unless a rule of the site sizes the icon, as the
example does by sizing it to its container.

The lists keep working with the styling of the site alone. They lose the
card layout of an item with its image above the text, the size of that image,
the striping of the table view, the centred pagination and the dimmed letters
of the letter navigation that have no profile.

Affected Installations
======================

Every installation that renders a profile view of the extension, the detail
view or a list, card, selected profiles or selected contracts element, and has
no stylesheet of its own for it. Installations whose template override
registers :file:`Css/frontend/profile-detail.css` or
:file:`Css/frontend/profile-list.css` by path.

Migration
=========

#.  Style the views in the site package. Copy the rules of the examples above
    into the stylesheet of the site and adapt them to the theme, keeping at
    least the two parts of the detail view listed under *Impact*, and the size
    of the icons when the site replaces one.
#.  An overridden template or :file:`Partials/Profile/Item.html` that
    registers one of the two files drops that line, the files do not exist any
    more.

.. index:: Frontend, Fluid, ext:academic_persons
