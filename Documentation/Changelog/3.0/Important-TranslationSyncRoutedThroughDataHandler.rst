.. _important-1788193381:

=============================================================
Important: Translation sync is routed through the DataHandler
=============================================================

Description
===========

:php:`\FGTCLB\AcademicPersons\Service\RecordSynchronizer` — the service behind
the ``AfterProfileUpdateEvent`` listener that keeps profile translations in
sync — previously wrote its translations with raw SQL. That implementation had
drifted a long way from what a translation write in TYPO3 involves.

**The synchronisation only ever touched the top-level row.** The recursion into
inline children was dead code since version 2.1.0: a contract, address, email or
phone number was never translated along with its profile, and a contract added
after the translation existed was never carried over. File references and MM
relations (the profile image, related frontend users) were skipped even by
design, and ``l10n_diffsource`` was left empty, so the backend diff view had
nothing to show.

**Every write was a live row, regardless of the acting workspace** — an
unpublished draft leaked into the live site the moment anything triggered a
synchronisation from within a workspace.

The service now routes every write through the TYPO3 :php:`DataHandler`. A
missing translation is created with a ``localize`` command, which carries the
full inline child tree, file references, MM relations and the diff source. For
an existing translation, the current values of the ``l10n_mode=exclude``
columns are re-submitted as a datamap, so core's ``DataMapProcessor``
propagates them, and an ``inlineLocalizeSynchronize`` command per inline
column carries children added later — including their own children.

Impact
======

**Child records are actually synchronised again.** A profile synchronisation
now translates contracts and their children, file references and MM relations,
as it did before 2.1.0. Note that this also re-activates the known
contacts4pages behaviour where a translated contact keeps pointing at an
untranslated default-language page.

**Writes are workspace aware.** A backend user acting in a workspace produces
versioned rows only (``t3ver_wsid``, ``t3ver_state``); the live site is
untouched until the workspace is published. A frontend-triggered
synchronisation acting in a non-live workspace is refused entirely and logs a
notice instead of writing anything.

Because the writes go through the DataHandler they now also update the
reference index and record history, fire hooks, and bump ``tstamp`` on
updated translations.

Affected Installations
======================

Every installation that enables the translation synchronisation through the
``profile.allowedLanguages`` setting of EXT:academic_persons_edit, or that
dispatches ``AfterProfileUpdateEvent`` from its own hooks.

.. index:: Backend, Database, ext:academic_persons
