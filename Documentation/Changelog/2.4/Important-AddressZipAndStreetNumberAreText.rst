.. _important-ace-841-academic-persons:

=========================================================
Important: Address postcode and street number accept text
=========================================================

Description
===========

The shipped :file:`Configuration/AcademicPersons/Settings.yaml` listed the
:yaml:`number` flag for the :yaml:`zip` and :yaml:`streetNumber` properties of
the :yaml:`physicalAddress` set. The flag turns a field into a TCA
:php:`number` field in the backend record editor and into a number input in
the frontend editing form of ``academic_persons_edit``. A TCA
:php:`number` field stores an integer, so the backend saved ``01067`` as
``1067``, ``12a`` as ``12`` and a postcode such as ``SW1A 2AA`` as ``0``, and
the frontend form refused ``12a`` and ``SW1A 2AA``.

The flag is removed from both properties. They are text fields again, as their
TCA and their :sql:`varchar` columns declare, and they stay required.

Impact
======

Postcodes and street numbers are stored exactly as entered, in the backend
and in the frontend editing form. Values saved before this change are not
repaired: a leading zero that was dropped stays dropped, and a street number
saved as ``12`` instead of ``12a`` has to be corrected by hand.

Affected Installations
======================

Every installation that edits addresses.

An installation that ships its own :file:`Configuration/AcademicPersons/Settings.yaml`
with a :yaml:`validations` map replaces the shipped map as a whole, because
the files are merged by their top-level keys. Such a copy keeps the
:yaml:`number` flag on :yaml:`zip` and :yaml:`streetNumber` until it is removed
there as well. The other sets of the copy are left out here:

..  code-block:: yaml

    validations:
      physicalAddress:
        streetNumber:
          - required
        zip:
          - required

Flush the TYPO3 caches afterwards, the settings are cached.

.. index:: TCA, Frontend, ext:academic_persons
