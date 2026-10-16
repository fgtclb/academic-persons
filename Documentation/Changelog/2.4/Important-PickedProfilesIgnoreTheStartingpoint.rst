..  _important-ace-923-picked-profiles-academic-persons:

===================================================
Important: Picked profiles ignore the Startingpoint
===================================================

Description
===========

The :guilabel:`Persons List` and :guilabel:`Persons List and Detail` content
elements show exactly the profiles an editor picks in :guilabel:`List of
profiles`, in the order they are picked. Their :guilabel:`Startingpoint` still
applied to that selection: a picked profile stored in a folder outside of it
was left out without a notice, although the editor had chosen it explicitly.
The :guilabel:`Profile Card` and :guilabel:`Profiles: Selected Profiles`
elements, which pick profiles the same way, never applied a storage folder.

A selection by hand now ignores the :guilabel:`Startingpoint` in the two list
elements as well. A picked profile is shown wherever it is stored. Hidden and
deleted profiles, the start and end time of a profile, :guilabel:`Show hidden
records`, the language handling and the workspace apply as before, and a list
without a selection still shows the profiles of its :guilabel:`Startingpoint`
only.

Impact
======

A list with picked profiles from more than one folder shows all of them, in
the order of the selection, also when it is paginated. Listeners of
:php:`ModifyProfileQueryEvent` receive the query of such a list with the
storage folders of the element still set in its query settings, but no longer
respected.

Affected Installations
======================

Installations with a :guilabel:`Persons List` or :guilabel:`Persons List and
Detail` element that combines :guilabel:`List of profiles` with a
:guilabel:`Startingpoint`, and picked a profile outside of it. Such a profile
appears on the page now. Where it should not, remove it from the selection.

..  index:: Frontend, ext:academic_persons
