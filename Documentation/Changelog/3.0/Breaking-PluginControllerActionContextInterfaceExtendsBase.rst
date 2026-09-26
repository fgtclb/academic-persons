..  _breaking-plugin-controller-action-context-interface-extends-base:

============================================================================
Breaking: The persons plugin action context extends the one of academic_base
============================================================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

:php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface`
now extends
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
and declares nothing of its own. Up to 2.4 it was a copy of that interface
without :php:`getContentObjectRenderer()`, so the two were different types: a
listener written for an event of :guilabel:`academic_jobs`,
:guilabel:`academic_partners` or :guilabel:`academic_projects` could not take
the context of a persons event, and the context of a persons event could not
say which content element it was rendered for.

The context class of this extension,
:php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContext`,
gains :php:`getContentObjectRenderer()`. It returns the content object of the
request the plugin renders in, and :php:`null` when the request carries none,
exactly as the :guilabel:`academic_base` context does.

The events keep the types they declare, so a listener typed against the
persons interface is called as before.

Impact
======

**A class of a project that implements the persons interface itself is a fatal
error** until it adds :php:`getContentObjectRenderer()`. PHP checks that
when the class is loaded, so the installation breaks on the first request that
uses the class rather than silently.

A listener of a persons event may now type the context it receives against the
:guilabel:`academic_base` interface, and may read the content element from it.

Affected Installations
======================

Installations with a class of their own implementing
:php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface`.
None of the extensions ships one apart from the context class above.

Migration
=========

Add the method to the implementing class:

..  code-block:: php

    use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

    public function getContentObjectRenderer(): ?ContentObjectRenderer
    {
        $contentObject = $this->request->getAttribute('currentContentObject');
        return $contentObject instanceof ContentObjectRenderer ? $contentObject : null;
    }

Keep implementing the persons interface for as long as the object is handed
to a persons event: the events declare it throughout 3.x, and an object that
implements only the :guilabel:`academic_base` interface is a type error there.
Switch to the :guilabel:`academic_base` interface with 4.0, see
:ref:`deprecation-persons-plugin-controller-action-context`.

..  index:: PHP-API, NotScanned, ext:academic_persons
