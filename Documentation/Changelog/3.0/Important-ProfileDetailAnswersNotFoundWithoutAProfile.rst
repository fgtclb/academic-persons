.. _important-profile-detail-answers-not-found-without-a-profile:

===================================================================
Important: The profile detail answers "not found" without a profile
===================================================================

Description
===========

The detail view of the :guilabel:`Persons Detail` and the
:guilabel:`Persons List and Detail` content elements answered a request
without a profile it can show with the error page of the site, but rendered
that error page into the content element, inside the page. On TYPO3 v13 the
page was sent with the status ``200``, on TYPO3 v14 with ``404``. The detail
view now ends the request with the "page not found" handling of the site on
both versions: the answer is the error page alone, with the status ``404``.

Impact
======

A request of the detail page without a profile, with a profile that does not
exist or with a hidden one ends with the error page the site configures for
``404``, or the default error page of TYPO3 when it configures none. The rest of
the page is no longer rendered. A link to the detail page in a menu, which
carries no profile, leads to that error page. Hide the detail page in menus.

Affected Installations
======================

Every installation rendering the :guilabel:`Persons Detail` or the
:guilabel:`Persons List and Detail` content element.

.. index:: Frontend, ext:academic_persons
