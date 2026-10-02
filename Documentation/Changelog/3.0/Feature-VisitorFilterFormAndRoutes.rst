..  _feature-1791043401:

=============================================================
Feature: A filter form and speaking URLs for the visitor list
=============================================================

Description
===========

A list or list-and-detail element that offers a :ref:`visitor filter
<configuration-visitor-filters>` renders a form above the profiles, from the
new partial :file:`Profile/List/Filter.html`. It has one select per filter,
"all" first and then the options by name, with the active filter selected, and
a submit button. It contains no JavaScript, so it works without it and under a
strict Content Security Policy.

The form posts to the new non-cacheable :php:`filter` action of both plugins,
which answers with a redirect to the URL of the filtered list. That list is
cached like any other. The redirect keeps the view mode and the letter of the
list the form was on and starts on the first page. A value that is not one of
the options leads to the list without that filter.

Function types and organisational units have a new field
:guilabel:`URL segment` (:sql:`slug`), generated from the name when a record is
created in the backend or by the frontend user synchronisation, and unique per
language in the whole installation. The route enhancers
:file:`List.yaml` and :file:`ListAndDetail.yaml` use it in eighteen new routes
each: the function type, the unit or both, alone, with a page and with a
letter, and each of these with the view mode, for instance
:file:`/persons/function/professor/unit/biology/page-2`. The words
``function`` and ``unit`` are ``funktion`` and ``einheit`` for German. A record
without a slug, or with a slash in it, keeps its filter as a query argument.

Saving, hiding, deleting or restoring a function type or an organisational unit
now flushes the cached lists, so their filter form offers what the records say.

See :ref:`configuration-route-enhancers-filters` and
:ref:`templates-visitor-filters`.

Impact
======

Run the upgrade wizard ``academicPersons_fillFilterSlugs`` once the database
schema is updated. It gives every existing function type and organisational
unit the slug a save would generate. Until it ran, the filter URLs of those
records are query arguments, which keep working. The wizard is repeatable and
is offered again whenever a record has no slug. See
:ref:`upgrade-step-filter-slugs`.

A list whose element has no filter switched on renders what it rendered before.
A project copy of :file:`Profile/List.html` that does not render the new
partial shows no form.

A site that routes the filters with an enhancer of its own for the same plugin
removes it, and changes the shipped enhancer key in its site configuration
instead. Two enhancers on one plugin take each other's URLs, see
:ref:`configuration-route-enhancers-extend`.

..  index:: Backend, Frontend, TCA, ext:academic_persons
