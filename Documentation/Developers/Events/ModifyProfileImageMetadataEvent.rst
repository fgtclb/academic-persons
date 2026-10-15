..  _developers-event-modify-profile-image-metadata-event:

..  _developers-image-metadata:

===============================
ModifyProfileImageMetadataEvent
===============================

:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent` is
dispatched immediately before this extension writes the metadata of a profile
image, and a listener decides what is written: whatever it leaves in
:php:`getMetadata()` is the field map that goes to the database, and an empty
map writes nothing at all.

When it is dispatched
=====================

It is dispatched for each of the two records that carry image metadata, and
:php:`getTargetTable()` says which one:

..  list-table::
    :header-rows: 1

    *   - :php:`getTargetTable()`
        - Written when
        - Fields
    *   - :sql:`sys_file_metadata`
        - a profile image is uploaded in the frontend, once, for the file that
          upload created, and only for the fields that record has empty
        - :sql:`title`, :sql:`alternative` and, with
          :composer:`typo3/cms-filemetadata`, :sql:`copyright`
    *   - :sql:`sys_file_reference`
        - a DataHandler save writes the name or the image of a profile record:
          a backend save, a localization, an import or the translation
          synchronisation. And after every profile update the frontend
          editing or the frontend user commands announce, whatever changed
        - :sql:`title` and :sql:`alternative`

The upload of the frontend editing of
:composer:`fgtclb/academic-persons-edit` dispatches the event for the file
first and then for the reference. A profile without an image gets no event,
and the upload for a profile without a name gets none for the file. The
default values are the composed name of the profile record: title, first,
middle and last name.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   What it does
    *   -   :php:`getTargetTable()`
        -   :sql:`sys_file_metadata` or :sql:`sys_file_reference`, the record
            that is written.
    *   -   :php:`getMetadata()`, :php:`setMetadata()`
        -   The fields that are written, column name to value.
    *   -   :php:`getFile()`
        -   The file the image is stored in.
    *   -   :php:`getFileReference()`
        -   The image relation of the profile.
    *   -   :php:`getProfileUid()`
        -   The uid of the profile record the image belongs to.
    *   -   :php:`getRequest()`
        -   The request the write happens in, or :php:`null`.

**Both records are handed over, whichever of them is written.**
:php:`getFile()` is the file, whose own metadata record is
:php:`$event->getFile()->getMetaData()`, and :php:`getFileReference()` is the
image relation of the profile, :php:`null` only for a profile that has none.
:php:`getProfileUid()` is the profile record the image belongs to, a
translation for an image of its own, and :php:`getRequest()` the request the
write happens in: the frontend request for an upload or a frontend edit, the
backend request for a save, and :php:`null` on the command line or where the
caller has no request to pass on.

Fields the target table does not declare are dropped before the write, so a
listener may set a column unconditionally: where the installation does not have
it, the value goes nowhere. :sql:`copyright` is one such column. It belongs to
:sql:`sys_file_metadata` and :composer:`typo3/cms-filemetadata`, and the
relation row has no equivalent.

Example
=======

An installation that requires a note on the right of use for every image, with
:composer:`fgtclb/file-required-attributes`, fills it when a profile image is
uploaded:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddImageRightOfUse.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddImageRightOfUse
    {
        #[AsEventListener(identifier: 'my-extension/add-image-right-of-use')]
        public function __invoke(ModifyProfileImageMetadataEvent $event): void
        {
            if ($event->getTargetTable() !== 'sys_file_metadata') {
                return;
            }
            $metadata = $event->getMetadata();
            $metadata['right_of_use'] = 'Portrait, own use only';
            $event->setMetadata($metadata);
        }
    }

Rules and pitfalls
==================

..  warning::

    **System fields are refused.** The identity of the record, the relation it
    is part of, its localization, its workspace columns and its enable columns
    are dropped and a warning is written to the log: :sql:`uid`, :sql:`pid`,
    :sql:`file`, :sql:`uid_local`, :sql:`uid_foreign`, :sql:`tablenames`,
    :sql:`fieldname`, :sql:`sys_language_uid`, :sql:`l10n_parent`,
    :sql:`t3ver_*`, :sql:`deleted`, :sql:`hidden` and the rest of their kind. This
    event writes metadata. Repointing a relation or moving a record is the
    :php:`DataHandler`'s business.

The two dispatches are not interchangeable. The metadata record of the file is
written **once**, which makes it the place for a value that has to survive,
the required attributes of :composer:`typo3/cms-filemetadata` or
:composer:`fgtclb/file-required-attributes`, for instance. The reference row is
rewritten on **every** save of the profile, so a listener that wants to own a
field there has to set it on every dispatch. One request may write the
reference more than once, an upload in the default language does, so a
listener sets the same value each time it is called.

..  warning::

    A listener runs inside the write of the profile record, for a backend save
    from within a :php:`DataHandler` hook. Keep it short and do not write
    profile records from it.

When the file or the relation cannot be loaded, the metadata is written without
the event, and a warning is logged.

Introduced in
=============

*   :ref:`feature-modify-profile-image-metadata-event` introduced the event.
*   :ref:`important-backend-saves-announce-profile-updates` describes why a
    backend save writes the reference through the DataHandler hook only.
