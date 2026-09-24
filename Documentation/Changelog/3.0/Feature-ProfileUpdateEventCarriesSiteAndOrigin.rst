..  _feature-profile-update-event-carries-site-and-origin:

=================================================================
Feature: The profile update event carries the site and the origin
=================================================================

Description
===========

:php:`\FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent` carries two more
values next to the profile:

*   :php:`getSite()` returns the site the profile belongs to, when the
    dispatcher knows it, and :php:`null` otherwise. A backend save passes the
    site of the profile's page and a frontend edit the site of its request; the
    commands pass none.
*   :php:`getOrigin()` returns where the update came from, as a case of the new
    enum :php:`\FGTCLB\AcademicPersons\Event\ProfileUpdateOrigin`:
    ``Creation`` and ``Synchronization`` for the frontend user commands,
    ``FrontendEditing`` for `EXT:academic_persons_edit`, ``Backend`` for a
    DataHandler save, ``Import`` for a DataHandler run marked as an import,
    and ``Unknown`` for a dispatcher that passes nothing.

Both are optional constructor arguments, so
:php:`new AfterProfileUpdateEvent($profile)` stays valid and yields no site and
the origin ``Unknown``. The case set of the enum is fixed; a listener may
:php:`match` over it exhaustively.

Import code that writes profiles through the DataHandler marks its run with
the new enum :php:`\FGTCLB\AcademicPersons\DataHandling\ProfileWriteCorrelation`,
after :php:`start()`, which replaces the correlation id:

..  code-block:: php

    $dataHandler->start($datamap, []);
    $dataHandler->setCorrelationId(ProfileWriteCorrelation::Import->create());
    $dataHandler->process_datamap();

Its saves are then announced with the origin ``Import`` instead of
``Backend``, see :ref:`important-backend-saves-announce-profile-updates`.
:php:`ProfileWriteCorrelation::Internal` marks a run that is never announced;
a listener of the event that writes profiles through the DataHandler uses it
for its own runs.

Impact
======

Listeners can tell a backend save from an import and from a frontend edit,
and defer or skip work for some of them. The translation synchronisation of
`EXT:academic_persons_edit` takes the site from the event. Only for an event
without one does it fall back to the site of the global request and then of
the profile's page - except for a backend save or an import, which carry no
site only when the profile's page belongs to none, and are then not
synchronised.

..  index:: PHP-API, ext:academic_persons
