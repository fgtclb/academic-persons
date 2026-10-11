..  _feature-restrict-the-backend-contract-selects:

==============================================
Feature: Restrict the backend contract selects
==============================================

Description
===========

Two backend fields let an editor pick a contract: the :guilabel:`Contract` of
a page contact record of :composer:`fgtclb/academic-contacts4pages`, and
:guilabel:`Selected Contracts` of the :guilabel:`Profiles: Selected Contracts`
content element. Both offer every contract of the installation. In an
installation with more than one site that is every site's contracts, each
labelled with a person's name.

Page TSconfig now restricts a field to the pages its contracts are stored on:

..  code-block:: typoscript
    :caption: Page TSconfig of a site root

    # The "Contract" field of a page contact record.
    TCEFORM.tx_academiccontacts4pages_domain_model_contact.contract.itemsProcFunc {
        storagePids = 42,84
        recursive = 1
    }

    # The "Selected Contracts" field of the content element.
    TCEFORM.tt_content.pi_flexform.academicpersons_selectedcontracts.sDEF.settings\.selectedContracts.itemsProcFunc {
        storagePids = 42,84
        recursive = 1
    }

:typoscript:`storagePids` is a comma-separated list of page uids and
:typoscript:`recursive` the number of levels below each of them to include. A
hidden folder is included as well, because a storage folder is regularly
hidden.

A contract the edited record already references stays selectable wherever it is
stored, so that opening and saving a record never drops a relation it has.

Impact
======

The setting is opt-in and without it the selects offer what they offered
before, so an installation that does not configure it is unaffected.

Respecting the Extbase storage page instead is deliberately not done: a page
tree without an explicitly configured storage page would get an empty select
and no error.

The restriction is applied before
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` is dispatched,
so a listener of that event still has the last word, including one that adds
items back.

Affected Installations
======================

Installations with contracts in more than one storage folder or site, whose
editors pick contracts in the backend.

..  index:: Backend, TSConfig, ext:academic_persons, ext:academic_contacts4pages
