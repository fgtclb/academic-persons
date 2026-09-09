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
    value is stored as the undefined type ``''``.

..  confval:: profile.feuser.faxNumberType

    :type: string
    :Default: business

    The type assigned to fax numbers imported from frontend users. It is
    validated independently from
    :confval:`profile.feuser.telephoneNumberType`; an unavailable value is
    stored as the undefined type ``''``.

..  confval:: demand.allowedGroupByValues

    :type: string
    :Default: firstNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.first_name,lastNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.last_name

    What values are allowed to group person listings?

..  confval:: demand.allowedSortByValues

    :type: string
    :Default: firstNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.first_name,lastNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.last_name

    What values are allowed to sort person listings?
