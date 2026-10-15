.. _important-ace-913-academic-persons:

=======================================================================
Important: The pagination of the profile list falls back to 10 per page
=======================================================================

Description
===========

The profile list and list-and-detail plugins read the number of profiles per
page from :guilabel:`Results per Page` and the number of page links from
:guilabel:`Number of Links`. A field saved empty replaced the value of the
site, and the list handed ``0`` to the paginator. With :guilabel:`Pagination`
switched on, the content element then ended the request with
``InvalidArgumentException`` 1573061766, "Argument $itemsPerPage must be
greater than 0", instead of rendering the list. An empty
:guilabel:`Number of Links` did not fail, it gave the page navigation the 10
links of :composer:`georgringer/numbered-pagination` instead of the value of
the site.

Three changes follow:

*   :typoscript:`pagination.resultsPerPage` and
    :typoscript:`pagination.numberOfLinks` are listed in
    :typoscript:`plugin.tx_academicpersons.ignoreFlexFormSettingsIfEmpty`. A
    field saved empty or as ``0`` takes the value of
    :typoscript:`plugin.tx_academicpersons.pagination.resultsPerPage` and
    :typoscript:`plugin.tx_academicpersons.pagination.numberOfLinks`. A value
    the editor entered still wins.
*   A number of profiles per page below ``1`` falls back to ``10``, whether it
    comes from the content element, from the site setting or from TypoScript,
    and a number of links below ``1`` falls back to ``5``, the shipped
    default.
*   The TypoScript constant
    :typoscript:`plugin.tx_academicpersons.pagination.resultsPerPage`, and the
    site setting of the same name where a site uses the site set, default to
    ``10`` instead of ``1``.

Impact
======

A paginated profile list with an empty :guilabel:`Results per Page` renders
again, with the number of profiles per page of the site, ``10`` unless the site
sets a value of its own.

A content element whose stored options lack the field took the value of the
site before as well: one saved before the field existed, one whose form hides
the field through page TSconfig, or one written without the backend form, by an
import for example. Such a list shows 10 profiles per page instead of 1, unless
the site sets a value of its own.

Affected Installations
======================

Installations with a profile list or list-and-detail plugin that has
:guilabel:`Pagination` switched on and :guilabel:`Results per Page` or
:guilabel:`Number of Links` empty, and installations that relied on the shipped
default of one profile per page. To keep that, set the constant or the site
setting :typoscript:`plugin.tx_academicpersons.pagination.resultsPerPage` to
``1``.

.. index:: Frontend, TypoScript, ext:academic_persons
