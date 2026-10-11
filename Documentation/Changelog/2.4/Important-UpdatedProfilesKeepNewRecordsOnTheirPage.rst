.. _important-ace-843-academic-persons:

==========================================================
Important: Updated profiles keep new records on their page
==========================================================

Description
===========

:bash:`academic:updateprofiles` stored every record it added to an existing
profile on the page of the frontend user: a new contract, and the physical
address, email address and phone numbers of a new or an existing contract. A
profile that had been moved to a folder of its own therefore collected records
in the folder of the frontend users.

The command now stores a new contract on the page of its profile. A physical
address, an email address or a phone number it adds to a contract is stored on
the page of that contract, which an editor may have moved on its own.

Creating a profile with :bash:`academic:createprofiles` is unchanged: a new
profile and its records are stored on the page of the frontend user.

Impact
======

Records added by :bash:`academic:updateprofiles` are stored next to the
profile or the contract they belong to.

Records stored on the page of the frontend user by an earlier run stay where
they are. They are not moved.

Affected Installations
======================

Installations that synchronize profiles from frontend users and keep the
profiles on another page than the frontend users.

Migration
=========

Optionally, find the records an earlier run stored on the page of the frontend
user and move them to the page of their profile or contract, for example with
the clipboard of the :guilabel:`List` module. Moving a contract to another page
takes its physical addresses, email addresses and phone numbers along.

The contracts of the synchronization that are not stored on the page of their
profile:

..  code-block:: sql

    SELECT contract.uid, contract.pid, profile.uid AS profile, profile.pid AS profile_pid
    FROM tx_academicpersons_domain_model_contract AS contract
    INNER JOIN tx_academicpersons_domain_model_profile AS profile
        ON profile.uid = contract.profile
    WHERE contract.import_identifier LIKE 'fe_users:%'
        AND contract.pid <> profile.pid
        AND contract.deleted = 0;

The email addresses of the synchronization that are not stored on the page of
their contract. Replace :sql:`tx_academicpersons_domain_model_email` with
:sql:`tx_academicpersons_domain_model_address` or
:sql:`tx_academicpersons_domain_model_phone_number` for the physical addresses
and the phone numbers:

..  code-block:: sql

    SELECT record.uid, record.pid, contract.uid AS contract, contract.pid AS contract_pid
    FROM tx_academicpersons_domain_model_email AS record
    INNER JOIN tx_academicpersons_domain_model_contract AS contract
        ON contract.uid = record.contract
    WHERE record.import_identifier LIKE 'fe_users:%'
        AND record.pid <> contract.pid
        AND record.deleted = 0;

.. index:: CLI, Database, ext:academic_persons
