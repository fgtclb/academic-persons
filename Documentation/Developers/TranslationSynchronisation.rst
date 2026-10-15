..  _developers-translation-synchronisation:

===========================
Translation synchronisation
===========================

..  warning::

    **The translation synchronisation is experimental.**
    :php:`RecordSynchronizerInterface` and :php:`RecordSynchronizer` are marked
    :php:`@internal`, and the rest of this page, :php:`SynchronizerContext`
    among it, is to be treated the same way. It works and is covered by
    functional tests, but signatures may still change in a minor release.
    Depend on it deliberately. The events are public API.

:composer:`fgtclb/academic-persons-edit` keeps the translations of a profile
in step with its default language record. Its listener of
:ref:`developers-event-after-profile-update-event` calls the synchronizer of
this extension for the languages its ``profile.allowedLanguages`` setting
allows.

..  _developers-synchronisation:

The synchronisation surface
===========================

:php:`\FGTCLB\AcademicPersons\Service\RecordSynchronizerInterface` declares one
method, :php:`synchronize(SynchronizerContext $context)`. The shipped
implementation (:php:`RecordSynchronizer`) routes **every write through the
TYPO3 DataHandler**. Nothing in TYPO3 outside the DataHandler honours
:php:`l10n_mode=exclude` or keeps translations consistent, so going through it
is what makes the created translations indistinguishable from ones created in
the backend: inline children, file references, MM relations,
``l10n_diffsource``, reference index, history and hooks are all carried along.

:php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\Synchronizer\SynchronizerContext`
describes one synchronisation run. Build it through
:php:`SynchronizerContext::create()`, which takes the synchronizer instance,
the :php:`Site`, the allowed language ids, the table name and the record uid,
and silently drops language ids that are not positive or that the site does not
define, so a run never targets a language the site cannot render.

For each remaining language, :php:`synchronize()`:

*   creates a missing translation with a DataHandler ``localize`` command,
    the full record, including its inline child tree,
*   for an existing translation, re-submits the default record's
    :php:`l10n_mode=exclude` column values as a datamap (core's
    ``DataMapProcessor`` propagates them into every translation) and issues an
    ``inlineLocalizeSynchronize`` command per inline column, which carries
    child records added to the default record after the translation was
    created.

A missing record, a record that is not in the default language, or a record
that is invisible in the acting workspace makes the run a silent no-op.

..  _developers-workspaces:

Workspace behaviour
===================

The synchronisation acts **in the workspace of the acting backend user**: run
from a backend context inside a workspace, it creates versioned rows only
(``t3ver_wsid`` set, ``t3ver_state=1``) and never touches the live records.
Publishing the workspace publishes the translations. When no backend user is
available (frontend and CLI contexts), a synthetic in-memory admin user acting
in the workspace of the current :php:`Context` is used.

Two refusals protect the live state:

*   A **frontend request acting in a non-live workspace** (a workspace preview)
    is refused entirely. A notice is logged and nothing is written. This
    policy is currently hardcoded.
*   A uid addressing a **workspace version row** (``t3ver_oid > 0``) is
    refused: the DataHandler addresses versioned records through their live
    uid, and accepting the version uid would publish draft values as live
    translations.

