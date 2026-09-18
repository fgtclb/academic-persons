..  _important-contract-fields-render-the-unit-and-tel-targets:

============================================================
Important: Contract rows render the unit and dialable phones
============================================================

Description
===========

:file:`Resources/Private/Partials/Profile/Contract/Field.html` renders one
contract field per call, for the fields an editor ticks in
:guilabel:`Show fields` of a list, list and detail, card, selected profiles or
selected contracts element. Two of those fields were rendered wrong, and both
are corrected.

**The organisational unit renders its name.** The field is offered in
:guilabel:`Show fields`, but the partial had no branch for it and
:file:`locallang.xlf` had no label for it either — so a contract that belongs
to a unit rendered a row with an empty label and no value at all. The row now
carries the label :guilabel:`Organisational Unit` (German
:guilabel:`Organisationseinheit`) followed by the unit's display text, falling
back to its unit name where the display text is empty. A contract without a
unit renders no row, as before.

**A phone link target carries no spaces.** The partial wrote the stored number
into :html:`href="tel:…"` unchanged, so a number stored as
``+49 6241 509 123`` produced a target with spaces in it. The spaces are now
removed from the target, exactly as the detail view has always done it; the
visible link text keeps the stored spelling.

Impact
======

A contract with an organisational unit renders one row more than it visibly
did before. An installation that hid the empty row with CSS — the row was
there, only without content — sees the unit appear and can drop that rule.

An installation that overrides
:file:`Resources/Private/Partials/Profile/Contract/Field.html` keeps its own
output and profits from neither correction until it drops its copy. The
arguments the partial is called with are unchanged, so an override of
:file:`Profile/Contract/Item.html` keeps working.

Affected Installations
======================

Installations that display the organisational unit or phone numbers in a
profile list, card or selection element.

..  index:: Frontend, ext:academic_persons
