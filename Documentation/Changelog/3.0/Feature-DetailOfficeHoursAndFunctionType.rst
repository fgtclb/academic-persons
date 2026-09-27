..  _feature-detail-office-hours-and-function-type:

===============================================================
Feature: Office hours and the function type on the profile page
===============================================================

Description
===========

The contact block of the profile detail view shows the office hours of each
contract, in a row of its own after the location and room, with a clock icon
and the label "Office hours". A contract without office hours gets no row.
Paragraphs, lists, emphasis and links written in the frontend editor are
kept, and a plain text value from the backend form or an import keeps its line
breaks. The core HTML sanitizer removes event handler attributes and prints a
script element as escaped text, so it never runs. See
:ref:`configuration-sections-profile-office-hours`.

The position line takes a new list in :file:`Settings.yaml`,
:yaml:`profile.details.position.fields`. It names what the line shows of each
contract, in order: :yaml:`position`, :yaml:`functionType` and
:yaml:`organisationalUnit`. The function type is shown with the name for the
profile's gender where one is maintained, and with its general name
otherwise. A site package states only the list:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicPersons/Settings.yaml

    profile:
      details:
        position:
          fields:
            - position
            - functionType

See :ref:`configuration-sections-profile-position-fields`.

The list, list and detail, card, selected profiles and selected contracts
elements offer the function type among their fields to show. The new partial
:file:`Profile/Contract/FunctionTypeName.html` renders its name for both the
detail view and the items, see :ref:`templates-function-type-name`.

Impact
======

A profile page whose contracts carry office hours shows them after the
update, without any configuration. A site that does not want them there hides
``academic-persons-detail__contact-row--office-hours`` in its stylesheet or
overrides :file:`Profile/PublicProfile/Contact.html`.

Plain text office hours are read as HTML too. A ``<`` in them starts a tag for
the sanitizer and is lost, so such values are better written without angle
brackets.

The shipped position line still shows the position alone, but every value of
the line now sits in a ``<span>`` of its own inside
``academic-persons-detail__position``. A stylesheet that targets the line
keeps working. One that expects text directly inside the paragraph targets
``academic-persons-detail__position-part`` instead.

..  index:: Frontend, YAML, ext:academic_persons
