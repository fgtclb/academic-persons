..  _important-ace-920-academic-persons:

==========================================
Important: Demand listeners work on a copy
==========================================

Description
===========

The profile list queried its profiles with its own demand object and handed
that very object to the listeners of :php:`ModifyProfileDemandEvent`. The
list action kept reading the object after the query, so a listener that
changed the demand it received, instead of handing back a changed copy
through :php:`setDemand()`, changed more than the query: the order of a
selection by hand, the page the pagination showed and the demand the list
template received as ``{demand}`` followed the listener as well. The same
listener written with a copy did not reach them.

:php:`FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository::findByDemand()`
now dispatches the event with a copy of the demand. A listener changes the
profiles the list queries, whether it changes the demand in place or hands
back another one, and never the demand of the list. The order of a selection
by hand, the page and the demand of the template come from the list's own
demand, built from the content element and the request as before, unless a
listener of a later event of the list replaces that demand on purpose.

Impact
======

A listener that changes the demand in place still changes the profiles the
list queries. What it no longer does:

*   A selection by hand the listener sets or changes is not the order of the
    list any more. The query returns the selected profiles in the order of
    their uids, and the list orders them by hand only when its own demand
    selects profiles, in the order of that selection.
*   A selection by hand the listener clears does not widen a list whose
    content element selects profiles. The list keeps to the profiles of its
    own selection.
*   A page the listener sets does not move the pagination.
*   A letter the listener sets does not switch the pagination off.
*   The template receives the list's own demand, not the listener's change.
*   A listener of :php:`ModifyListProfilesEvent`, where the list dispatches
    it, receives the list's own demand, not one a demand listener changed in
    place.

Listeners that hand back a copy through :php:`setDemand()` behave as before.
The card plugin, the other caller of :php:`findByDemand()` in this extension,
reads nothing back from its demand and is not affected.

Affected Installations
======================

Installations with a listener of :php:`ModifyProfileDemandEvent` that changes
the demand it receives in place, and in particular one that relies on it to
order a selection by hand, to move the page or to pass a value to the list
template. A selection to order belongs in the content element, and a value
for the template is assigned to the view by a listener of its own.

..  index:: Frontend, PHP-API, ext:academic_persons
