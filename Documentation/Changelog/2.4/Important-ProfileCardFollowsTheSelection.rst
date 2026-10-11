.. _important-ace-330-academic-persons:

==========================================================
Important: The profile card renders the selection in order
==========================================================

Description
===========

The :guilabel:`Profile Card` content element shows the profiles an editor
picks in :guilabel:`List of profiles`. Two defects of this element are fixed.

**The card renders the selected fields.** The card shares its FlexForm with the
list plugins, including :guilabel:`Show only selected Fields`. The list actions
turn the stored value into a list before they render, the card action did not:
it handed the comma separated string to the template, and the profile partials
iterate that value with :html:`<f:for>`. As soon as one field was ticked, the
element failed with the Fluid exception 1256475113, *The argument "each" was
registered with type "array", but is of type "string"*, on TYPO3 v12 and v13
alike. The card now prepares the value like the list actions do and renders the
ticked fields.

**The card keeps the order of the selection.** The selection is an ordered list,
but the query that fetches it matches :sql:`uid IN (...)` and carries no
:sql:`ORDER BY`, so the cards appeared in the order the database returned them.
The list, selected profiles and selected contracts plugins restored the order of
the selection afterwards, the card did not. It now does the same.

Impact
======

A :guilabel:`Profile Card` element with ticked fields renders instead of an
error. Its cards appear in the order the editor arranged the profiles in
:guilabel:`List of profiles`, on every supported database.

Nothing has to be configured or migrated.

Affected Installations
======================

Installations with a :guilabel:`Profile Card` content element. Elements whose
selection was not in database order show their cards in a different order now.

.. index:: Frontend, ext:academic_persons
