..  _breaking-contract-publish-field-removed:

=======================================================
Breaking: The contract field "publish" has been removed
=======================================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

A contract had a toggle :guilabel:`Show this contract online?`, stored in the
column :sql:`publish` of :sql:`tx_academicpersons_domain_model_contract`. No
public view has ever read it: a contract whose toggle was off was shown like
any other. Contracts have a visibility that works, their :sql:`hidden` field.
Every public view leaves a hidden contract out, the frontend editor shows and
hides a contract with the hide action of its row, and
:guilabel:`Show hidden records` of the selected contracts element keeps
showing hidden contracts where an internal directory needs them.

The second switch is removed:

*   the column :sql:`publish` from :file:`ext_tables.sql`,
*   the TCA column and its place in the ``general`` palette,
*   :php:`\FGTCLB\AcademicPersons\Domain\Model\Contract::$publish` with
    :php:`setPublish()`, :php:`isPublish()` and :php:`getPublish()`,
*   the contract field ``publish`` of
    :file:`Configuration/AcademicPersons/Settings.yaml`,
*   the labels ``tx_academicpersons_domain_model_contract.columns.publish.label``
    of :file:`locallang_tca.xlf` and ``helptext.contracts.publish`` of
    :file:`locallang.xlf`.

:composer:`fgtclb/academic-persons-edit` removes the switch from its contract
form in the same release.

Impact
======

**Nothing that is rendered changes.** The flag had no effect, and the
visibility of every contract stays as it is.

Code of a project that used the flag breaks:

*   Calling :php:`setPublish()`, :php:`isPublish()` or :php:`getPublish()` on a
    contract is a fatal error, for example in an importer that creates
    contracts.
*   A template that reads ``{contract.publish}`` gets an empty value, so a
    condition on it is false for every contract.
*   A query that selects or filters :sql:`publish` fails once the column is
    dropped.
*   A :php:`DataHandler` data map that writes ``publish`` loses the value
    without an error, since the column is not in the TCA any more.
*   A site package that copied the settings map before 3.0 still names
    ``contracts.fields.publish``. The field is left out of the frontend editor,
    and a warning in the log names the key to remove. It is logged when the
    settings are built again, for example after the caches were flushed. A
    ``managedFields`` map that lists ``publish`` for ``contracts`` is refused
    with the error of any name that is not a field of ``contracts.fields``.

The database analyzer offers the column for removal. It never drops it on its
own.

Affected Installations
======================

Installations whose own code reads or writes the contract field
:sql:`publish`, through the model, a query, a :php:`DataHandler` data map, a
template, the settings or a TCA override.

Migration
=========

Use the visibility of the contract instead: :php:`setHidden()` and
:php:`getHidden()` of the model, :sql:`hidden` in a query or a data map.

A project whose own code gave the flag a meaning, and hid contracts that were
not published, carries it over with the upgrade wizard
``academicPersons_migrateContractPublishToHidden`` before the column is
dropped. The wizard is **not registered by default**, since on every other
installation it would hide all contracts. How to register and run it is
described in :ref:`important-contract-publish-to-hidden-wizard`.

A project that never gave the flag a meaning has nothing to migrate. It removes
what it wrote for the flag, such as a TCA default, a model property of its own
or the value its importer sets, and lets the analyzer drop the column.

Remove ``contracts.fields.publish`` from the
:file:`Configuration/AcademicPersons/Settings.yaml` of the site package, and
``publish`` from a ``managedFields`` map.
