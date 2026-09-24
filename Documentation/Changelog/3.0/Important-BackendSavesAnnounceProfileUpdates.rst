..  _important-backend-saves-announce-profile-updates:

=================================================
Important: Backend saves announce profile updates
=================================================

Description
===========

A save of a profile through the DataHandler now dispatches
:php:`\FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent`, like a frontend
edit and the frontend user commands did before. That covers the backend form
and any code that writes profiles through the DataHandler, such as an import
running with a backend user.

*   Every live, default-language profile in the datamap of the run is
    announced once, after the whole run is written, with the site of its page
    and the origin ``Backend`` - or ``Import``, when the run is marked as an
    import, see :ref:`feature-profile-update-event-carries-site-and-origin`.
*   Not announced: a save that touches translations only, a save in a
    workspace, commands such as copy, move and localize, a save of only a
    child record such as a contract, and every DataHandler run started from
    inside another one.
*   A profile outside its visibility window - hidden, expired, scheduled or
    restricted to a frontend user group - is announced like any other.
*   The writes of the translation synchronisation and of the profile image
    are never announced a second time.

The image metadata listener of this extension ignores announcements with the
origin ``Backend`` or ``Import``: the DataHandler hook has written the
metadata for those saves already, where a name or the image changed.

Impact
======

The listeners of the event run for backend saves and DataHandler based
imports. With `EXT:academic_persons_edit` installed, the translations of the
profile follow a backend save; its entry "Translations follow backend saves
and imports" describes that, and what happens to the slug.

An import that writes many profiles in one request synchronises every one of
them in that request, one after another. Listeners that want to defer their
work for imports recognise them by the origin ``Import``. The profiles are
loaded through Extbase, and its persistence session keeps every one of them
for the rest of the request: memory grows with the number of profiles an
import writes, and a profile the import had loaded through Extbase before is
announced as that earlier object. Listeners read the database, not the
object.

Affected Installations
======================

Installations with a DataHandler hook of their own that dispatches
:php:`AfterProfileUpdateEvent` after a backend save, often together with a
faked frontend request for the site. The save is announced by this extension
now, and such a hook announces it a second time: remove it.

..  index:: Backend, PHP-API, ext:academic_persons
