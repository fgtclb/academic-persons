..  _integrator-person-contact-card:

===================
Person Contact Card
===================

..  image:: /Images/Icons/card.svg
    :width: 48px
    :alt: Icon of the contact card

:CType:
    ``academicpersons_card``

:Plugin:
    `AcademicPersons`, plugin `Card`, action
    :php:`ProfileController::cardAction()`

:Set:
    `fgtclb/academic-persons-card`, or the aggregate set
    `fgtclb/academic-persons`

:Templates:
    :file:`Templates/Profile/Card.html`, see :ref:`templates`

Contact cards of the profiles an editor picks, in the order they are picked:
the contact person in the sidebar of a page, or the two people to ask on the
page of a service. Each card is a profile item, as in the tiles of the list,
with the crop variant of the card, see
:ref:`templates-profile-image-crop-variant`. The names link to the detail page.

..  figure:: /Images/Screenshots/person-contact-card-default.png
    :alt: Three profile cards with portrait, linked name and one position each
    :class: with-border with-shadow

    Three contact cards showing the image and the position of the first
    contract.

The card shares its options with the :ref:`Person List
<integrator-person-list>`. The page TSconfig of its component, which comes
with the set `fgtclb/academic-persons-card`, hides the list, sorting,
pagination, view mode and filter fields, because a fixed selection has no use
for them. The card always renders tiles.

..  _integrator-person-contact-card-configuration:

Settings
========

..  confval:: PageTitle display format
    :name: person-contact-card-page-title-format
    :type: text, with a value picker
    :Default: empty

    Has no effect in this element. Set the format on the
    :ref:`Person Details <integrator-person-details>` element of the detail
    page.

..  confval:: Detail Page
    :name: person-contact-card-detail-pid
    :type: page
    :Default: empty

    The page the names link to, see :confval:`Detail Page
    <person-list-detail-pid>`.

..  confval:: Link the names to the detail view
    :name: person-contact-card-detail-link
    :type: select
    :Default: Use the site setting

    See :confval:`Link the names to the detail view <person-list-detail-link>`.
    :guilabel:`No link` is the choice for a card of a person without a public
    profile.

..  confval:: List of profiles
    :name: person-contact-card-list-of-profiles
    :type: records
    :Default: empty

    The profiles the element shows, one card each, in the order they are
    picked. Empty shows the text that no profile was found. The profiles are
    found wherever they are stored.

..  confval:: Fallback to default language for non translated profiles
    :name: person-contact-card-fallback-for-non-translated
    :type: checkbox
    :Default: on

    On a translated page, also shows the picked profiles that are not translated
    into its language, in the default language. See :confval:`Fallback to
    default language for non translated profiles
    <person-list-fallback-for-non-translated>`.

..  confval:: Show only selected Fields
    :name: person-contact-card-show-fields
    :type: select, several values
    :Default: empty

    The fields each card shows, see :confval:`Show only selected Fields
    <person-list-show-fields>`. A card for a sidebar usually shows the image,
    the position and the e-mail addresses and phone numbers.

..  figure:: /Images/Screenshots/person-contact-card-all-fields.png
    :alt: One profile card with portrait, name, position, location, e-mail, phone and address
    :class: with-border with-shadow

    A card with an empty field selection shows the image and every contract
    field except the function type and the organisational unit.

..  confval:: Organisational Units
    :name: person-contact-card-organisational-units
    :type: select, several values
    :Default: empty

    Has no effect in this element on its own. The card shows the profiles it
    is given, and the contract restriction that would read this field is
    hidden. A value of :guilabel:`Only contracts of the selected
    organisational units and function types` stored while the element was a
    list stays in effect, and then this field decides which contracts the card
    shows.

..  confval:: Function Types
    :name: person-contact-card-function-types
    :type: select, several values
    :Default: empty

    The same as :guilabel:`Organisational Units`.

..  confval:: Contracts per profile
    :name: person-contact-card-contracts-display
    :type: select
    :Default: All contracts

    :guilabel:`Only the first contract` keeps a card short for a person with
    several contracts. See :ref:`configuration-contract-display`.

..  confval:: Only contracts valid today
    :name: person-contact-card-contracts-only-valid
    :type: checkbox
    :Default: off

    Leaves out the contracts that have ended or not started yet. A picked
    profile is shown whatever its contracts are. See
    :ref:`configuration-contract-display`.

..  confval:: Show hidden records
    :name: person-contact-card-show-hidden-records
    :type: checkbox
    :Default: off

    Shows a picked profile whose :guilabel:`Visible` switch is off as well.
    See :ref:`configuration-hidden-profiles`.

..  _integrator-person-contact-card-hidden-fields:

Hidden fields
-------------

The page TSconfig of the card disables these fields of the shared options:
:guilabel:`Group By`, :guilabel:`Sort By`, :guilabel:`Sort By Direction`,
:guilabel:`Pagination`, :guilabel:`Alphabetical Pagination`, :guilabel:`Results
per Page`, :guilabel:`Number of Links`, :guilabel:`View Mode Toggle`,
:guilabel:`View Mode Default`, :guilabel:`Visitors may filter by function
type`, :guilabel:`Visitors may filter by organisational unit` and
:guilabel:`Only contracts of the selected organisational units and function
types`. The card reads none of them, except the last one, whose stored value
stays in effect. The same page TSconfig is what makes the card selectable, so
wherever an editor can add a card, the fields are hidden.
