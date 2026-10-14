..  _breaking-synchronizer-context-namespace-corrected:

=====================================================
Breaking: The synchronizer context namespace is fixed
=====================================================

Description
===========

The data object that describes one translation synchronisation run moved out of
its misspelled namespace, and its property lost the same misspelling:

..  list-table::
    :header-rows: 1

    *   -   Before
        -   Since 3.0
    *   -   :php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext`
        -   :php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\Synchronizer\SynchronizerContext`
    *   -   :php:`SynchronizerContext::$recordSyncronizer`
        -   :php:`SynchronizerContext::$recordSynchronizer`
    *   -   the named argument :php:`recordSyncronizer:` of the constructor and
            of :php:`SynchronizerContext::create()`
        -   :php:`recordSynchronizer:`

:php:`\FGTCLB\AcademicPersons\Service\RecordSynchronizerInterface` takes the
context under its new name. No class alias is registered for the old one: the
property and the named argument cannot be bridged, so an alias would keep half
of the old code working and let the other half fail later.

The listener
:php:`\FGTCLB\AcademicPersonsEdit\EventListener\SyncChangesToTranslations` of
:php:`EXT:academic_persons_edit` carries the same misspelling. Its constructor
argument and promoted property :php:`$recordSyncronizer` are renamed to
:php:`$recordSynchronizer` as well. The class is final, so only code that
creates the listener itself is affected.

Impact
======

Code that names the old class, reads the old property or passes the old named
argument fails with an error, the named argument :php:`recordSyncronizer:` of
the listener constructor included. The translation synchronisation of the
profile editor works as before, on TYPO3 v13 and v14.

Affected Installations
======================

Installations with their own implementation of
:php:`RecordSynchronizerInterface`, which is marked internal and experimental,
with code that builds or reads a :php:`SynchronizerContext`, or with code that
creates :php:`SyncChangesToTranslations` with named arguments. Search the site
package for the misspelling:

..  code-block:: bash

    grep -rn "yncronizer" packages/my_sitepackage/

Migration
=========

Replace :php:`Dto\Syncronizer\` with :php:`Dto\Synchronizer\` in the imports,
:php:`->recordSyncronizer` with :php:`->recordSynchronizer`, and the named
argument :php:`recordSyncronizer:` with :php:`recordSynchronizer:`, for the
context and for the listener.

..  index:: PHP-API, ext:academic_persons, ext:academic_persons_edit
