..  _important-contract-publish-to-hidden-wizard:

===================================================================
Important: The contract publish wizard has to be registered by hand
===================================================================

Description
===========

..  warning::
    Do not register the wizard on an installation whose own code never gave the
    contract field :sql:`publish` a meaning. Every contract of such an
    installation carries the default "not published", and the wizard hides
    **all of them**.

:ref:`breaking-contract-publish-field-removed` removes the contract field
:sql:`publish` in favour of the visibility of the contract. The upgrade wizard
``academicPersons_migrateContractPublishToHidden`` carries the flag over: a
contract that was not published is hidden. It is meant for a project whose own
code honoured the flag, for example a template or a query that left
unpublished contracts out.

The wizard ships **unregistered**. The core does not list it in
:guilabel:`Admin Tools > Upgrade > Upgrade Wizard`, and
:bash:`vendor/bin/typo3 upgrade:run` does not run it. A project that wants it
declares the class in the :file:`Configuration/Services.yaml` of its site
package:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/Services.yaml

    services:
      FGTCLB\AcademicPersons\Upgrades\MigrateContractPublishToHiddenUpgradeWizard:
        autowire: true
        autoconfigure: true

``autoconfigure`` registers it under its identifier, on TYPO3 v13 and v14.
Flush the caches afterwards, so the service container is built again.

The order
=========

#.  Update the database schema without removing anything. The analyzer offers
    :sql:`publish` for removal, do not accept that yet.
#.  Register the wizard as above and flush the caches.
#.  Run it:

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicPersons_migrateContractPublishToHidden

#.  Flush the frontend caches. The wizard writes the database directly, so a
    page cached before still shows the contracts it hid.

#.  Remove the code of the project that read or wrote the flag, and the
    registration of the wizard.
#.  Let the analyzer drop the column.

The analyzer renames a column to :sql:`zzz_deleted_publish` before it drops it.
The wizard reads that name as well, so a renamed column is still migrated. Once
the column is dropped, the flag is gone and nothing can be migrated any more.

What the wizard does
====================

*   A contract that was not published is hidden. A published contract keeps its
    visibility, hidden or not.
*   The contract in the default language decides, and its translations follow
    it. :sql:`hidden` is shared by every language of a contract, so a
    translation that was not published while its default language contract was
    stays visible. The flag of a translation is not read, unless the translation
    has no default language contract, then it decides for itself.
*   Live records, workspace versions and deleted records are all migrated, so a
    version that is published later or a record that is restored keeps the
    meaning it had. A workspace version of a translation follows the version of
    its default language contract in the same workspace, and the live contract
    where that workspace has none.
*   It writes the database directly, not through the :php:`DataHandler`, and it
    writes the translations itself: the core copies :sql:`hidden` into the
    translations only when the :php:`DataHandler` saves a contract.
*   Running it again changes nothing.

A contract that was hidden by mistake is shown again in the backend or with the
hide action of the frontend editor.
