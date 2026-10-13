.. _important-child-records-keep-their-parent-on-publish:

=========================================================================
Important: Contracts and addresses keep their parent when it is published
=========================================================================

Description
===========

The person records are workspace aware since this version, see
:ref:`breaking-person-records-are-workspace-aware`. Six of them point at the
record they belong to through a field that is declared as a relation: the
contract of an e-mail address, a phone number and a postal address, the
profile and the organisational unit of a contract, and the profile of a
profile information record.

TYPO3 treated those pointers like any other relation when the parent was
edited in a workspace: the first edit versions the children together with the
parent, and TYPO3 pointed each child version at the version row of the parent,
which publishing deletes. Published from the workspace module, TYPO3 writes
the live parent into the children once more after publishing, and nothing was
lost. Published from the live workspace, by the scheduler through the publish
date of the workspace (:bash:`workspace:autopublish`) or by other code that
publishes as a user of the live workspace, the children kept the pointer to
the deleted row. A profile then showed no contracts and no profile
information, a contract no addresses, and a later edit of the parent no longer
included them.

The six pointers now name the table of their parent in
:php:`dontRemapTablesOnCopy`. A child keeps its live parent through a publish
from either place, and a copied parent, or a translated profile or contract,
still gets copies or translations of its children that point at the copy or
the translation.

Impact
======

Neither the database schema nor an upgrade wizard is involved.

Only an installation that ran a development state of version 3.0 and published
workspaces from the live workspace, through their publish date or by other
code that publishes as a user of the live workspace, can hold children that
lost their parent this way, and they are not repaired. This query lists the
live contracts whose profile no longer exists, and the same query with the
other table and field names finds the others:

..  code-block:: sql

    SELECT c.uid, c.pid, c.profile
      FROM tx_academicpersons_domain_model_contract AS c
      LEFT JOIN tx_academicpersons_domain_model_profile AS p ON p.uid = c.profile
     WHERE c.deleted = 0 AND c.t3ver_wsid = 0
       AND c.profile > 0 AND p.uid IS NULL;

Affected Installations
======================

Installations that publish workspaces with person records through their
publish date, or by other code that publishes as a user of the live workspace.

.. index:: Backend, TCA, ext:academic_persons
