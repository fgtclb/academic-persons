..  _important-localizing-an-organisational-unit-keeps-its-contracts:

===================================================================
Important: Localizing an organisational unit leaves contracts alone
===================================================================

Description
===========

A contract belongs to its profile and is translated with it. The
:guilabel:`Contracts` field of an organisational unit lists the contracts of
its people as an inline relation all the same, and core localizes every inline
child of a localized record. Localizing an organisational unit therefore
created a translation of each contract it lists, and one more copy of a
contract that is valid in all languages or in the target language already. A
profile valid in all languages then showed such a contract twice.

Localizing an organisational unit, or copying it into a language, no longer
localizes its contracts. The contracts core creates are deleted again when the
localization ends, together with their addresses, email addresses and phone
numbers. For a contract that is translated already, because its profile is
translated, core still reports that its localization failed, while nothing is
created. A plain copy of an organisational unit keeps copying its contracts.

Impact
======

Contracts created by localizing an organisational unit before the update stay.
A contract keeps the organisational unit of its default language record, so a
contract that points to a translation of a unit was created that way. This
query lists them:

..  code-block:: sql

    SELECT contract.uid, contract.profile, contract.organisational_unit, contract.sys_language_uid
    FROM tx_academicpersons_domain_model_contract AS contract
    JOIN tx_academicpersons_domain_model_organisational_unit AS unit ON unit.uid = contract.organisational_unit
    WHERE contract.deleted = 0 AND unit.sys_language_uid > 0;

Delete them. The contracts of a profile and their translations point to the
default language unit and are not listed.

Affected Installations
======================

Installations that translate organisational units.

..  index:: Backend, ext:academic_persons
