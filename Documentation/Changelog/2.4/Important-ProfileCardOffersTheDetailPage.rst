.. _important-ace-833-academic-persons:

==================================================
Important: The profile card offers the detail page
==================================================

Description
===========

The :guilabel:`Profile Card` links the name of every profile to its detail
view on the page set in :guilabel:`Detail Page`. The page TSconfig of this
extension disabled that field for the card, together with the list, sorting,
pagination and view mode fields the card does not use. An editor could not
choose the detail page of a card. It came from the TypoScript constant
:typoscript:`plugin.tx_academicpersons.detailPid` alone, and without the
constant every name on a card linked to the current page.

The card now offers the :guilabel:`Detail Page` field. The other fields stay
hidden.

Impact
======

Editors can choose the detail page of each card. A card without a chosen page
still links to the page of :typoscript:`plugin.tx_academicpersons.detailPid`.
Existing cards render as before.

Affected Installations
======================

Every installation that uses the :guilabel:`Profile Card`. Nothing has to be
done on update.

To keep the field hidden, set this in the page TSconfig of the site:

..  code-block:: typoscript

    TCEFORM.tt_content.pi_flexform.academicpersons_card.sDEF {
      settings\.detailPid.disabled = 1
    }

.. index:: Backend, TSConfig, ext:academic_persons
