..  _breaking-public-profile-detail-partials:

================================================
Breaking: The partials of the detail view change
================================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

The public profile detail view is assembled from configuration instead of a
fixed template (:ref:`feature-configurable-public-profile`), and
:file:`Resources/Private/Templates/Profile/Detail.html` is rewritten for it.
It dispatches every configured element to one partial below
:file:`Resources/Private/Partials/Profile/PublicProfile/` and renders nothing
by itself:

:file:`Contact.html`, :file:`Element.html`, :file:`Headline.html`,
:file:`Links.html`, :file:`MenuSections.html`, :file:`MenuSectionsDatas.html`,
:file:`Position.html`, :file:`ProfileEntries.html`, :file:`ProfileImage.html`,
:file:`Subline.html` and :file:`TimelineItem.html`.

Those eleven files are the override surface of the detail view from 3.0 on.

Three partials the previous :file:`Detail.html` rendered are no longer part of
it:

*   :file:`Partials/Profile/DataHeader.html` **is deleted.** The detail view
    was its only caller. It rendered the heading of a data block through
    :html:`{header -> f:format.raw()}` and a :html:`f:switch` on a ``layout``
    value; the configurable view gives every element its own heading, in its
    own partial, escaped.
*   :file:`Partials/Profile/Header.html` and
    :file:`Partials/Profile/SectionHeader.html` **are kept, and the detail view
    no longer renders them.** They still render the headings of the list and
    card views of this extension and of the contact plugins of
    `EXT:academic_contacts4pages`, so an override of either keeps
    working there and stops having any effect on a profile detail page.

Impact
======

A project that overrode one of the three partials to style the detail view
loses that styling: the deleted one is not read any more, and the two that stay
are not reached from the detail view. Neither shows an error - the page renders,
without the override.

A project that overrides :file:`Templates/Profile/Detail.html` itself keeps
rendering its own template and is affected differently: see
:ref:`feature-configurable-public-profile`.

Affected Installations
======================

Every installation with a project override of
:file:`Partials/Profile/DataHeader.html`, :file:`Partials/Profile/Header.html`
or :file:`Partials/Profile/SectionHeader.html`, and every project whose own
template renders ``Profile/DataHeader``.

Migration
=========

#.  Remove an override of :file:`Partials/Profile/DataHeader.html`, and any
    :html:`<f:render partial="Profile/DataHeader" />` in project templates. A
    partial that no longer exists is a render time error, not a silent empty
    string.
#.  Move detail view styling out of :file:`Partials/Profile/Header.html` and
    :file:`Partials/Profile/SectionHeader.html` into the
    :file:`PublicProfile/` partial of the element it belongs to. Keep the
    override for the list and card views if it is still wanted there.
#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, ext:academic_persons
