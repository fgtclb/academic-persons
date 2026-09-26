..  _important-label-overrides-use-the-documented-path:

============================================================
Important: Label overrides are read from the documented path
============================================================

Description
===========

The templates of this extension translate their labels with the extension name
:html:`AcademicPersons` instead of the extension key :html:`academic_persons`.
TYPO3 v12 and v13 build the TypoScript path of :typoscript:`_LOCAL_LANG` from
that name as it is given, so they read label overrides from
:typoscript:`plugin.tx_academic_persons`. They now read them from
:typoscript:`plugin.tx_academicpersons` and
:typoscript:`plugin.tx_academicpersons_<plugin>`, the paths the TYPO3
documentation names and TYPO3 v14 reads anyway.

Every label of the extension, and where it is shown, is listed in
:ref:`configuration-labels`.

Impact
======

On TYPO3 v12 and v13, a label override under
:typoscript:`plugin.tx_academic_persons._LOCAL_LANG` no longer has an effect.
Move it to :typoscript:`plugin.tx_academicpersons._LOCAL_LANG`, or to the path
of the one plugin it is meant for:

..  code-block:: typoscript

    plugin.tx_academicpersons._LOCAL_LANG.default.list.noProfilesFound = Nobody found
    plugin.tx_academicpersons_list._LOCAL_LANG.default.list.noProfilesFound = Nobody found

..  index:: Frontend, Fluid, TypoScript, ext:academic_persons
