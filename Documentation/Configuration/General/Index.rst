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

..  confval:: profile.autoCreateProfiles

    :type: boolean
    :Default: false

    Whether :bash:`academic:createprofiles` creates profiles. When enabled, the
    command creates a profile for every frontend user of the record type
    ``Tx_Academicpersonsedit_Domain_Model_FrontendUser`` that has no profile
    yet, limited by
    :confval:`profile.createProfileForUserGroups`. When disabled, the command
    creates no profile at all. Only this command creates profiles from
    frontend users, a frontend login does not.

    The option applies to the profile factory this extension ships. A profile
    factory chosen through the :php:`ChooseProfileFactoryEvent` decides on its
    own.

..  confval:: profile.createProfileForUserGroups

    :type: string
    :Default:

    A comma-separated list of frontend user group uids. When set,
    :bash:`academic:createprofiles` creates a profile only for a frontend user
    that is a member of one of these groups. Empty means every frontend user.
    It has no effect while :confval:`profile.autoCreateProfiles` is disabled.

..  note::

    Both options belonged to :guilabel:`academic_persons_edit` before version
    2.1, which still lists them without any effect. The upgrade wizard
    :guilabel:`Migrate profile auto create options from
    "EXT:academic_persons_edit" to "EXT:academic_persons"` copies a value set
    there to this extension, as long as the option here still has its default.

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
