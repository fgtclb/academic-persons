..  _developers-profile-list:

=================
The profile lists
=================

The list and list-and-detail plugins build more than the list itself: the
letters of the letter navigation, the links of the pagination and of the
navigation, and the page cache lifetime. This page describes how these follow
the list, and where :ref:`developers-event-modify-profile-demand-event` and
:ref:`developers-event-modify-profile-query-event` reach them.

..  _developers-letter-availability:

Which letters lead somewhere
============================

The letter navigation of the list and list-and-detail plugins is told which
letters lead to a list that is not empty:
:php:`ProfileRepository::findAlphabetFilterLetters()`, assigned to the view as
`alphabetFilterLetters`, the letters `a` to `z`, each mapped to a boolean. It
is assigned only while the content element has the navigation switched on and
no profiles are selected by hand. A manual selection ignores the letter filter.

The letters come from the list's own query with the letter cleared, so the
active letter never narrows the others, and both events reach it:
:php:`ModifyProfileDemandEvent` and :php:`ModifyProfileQueryEvent` are
dispatched **twice** for a list that shows the navigation, once for the list
and once for its letters, and the query event carries the same plugin context
both times. A listener that narrows the list narrows the letters in the same way, as
long as it answers both calls alike. The demand of the second call carries no
letter.

While the content element shows only contracts valid today,
:php:`ModifyProfileDemandEvent` is dispatched once more, for the next day on which
the list changes because a contract that meets its conditions starts or ends.
That day caps the page cache lifetime, so a listener that adds a condition on
contracts should answer this call like the others.

Two things the letters do not see, both shared with the list's own pagination
count, which is computed in SQL as well:

*   A listener of :php:`ModifyPluginViewEvent` that assigns other profiles to
    the view. The letters are computed from the query, not from the records
    the view renders.
*   In a workspace preview, a profile deleted or hidden only in the workspace.
    It still makes its letter available. Live, the letters are exact.

The method compares with the letter filter's own predicate, :sql:`LIKE` or
:sql:`ILIKE` on PostgreSQL against the last name, so a name is available
under exactly the letter the list files it under. Where a name starting with an
umlaut ends up is a question of the database collation: under O on MariaDB and
MySQL, under no letter on PostgreSQL and SQLite.

..  _developers-navigation-links:

What the navigation links carry
===============================

The list action of the list and list-and-detail plugins assigns
`activeListArguments`, the visitor's choices the pagination and the letter
navigation carry into their links. For the templates, see
:ref:`what the navigation links carry <templates-navigation-links>`. Which demand properties a visitor may set is one list in
:php:`ProfileController`, and it decides both what the property mapping accepts
from the request and what the links carry, so the two cannot differ. Today it
holds the page, the letter, the view mode and the two visitor filters. A value
equal to its default is left out.

The view mode is resolved before that: the list action checks the requested
mode against the switch of the content element and the allowed modes, and
writes the result back into the demand. It is empty for the default mode and
for a mode it rejected, so neither ever reaches a link. The mode the list renders is
also read before the events, so a demand a listener hands back cannot name a
partial.

The filters are checked before that as well. A value that is not a whole
number never reaches the property mapping, and the list action resets a
function type or unit that is not one of the options of the filter to `0`,
which includes every value while the content element does not offer the
filter. :php:`ProfileDemand::getFunctionTypeFilter()` and
:php:`getOrganisationalUnitFilter()` therefore hold an offered uid in the
default language, or `0`, when a listener of :php:`ModifyProfileDemandEvent`
receives the demand. A value the listener sets itself is applied as it is.

The value is read from the mapped demand before any event of the list is
dispatched, :php:`ModifyProfileDemandEvent` and :php:`ModifyProfileQueryEvent`
alike. A listener acts again on the request a link leads to, so what it changes
need not travel in the URL.
