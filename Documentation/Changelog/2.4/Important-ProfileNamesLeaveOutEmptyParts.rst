.. _important-ace-853-academic-persons:

==============================================
Important: Profile names leave out empty parts
==============================================

Description
===========

The templates composed the name of a profile from its title, first name, middle
name and last name with a space between every two parts, also when a part was
empty. A profile without a middle name was rendered with two spaces between
first and last name, and on the detail page a profile without a title started
with a space.

The name now leaves out the parts a profile does not have, together with their
space:

*   The heading of a profile in
    :file:`Resources/Private/Partials/Profile/Item.html`, which the
    :guilabel:`Persons List`, :guilabel:`Persons List and Detail`,
    :guilabel:`Profile Card`, :guilabel:`Profiles: Selected Profiles` and
    :guilabel:`Profiles: Selected Contracts` plugins render for every profile,
    and the contacts plugin of `EXT:academic_contacts4pages` as well. It has no
    title, as before.
*   The heading of :file:`Resources/Private/Templates/Profile/Detail.html` and
    the :html:`alt` text of the profile image on the detail page.

A profile with all four parts is rendered as before.

Impact
======

The page looks the same, browsers collapse the spaces. The markup changes:
the heading text and the :html:`alt` attribute hold the name with single
spaces and without a leading space. Code or tests that match the rendered
markup may have to be adjusted.

Affected Installations
======================

Every installation that renders profiles without a title or a middle name.
Nothing has to be done on update.

A site that overrides one of the two files keeps the old composition. To take
over the change, use these expressions in the copy. In
:file:`Partials/Profile/Item.html`, the :html:`header` argument of both
headings:

..  code-block:: html

    header: '{profile.firstName}{f:if(condition: profile.middleName, then: \' {profile.middleName}\')} {profile.lastName}',

In :file:`Templates/Profile/Detail.html`, the :html:`header` argument and the
:html:`alt` attribute of the image:

..  code-block:: html

    header: '{f:if(condition: profile.title, then: \'{profile.title} \')}{profile.firstName}{f:if(condition: profile.middleName, then: \' {profile.middleName}\')} {profile.lastName}',

    alt="{f:if(condition: profile.title, then: '{profile.title} ')}{profile.firstName}{f:if(condition: profile.middleName, then: ' {profile.middleName}')} {profile.lastName}"

.. index:: Frontend, Fluid, ext:academic_persons
