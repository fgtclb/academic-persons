.. include:: /Includes.rst.txt

.. _feature-1772802168:

==============================================
Feature: Add `academic:updateprofiles` command
==============================================

Description
===========

`EXT:academic_persons` provided the `academic:createprofiles` for quite a long
time now to create profile automatically for frontend users taking data from
TYPO3 `fe_users` records. That can be extended by extension or projects to get
import data from other sources like `LDAP` or other external identity providers.

To further improve that handling the `academic:updateprofiles` is now added in
a similar way to handle updates of profile data based on the source using the
scheduler, for example to update data imported from external LDAP.

:sql:`import_identifier varchar(170) DEFAULT '' NOT NULL` is added to all
extension tables and all domain models got extended to have that property
along with setter und getter in place. Existing imported profile data needs
to be updated in the project to have the identifier in place and custom
create/update profile implementation can use that field to flag it with their
data.

Further a :sql:`skip_sync` field is added to :sql:`tx_academicpersons_domain_model_profile`
defaulting to `false` (`(INT)0`). If this field is set to `true/1` the update
command excludes these records in a early stage and do not call or dispatch any
further methods or events.

..  important::

    Projects needs to implement own upgrade wizards to set the `import_identifier`
    data before using the new `academic:updateprofiles` command and also ensure
    that custom ProfileFactory implements the additional methods required by the
    extended interface to full-fill the requirements for the update command.

Important note
--------------

Custom profile factory implementations needs to be updated due to the extended
:php:`\FGTCLB\AcademicPersons\Profile\ProfileFactoryInterface` interface and
implement the update handling part.

This is breaking and needs to be addressed on a update.


.. index:: CLI
