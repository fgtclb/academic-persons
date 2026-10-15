..  _integrator:

===============
For integrators
===============

This extension ships six content elements. All six are driven by one Extbase
plugin, so they share the TypoScript block
:typoscript:`plugin.tx_academicpersons` and the site settings of the set
`fgtclb/academic-persons`, see :ref:`configuration`. What differs per element
is its plugin options: the fields an editor fills in on the tab
:guilabel:`Configuration` of the content element. This chapter describes them,
one page per content element, and what each value does in the frontend.

Which of the elements the backend offers is decided by the sets or the page
TSconfig a site includes, see :ref:`configuration-components`.

..  _integrator-flexform-and-settings:

Plugin options and site settings
================================

A plugin option the editor saved wins over the site setting or the TypoScript
setting of the same name, and that includes an empty value: a field that was
saved empty replaces the default of the site. A site setting such as
:guilabel:`Group by`, :guilabel:`Sort by` or :guilabel:`Pagination | Results
per page` therefore only reaches a content element whose stored options lack
the key: one saved before the field existed, one whose form hides the field
through page TSconfig, or one written without the backend form, by an import
for example. An element that leaves :guilabel:`Group By` out that way is
grouped by the first letter of the last name, the default of the site setting.

Four options are the exception. While they are empty, the value of the site
applies:

..  list-table::
    :header-rows: 1

    *   -   Option
        -   While it is empty
    *   -   :guilabel:`Detail Page`
        -   The site setting :typoscript:`plugin.tx_academicpersons.detailPid`,
            ``0`` by default, which links to the page of the element.
    *   -   :guilabel:`Link the names to the detail view`
        -   The site setting :typoscript:`plugin.tx_academicpersons.detailLink`,
            see :ref:`configuration-detail-link`.
    *   -   :guilabel:`PageTitle display format`
        -   :typoscript:`plugin.tx_academicpersons.settings.pageTitleFormat`
            when a site sets it in TypoScript. None is shipped, so the built-in
            format applies.
    *   -   :guilabel:`Show only selected Fields`
        -   :typoscript:`plugin.tx_academicpersons.settings.showFields` when a
            site sets it in TypoScript, a comma separated list of the field
            values. None is shipped, so the shipped selection of fields is
            shown, see :confval:`Show only selected Fields
            <person-list-show-fields>`.

..  _integrator-content-elements:

The content elements
====================

..  list-table::
    :header-rows: 1
    :widths: 10 25 25 40

    *   -   Icon
        -   Content element
        -   CType
        -   What it shows
    *   -   ..  image:: /Images/Icons/persons.svg
                :width: 32px
                :alt: Icon of the profile list
        -   :ref:`Person List <integrator-person-list>`
        -   ``academicpersons_list``
        -   A directory of profiles, with grouping, letter navigation,
            pagination, visitor filters and view modes. The names link to a
            detail page.
    *   -   ..  image:: /Images/Icons/persons.svg
                :width: 32px
                :alt: Icon of the profile list and detail
        -   :ref:`Person List and Details <integrator-person-list-and-details>`
        -   ``academicpersons_listanddetail``
        -   The same directory, and the profile a visitor opens from it on the
            same page.
    *   -   ..  image:: /Images/Icons/persons.svg
                :width: 32px
                :alt: Icon of the profile detail
        -   :ref:`Person Details <integrator-person-details>`
        -   ``academicpersons_detail``
        -   The public profile of one person, on the detail page the other
            elements link to.
    *   -   ..  image:: /Images/Icons/card.svg
                :width: 32px
                :alt: Icon of the contact card
        -   :ref:`Person Contact Card <integrator-person-contact-card>`
        -   ``academicpersons_card``
        -   Contact cards of a few profiles an editor picks, for a sidebar or
            a contact box.
    *   -   ..  image:: /Images/Icons/selected-profiles.svg
                :width: 32px
                :alt: Icon of the selected profiles
        -   :ref:`Selected Person’s Profiles <integrator-selected-profiles>`
        -   ``academicpersons_selectedprofiles``
        -   Profiles an editor picks, as tiles or a table, with their
            contracts.
    *   -   ..  image:: /Images/Icons/selected-profiles.svg
                :width: 32px
                :alt: Icon of the selected contracts
        -   :ref:`Selected Person’s Contracts <integrator-selected-contracts>`
        -   ``academicpersons_selectedcontracts``
        -   Contracts an editor picks, one item per contract, for a person who
            should appear in one role only.

The elements share their templates and partials, see :ref:`templates`, and
the classes of their markup, see :ref:`styling`.

..  toctree::
    :hidden:
    :titlesonly:

    PersonList
    PersonListAndDetails
    PersonDetails
    PersonContactCard
    SelectedProfiles
    SelectedContracts
