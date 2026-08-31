.. _breaking-person-records-are-workspace-aware:

============================================
Breaking: Person records are workspace aware
============================================

Description
===========

All nine record tables of this extension now declare :php:`'versioningWS' => true`
in their TCA :php:`ctrl` section:

*   :sql:`tx_academicpersons_domain_model_profile`
*   :sql:`tx_academicpersons_domain_model_profile_information`
*   :sql:`tx_academicpersons_domain_model_contract`
*   :sql:`tx_academicpersons_domain_model_address`
*   :sql:`tx_academicpersons_domain_model_email`
*   :sql:`tx_academicpersons_domain_model_phone_number`
*   :sql:`tx_academicpersons_domain_model_organisational_unit`
*   :sql:`tx_academicpersons_domain_model_function_type`
*   :sql:`tx_academicpersons_domain_model_location`

None of them did before, so none of the records of this extension could be
created or changed in a workspace, and the workspaces module did not offer
them.

Nothing reported the gap. The automatic TCA migration TYPO3 v14 ships for this,
:php:`TcaMigration::addWorkspaceAwarenessToInlineChildren()`, repairs an inline
child only when its parent table is *already* declared workspace aware. Here the
inline parents — :sql:`profile` for contracts and profile information,
:sql:`contract` for addresses, email addresses and phone numbers, and
:sql:`organisational_unit` as a second parent of :sql:`contract` — were
unflagged themselves, so it never fired and no deprecation was logged. TYPO3
v13 carries no such migration at all.

:sql:`function_type` and :sql:`location` are plain select targets of
:sql:`contract` rather than inline children. They are flagged for consistency,
so that an editor can add a function type or a location as part of the same
draft that uses it.

Impact
======

**The database schema changes.**
:php:`\TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema` derives the
:sql:`t3ver_oid`, :sql:`t3ver_wsid`, :sql:`t3ver_state` and :sql:`t3ver_stage`
columns and an index over the first two from the declaration, so every one of
the nine tables needs those columns added.

This is not optional and it does not wait for someone to open a workspace. A
workspace aware table is queried with a :php:`WorkspaceRestriction` in the live
workspace too, so until the database analyzer has run, both of these raise a
database error about the unknown columns:

*   the backend record lists of this extension — :php:`DatabaseRecordList` adds
    the restriction unconditionally, with the backend user's workspace,
    including workspace :php:`0`;
*   the frontend rendering of any **translated** profile — the language overlay
    in :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository` selects the
    overlay record with a :php:`FrontendRestrictionContainer`, which carries the
    restriction by default.

**Editing changes permanently, and running the analyzer does not undo it.** In a
workspace with live editing enabled, a profile edit previously went straight to
live, because TYPO3 permits live editing only for tables that are *not*
workspace aware. It now becomes a workspace version that has to be published.

**Custom queries against these tables have to be adapted.** A plain
:php:`QueryBuilder` selecting from any of the nine tables now sees workspace
versions as ordinary rows and, without a version overlay, will render
unpublished drafts into the live frontend. Code that goes through the Extbase
repositories of this extension is not affected: Extbase adds the constraint and
performs the overlay itself, on TYPO3 v13 and v14 alike.

Affected Installations
======================

Every installation of this extension, and every installation of
:php:`academic_contacts4pages`, whose contact records hang below
:sql:`tx_academicpersons_domain_model_contract` as inline children.

No existing record is touched and no rendered output changes — but the database
analyzer has to run, and until it does the two places named under *Impact* are
broken. Development instances built from a committed database snapshot need the
same treatment.

Projects and extensions that query the nine tables with their own
:php:`QueryBuilder` are affected regardless of whether they use workspaces
today, because a workspace version created later becomes visible to them.

Migration
=========

**Run the database analyzer once after updating**, in the
:guilabel:`Admin Tools > Maintenance` module or with
:bash:`vendor/bin/typo3 extension:setup`.

..  warning::

    **On SQLite the command line path is broken.**
    :bash:`vendor/bin/typo3 extension:setup` reports success, creates the index
    over :sql:`t3ver_oid` and :sql:`t3ver_wsid`, and does **not** add the four
    columns. The database is then left with an index over columns that do not
    exist, and every later schema operation aborts with
    :php:`Doctrine\DBAL\Schema\Index::_addColumn(): Argument #1 ($column)
    must be of type string, null given`. Nothing is printed when it happens.

    This is a TYPO3 Core defect, tracked as `forge issue #110422
    <https://forge.typo3.org/issues/110422>`__ with a fix under review that is
    scheduled for TYPO3 v13.4, v14.3 and main. Until it is released, take the
    schema from a database built with the new state rather than migrating an
    existing one, and check afterwards that the nine tables really carry the
    four columns. Installations on MySQL, MariaDB or PostgreSQL are not
    affected.

For custom queries, add the restriction and the overlay:

..  code-block:: php

    $queryBuilder->getRestrictions()->add(
        GeneralUtility::makeInstance(
            WorkspaceRestriction::class,
            (int)$context->getPropertyFromAspect('workspace', 'id', 0),
        ),
    );

    // ... and per fetched row, before using it:
    $pageRepository->versionOL($table, $row, true);
    if (!is_array($row)) {
        continue;
    }

Note that :php:`versionOL()` keeps the live uid of an overlaid record, so
relations resolved through it — the profiles of a frontend user through
:sql:`tx_academicpersons_feuser_mm`, for instance — still read the live
relation. :sql:`fe_users` is not workspace aware in TYPO3 itself. A relation
*changed* inside a workspace is therefore not part of the preview.

.. index:: Database, TCA, ext:academic_persons
