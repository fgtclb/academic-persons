..  _important-persons-record-icons-follow-the-colour-scheme:

========================================================
Important: Record icons follow the backend colour scheme
========================================================

Description
===========

The record icons of this extension were registered with the core provider
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`, which renders
the default markup - the markup a :php:`typeicon_classes` entry reaches - as an
:html:`<img>` tag. An image is opaque to CSS, so the icon kept the ink of its
file whatever the backend colour scheme said, and a dark drawing stayed dark on
the dark cards of the record list.

They are now registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
which inlines the file in both markups, and the files themselves are drawn in
`currentColor` with no colour of their own.

That covers the record icons of all nine tables this extension ships, from
:sql:`tx_academicpersons_domain_model_address` to
:sql:`tx_academicpersons_domain_model_profile_information`. Their identifiers
are renamed in the same release, see
:ref:`breaking-persons-public-profile-icons-moved-to-the-frontend-icon-registry`.

Impact
======

The nine record icons take the text colour of the backend, so they stay legible
in a dark colour scheme. Their markup is now the inlined :html:`<svg>` rather
than an :html:`<img>`, which matters to any CSS or test that addressed the
image.

The content element icons are drawn and registered the same way. The seven
control icons of the public profile are frontend icons, the shared icons of
:guilabel:`academic_base` in its frontend icon registry, see
:ref:`breaking-persons-public-profile-icons-moved-to-the-frontend-icon-registry`.

Affected Installations
======================

Every installation of this extension.

.. index:: Backend, ext:academic_persons
