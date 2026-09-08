.. _feature-modify-profile-image-metadata-event:

=======================================================
Feature: Decide what a profile image's metadata will be
=======================================================

Description
===========

:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent` announces
the metadata this extension is about to write for the image of a profile, and a
listener decides what is written. Whatever it leaves in :php:`getMetadata()` is
the field map that reaches the database; an empty map writes nothing.

The event is dispatched for each of the two records that carry image metadata,
and :php:`getTargetTable()` says which one is being written:

*   :sql:`sys_file_metadata`, the record of the file itself, written once by
    the frontend upload that created the file and only for the fields it found
    empty — :sql:`title`, :sql:`alternative` and, where
    :composer:`typo3/cms-filemetadata` adds it, :sql:`copyright`. This is the
    record an installation requiring file attributes reads, which is why the
    :php:`File` is handed over rather than only its uid.

*   :sql:`sys_file_reference`, the profile's own relation row, written whenever
    the name of the profile record changes — from a backend save, a
    localization or a frontend edit.

Both records are handed over either way: :php:`getFile()` is the file, whose
own metadata record is :php:`$event->getFile()->getMetaData()`, and
:php:`getFileReference()` the image relation of the profile.
:php:`getRequest()` is the request the write happens in, and :php:`null` where
there is none — on the command line, for instance.

Fields the target table does not declare are dropped before the write, so a
listener may set :sql:`copyright` unconditionally: without
:composer:`typo3/cms-filemetadata` the column does not exist and the value goes
nowhere. System fields are dropped as well, with a warning in the log: the
identity of the record, the relation it is part of, its localization, its
workspace and its enable columns are the :php:`DataHandler`'s, and this event
writes metadata.

..  code-block:: php

    #[AsEventListener(identifier: 'my-extension/add-image-copyright')]
    public function __invoke(ModifyProfileImageMetadataEvent $event): void
    {
        if ($event->getTargetTable() !== 'sys_file_metadata') {
            return;
        }
        $metadata = $event->getMetadata();
        $metadata['copyright'] = $metadata['alternative'] ?? '';
        $event->setMetadata($metadata);
    }

Impact
======

Nothing changes without a listener: the composed name of the profile record is
written to the columns named above. With one, a project fills the columns its
own installation adds and requires — :sql:`right_of_use` of
:composer:`fgtclb/file-required-attributes`, a copyright composed differently,
a caption of its own.

A listener runs inside the write of the profile record, for a backend save from
within a :php:`DataHandler` hook. It has to be short, and it must not write
profile records itself.

.. index:: FAL, PHP-API, ext:academic_persons
