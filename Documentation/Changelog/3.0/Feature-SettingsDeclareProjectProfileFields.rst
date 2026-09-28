.. _feature-settings-declare-project-profile-fields:

=======================================================
Feature: Settings declare project fields of the profile
=======================================================

Description
===========

A profile field of :file:`Configuration/AcademicPersons/Settings.yaml` can be
declared a project field with :yaml:`custom: true`: a column a site package adds
to the profile table with its own SQL and TCA. Its :yaml:`fieldName` names the
column and is required, its key is its property name. The profile editor of
`academic_persons_edit
<https://docs.typo3.org/p/fgtclb/academic-persons-edit/main/en-us/Configuration/Settings/Index.html#configuration-editor-project-fields>`__
lets people edit it.

The flags of a project field reach the TCA of its column like those of any
other field, once the column may be used: it is in the profile TCA, it is
neither a system column nor a column of the shipped profile model, its type is
``input``, ``text``, ``email``, ``link``, ``number`` or ``check``, and the
renderer and validators of the field fit that type.

Impact
======

Nothing changes for settings without a project field.

A project field whose column may not be used gets nothing of its flags, and the
settings listener raises an ``E_USER_DEPRECATED`` notice naming the field, the
column and the reason while the TCA is compiled. The backend and the install
tool keep working, and a test suite that fails on deprecations fails on it. A
regular field whose column the TCA does not have stays left out without a
notice.

.. index:: Backend, TCA, ext:academic_persons
