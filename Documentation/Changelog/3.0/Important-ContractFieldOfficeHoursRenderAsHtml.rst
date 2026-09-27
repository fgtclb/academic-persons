..  _important-contract-field-office-hours-render-as-html:

=================================================
Important: List items render office hours as HTML
=================================================

Description
===========

:file:`Resources/Private/Partials/Profile/Contract/Field.html` printed the office
hours of a contract as escaped text. The frontend editor of
`EXT:academic_persons_edit` stores office hours as HTML, so the list, list and
detail, card, selected profiles and selected contracts elements, and the
contacts element of `EXT:academic_contacts4pages`, showed the markup itself,
for example ``<p>Tuesday 10:00 to 12:00</p>``, wherever office hours were
shown. That is the case by default: office hours are one of the fields an
element without a :guilabel:`Show only selected Fields` selection renders, and
the contacts element renders those default fields as shipped. Plain text office
hours from the backend form or an import lost their line breaks in the same
place.

The field now renders office hours as HTML. Line breaks become ``<br>``, and
the core HTML sanitizer, the default build of :html:`<f:sanitize.html>`, keeps
paragraphs, lists, emphasis and links. It removes event handler attributes and
prints an element it does not allow, such as a script, as escaped text.

Impact
======

Office hours written in the frontend editor render as paragraphs and links in
the list items instead of as visible tags, and plain text keeps its lines.

Plain text goes through the sanitizer too, so a ``<`` in it is read as the
start of a tag and is lost. Such values are better written without angle
brackets.

An installation that overrides
:file:`Resources/Private/Partials/Profile/Contract/Field.html` keeps its own
output until it drops its copy or takes over the new office hours branch.

..  index:: Frontend, ext:academic_persons, ext:academic_contacts4pages
