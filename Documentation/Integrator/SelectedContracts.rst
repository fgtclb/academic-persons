..  _integrator-selected-contracts:

===========================
Selected Person’s Contracts
===========================

..  image:: /Images/Icons/selected-profiles.svg
    :width: 48px
    :alt: Icon of the selected contracts

:CType:
    ``academicpersons_selectedcontracts``

:Plugin:
    `AcademicPersons`, plugin `SelectedContracts`, action
    :php:`ProfileController::selectedContractsAction()`

:Set:
    `fgtclb/academic-persons-selected-contracts`, or the aggregate set
    `fgtclb/academic-persons`

:Templates:
    :file:`Templates/Profile/SelectedContracts.html`, see
    :ref:`templates`

The contracts an editor picks, in the order they are picked, one item or one
table row each, with the name of its profile. It is the element for a person
who holds several roles and should appear in one of them only: the dean on the
page of the dean's office, without the professorship, or the contact person of
a service with the room and phone number of that role.

The element shows the picked contract, whatever its validity, and none of the
other contracts of the profile. The names link to the detail page, which shows
the whole profile.

..  figure:: /Images/Screenshots/selected-contracts-default.png
    :alt: Three tiles with linked name, one position and e-mail each
    :class: with-border with-shadow

    Three picked contracts, each with the name of its profile and only that
    contract.

..  _integrator-selected-contracts-configuration:

Settings
========

..  confval:: Detail Page
    :name: selected-contracts-detail-pid
    :type: page
    :Default: empty

    The page the names link to, see :confval:`Detail Page
    <person-list-detail-pid>`.

..  confval:: Link the names to the detail view
    :name: selected-contracts-detail-link
    :type: select
    :Default: Use the site setting

    See :confval:`Link the names to the detail view <person-list-detail-link>`.

..  confval:: Selected Contracts
    :name: selected-contracts-selected-contracts
    :type: select, several values
    :Default: empty

    The contracts the element shows, in the order of the right-hand list. The
    left-hand list offers every contract of the installation, ordered by the
    name of the person and labelled with the last and first name, the
    function type, the organisational unit and the validity, for example
    ``Doe, Jane / Professor / Faculty of Engineering / 2020-04-01--``. Empty
    shows the text that no contract was found.

    In an installation with more than one site, page TSconfig restricts the
    offered contracts to the storage folders of a page tree, see
    :ref:`configuration-contract-select-storage-scope`. A listener of
    :php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` can change
    the items as well.

..  confval:: Show only selected Fields
    :name: selected-contracts-show-fields
    :type: select, several values
    :Default: empty

    The fields each item shows, see :confval:`Show only selected Fields
    <person-list-show-fields>`. The contract fields are those of the picked
    contract.

..  confval:: View Mode Toggle
    :name: selected-contracts-view-mode-toggle
    :type: checkbox
    :Default: off

    See :confval:`View Mode Toggle <selected-profiles-view-mode-toggle>`. Two
    elements of this kind on one page share the chosen mode.

..  confval:: View Mode Default
    :name: selected-contracts-view-mode-default
    :type: select
    :Default: Tiles

    See :confval:`View Mode Default <person-list-view-mode-default>`. The table
    has one row per contract.

..  figure:: /Images/Screenshots/selected-contracts-table.png
    :alt: View mode switch with Table active above a table with one row
    :class: with-border with-shadow

    The table view mode of the selected contracts element: one row per
    contract.

..  confval:: Show hidden records
    :name: selected-contracts-show-hidden-records
    :type: checkbox
    :Default: off

    Shows a picked contract whose :guilabel:`Visible` switch is off as well,
    for an internal directory. See :ref:`configuration-hidden-contracts`.
