..  index:: Configuration
..  _configuration-general:

=====================
General configuration
=====================

**Extension configuration**
There are some options for global extension configuration:

..  confval:: types.physicalAddressTypes

    :type: string
    :Default: private=Private,business=Business

    The available types for physical addresses that can be chosen when adding a physical address to a profile.

..  confval:: types.emailAddressTypes

    :type: string
    :Default: private=Private,business=Business

    The available types for email addresses that can be chosen when adding an email address to a profile.

..  confval:: types.phoneNumberTypes

    :type: string
    :Default: private=Private,business=Business,mobile=Mobile

    The available types for phone numbers that can be chosen when adding a phone number to a profile.

..  confval:: profile.feuser.telephoneNumberType

    :type: string
    :Default: business

    The type assigned to telephone numbers imported from frontend users. The
    value must be one of :confval:`types.phoneNumberTypes`. An unavailable
    value is stored as the undefined type ``''``. It applies to every phone
    number of the :ref:`frontend user synchronisation map
    <configuration-frontend-user-sync>` without a type of its own, apart from
    the column :sql:`fax`.

..  confval:: profile.feuser.faxNumberType

    :type: string
    :Default: business

    The type assigned to fax numbers imported from frontend users. It is
    validated independently from
    :confval:`profile.feuser.telephoneNumberType`; an unavailable value is
    stored as the undefined type ``''``. It applies to the column :sql:`fax`
    when its entry of the :ref:`frontend user synchronisation map
    <configuration-frontend-user-sync>` names no type.

..  confval:: demand.allowedGroupByValues

    :type: string
    :Default: firstNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.first_name,lastNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.last_name

    What values are allowed to group person listings?

..  confval:: demand.allowedSortByValues

    :type: string
    :Default: firstName=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.first_name,lastName=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.last_name

    What values are allowed to sort person listings?

The three ``types.*`` options and the two ``demand.*`` options above share one
format: entries separated by commas, each one a value, optionally followed by
``=`` and a label, which may be a ``LLL:`` reference. Whitespace around both
separators is ignored, and so is an empty entry, such as the one after a
trailing comma. An entry without a label, such as ``lastName`` or
``lastName=``, is labelled with its value. An entry without a value, such as
``=Last name``, is ignored. A value that is listed twice keeps its last label.
The values of :confval:`demand.allowedSortByValues` and
:confval:`demand.allowedGroupByValues` are also the only fields a persons list
can be sorted or grouped by.
