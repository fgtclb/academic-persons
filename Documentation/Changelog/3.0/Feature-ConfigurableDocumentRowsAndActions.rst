..  _feature-configurable-document-rows-and-actions:

===============================================
Feature: Configurable document rows and actions
===============================================

Description
===========

Every entry below :yaml:`documentSections` in
:file:`Configuration/AcademicPersons/Settings.yaml` - the seven timeline entry
types and the contracts - declares what a compact row of that list shows and
which actions it offers:

..  code-block:: yaml

    documentSections:
      publications:
        label: 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.publications.label'
        type: publication
        fieldName: publications
        rowFields:
          - year
          - title
        actions:
          - view
          - down
          - up
          - delete
          - edit

:yaml:`rowFields` is the ordered list of values a row renders. Timeline
entries support ``from``, ``to``, ``year``, ``title`` and ``description``;
the contracts support ``from``, ``to`` and ``position``. :yaml:`actions` is
the ordered list of per-row actions: ``view``, ``down``, ``up``, ``delete``
and ``edit``. An action that is not listed is not offered, and listing both
``up`` and ``down`` is what enables drag-and-drop sorting of the list.

A section marked :yaml:`readonly: true` offers ``view`` and nothing else,
whatever its :yaml:`actions` list says, and does not allow creating a record.
Unknown values and duplicates in either list are discarded; both lists are
matched without regard to case.

The typed :php:`FGTCLB\AcademicPersons\Settings\DocumentSection` carries the
normalised lists and answers the capability questions -
:php:`allowsAction()`, :php:`getAllowedActions()`, :php:`allowsCreate()`
and :php:`allowsDragSorting()` - so the editing frontend of
:composer:`fgtclb/academic-persons-edit` renders and offers exactly what the
file declares.

The lists are enforced on both sides. The rendered buttons come from them, and
so does the answer of every write endpoint of the editing frontend: an action a
section does not list is refused with HTTP 403 no matter how the request was
made. That includes the addresses, e-mail addresses and phone numbers of a
contract, which have no section of their own and follow the :yaml:`actions`
list and the :yaml:`readonly` flag of the ``contracts`` section.

The shipped timeline descriptions declare ``editor.type: ckeditor``, which
marks the field as rich text (the ``html`` flag) and carries a readable-text
limit of 500 characters, so the editing frontend renders the rich text editor
and enforces the limit with the same sanitisation contract as the rich text
fields of the profile.

Impact
======

The shipped sections list every row field and action that makes sense for
them. A site package that overrides :yaml:`documentSections` restates the two
lists for every section it declares - a section without :yaml:`actions`
offers no action at all. Flush the TYPO3 caches after changing the file.

..  index:: Configuration, Frontend, ext:academic_persons
