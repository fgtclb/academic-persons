..  _important-profile-information-year-range:

===================================================
Important: The timeline year fields are constrained
===================================================

Description
===========

The three year columns of
:sql:`tx_academicpersons_domain_model_profile_information` -
:sql:`year`, :sql:`year_start` and :sql:`year_end` - declared

..  code-block:: php

    'config' => [
        'type' => 'number',
        'min' => 0,
        'max' => 9999,
        'nullable' => true,
    ],

**and enforced none of it.** :yaml:`min` and :yaml:`max` are options of the
TCA type :php:`input`; the type :php:`number` reads its bounds from
:yaml:`range` alone. So the backend form rendered a number field without an
HTML :html:`min` or :html:`max` attribute, and :php:`DataHandler` clamped
nothing on save.

The three columns now declare

..  code-block:: php

    'config' => [
        'type' => 'number',
        'format' => 'integer',
        'range' => [
            'lower' => 0,
            'upper' => 9999,
        ],
        'nullable' => true,
    ],

which is what renders the HTML bounds and what :php:`DataHandler` clamps a
submitted value against; :file:`ext_tables.sql` declares the three columns
``int(11) unsigned DEFAULT NULL``, what the corrected TCA derives. The
palette, the labels, the property names and the frontend rendering are
unchanged.

Impact
======

The backend record editor now keeps a year within ``0``-``9999``: a value
above the upper bound is clamped to ``9999`` on save, a negative one to ``0``.
:sql:`NULL` stays the empty value.

:file:`ext_tables.sql` declares the three columns as
:sql:`int(11) unsigned DEFAULT NULL`, matching what the corrected TCA derives.
The database analyzer therefore offers the change of signedness. An
installation that stored a negative year - which nothing in the extension ever
wrote - has to correct those rows before applying it.

Affected Installations
======================

Every installation of :composer:`fgtclb/academic-persons` that edits profile
information records in the TYPO3 backend.

..  index:: Backend, Database, TCA, ext:academic_persons
