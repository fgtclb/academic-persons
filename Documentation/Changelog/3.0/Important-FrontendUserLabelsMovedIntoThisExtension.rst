..  _important-frontend-user-labels-moved:

=========================================================
Important: Frontend user labels moved into this extension
=========================================================

Description
===========

The relation between frontend users and profiles belongs to this extension,
but the labels of its backend fields were read from
:file:`EXT:academic_persons_edit/Resources/Private/Language/locallang_be.xlf`,
an extension this one does not require. Without
:composer:`fgtclb/academic-persons-edit` the field :guilabel:`Assigned
Profiles` and its tab in the frontend user form, the record type item of the
frontend user, and the field :guilabel:`Assigned Users` and its tab in the
profile form showed no label at all.

The labels now live in
:file:`EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf`, and
:composer:`fgtclb/academic-persons-edit` no longer ships them:

*   :xml:`fe_users.columns.tx_academicpersons_profiles.label`
*   :xml:`fe_users.tabs.tx_academicpersons_profiles.label`
*   :xml:`fe_users.columns.tx_extbase_type.items.Tx_AcademicPersonsEdit_Domain_Model_FrontendUser`
*   :xml:`tx_academicpersons_domain_model_profile.columns.frontend_users.label`
*   :xml:`tx_academicpersons_domain_model_profile.div.frontend_users.label`,
    which was :xml:`tx_academicpersons_domain_model_profile.tabs.frontend_users.label`

The same change gives several backend fields of the profile records the label
they never had: the language field of addresses, e-mail addresses, function
types, locations, organisational units, phone numbers, profiles and profile
information, the profile of a contract and the date palette of profile
information.

Impact
======

A label override that targets one of the keys above in the file of
:composer:`fgtclb/academic-persons-edit` has to target the file of this
extension instead, and the tab of the profile form its new key.

Affected Installations
======================

Installations that override these backend labels with
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` on TYPO3 v13
or :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` on TYPO3
v14.

..  index:: Backend, TCA, ext:academic_persons
