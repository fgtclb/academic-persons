..  _important-ace-923-hidden-options-academic-persons:

============================================
Important: Options without effect are hidden
============================================

Description
===========

The :guilabel:`Persons List`, the :guilabel:`Persons List and Detail` and the
:guilabel:`Profile Card` content elements share one set of plugin options,
and each of them showed options it does not read.

*   :guilabel:`PageTitle display format` sets the page title of the detail
    view. Only the :guilabel:`Persons List and Detail` element renders one, so
    the field is offered there alone now, through a condition in the FlexForm.
*   :guilabel:`Detail Page` is not offered by the :guilabel:`Persons List and
    Detail` element any more. Its names always link to the page of the element,
    which shows the detail view itself.
*   :guilabel:`Organisational Units` and :guilabel:`Function Types` are hidden
    in the :guilabel:`Profile Card` by the page TSconfig of the card, next to
    the list, sorting and pagination fields it already hid. The card shows the
    profiles it is given and restricts neither them nor their contracts.

Impact
======

Editors see fewer fields in these three elements. A value stored in a hidden
field stays stored, is kept when the element is saved, and still has no
effect, so no page renders differently. The field and its value are back when
the element is switched to a type that offers it.

Affected Installations
======================

Every installation that uses one of the three elements. Nothing has to be done
on update. A project that wants the card fields back sets them in its own page
TSconfig:

..  code-block:: typoscript

    TCEFORM.tt_content.pi_flexform.academicpersons_card.sDEF {
      settings\.organisationalUnits.disabled = 0
      settings\.functionTypes.disabled = 0
    }

..  index:: Backend, FlexForm, TSConfig, ext:academic_persons
