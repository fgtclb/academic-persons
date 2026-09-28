..  _breaking-settings-apply-after-tca-overrides:

================================================
Breaking: Settings apply after the TCA overrides
================================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

The validators of :file:`Configuration/AcademicPersons/Settings.yaml` set
``required`` and ``readOnly`` of a column, and the :yaml:`email` and
:yaml:`number` flags its type, in the TCA of the six person tables. They used to
be applied by the TCA files of :guilabel:`academic_persons` themselves, before
any :file:`Configuration/TCA/Overrides` file of another package ran. They are
now applied once the TCA is compiled, by a listener of the PSR-14 event
:php:`TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent` with the
identifier ``academic-persons/apply-settings-to-tca``. That is after every TCA
override, and the result is what the TCA cache and the TCA schema cache hold.
The identifier is public API, listed on the `extension points page of
academic_base <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Developers/ExtensionPoints/Index.html>`__.
The listener class is not.

Impact
======

**The settings win over a TCA override of the keys they set.** Every field of
the settings writes ``required`` and ``readOnly`` of its column, also when they
are false. A site package that sets either of them on a configured column in
its TCA overrides loses that value. A column the override **replaces as a
whole**, for a label or a rich text configuration of its own, keeps what the
settings say about it now, where the replacement used to drop it silently.

A site package that copies a person column into a table of its own in a TCA
override used to copy it with the ``required`` and ``readOnly`` of the settings,
and now copies it without them.

A field of the settings whose column the TCA does not have no longer adds an
incomplete column without a type to the TCA. Such a field used to stop the TCA
build of the whole installation with ``Missing "type" in TCA of field``.

The TCA listener of :guilabel:`content_blocks` runs before the TCA overrides
and is not affected. The listener of :guilabel:`academic_persons` is ordered
after the identifier ``content-blocks-tca`` in case that extension moves its
listener to the same event.

Affected Installations
======================

Installations whose site package changes ``required`` or ``readOnly`` of a
column of a person table in :file:`Configuration/TCA/Overrides`, or
replaces such a column as a whole, where the settings configure a field for
that column. The tables are
:sql:`tx_academicpersons_domain_model_profile`,
:sql:`tx_academicpersons_domain_model_contract`,
:sql:`tx_academicpersons_domain_model_email`,
:sql:`tx_academicpersons_domain_model_phone_number`,
:sql:`tx_academicpersons_domain_model_address` and
:sql:`tx_academicpersons_domain_model_profile_information`.

Migration
=========

Move the lock or the requirement into the settings of the site package, where
it reaches the backend form and the profile editor alike:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/AcademicPersons/Settings.yaml

    profile:
      title:
        validators:
          - readonly

A value that has to differ from the settings in the backend only is set by a
listener of :php:`AfterTcaCompilationEvent` of the site package, ordered after
``academic-persons/apply-settings-to-tca``:

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/EventListener/KeepTitleEditable.php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

    final class KeepTitleEditable
    {
        #[AsEventListener(
            identifier: 'my-sitepackage/keep-title-editable',
            after: 'academic-persons/apply-settings-to-tca',
        )]
        public function __invoke(AfterTcaCompilationEvent $event): void
        {
            $tca = $event->getTca();
            $tca['tx_academicpersons_domain_model_profile']['columns']['title']['config']['readOnly'] = false;
            $event->setTca($tca);
        }
    }

Flush the TYPO3 caches afterwards, the compiled TCA is cached.

..  index:: Backend, TCA, ext:academic_persons
