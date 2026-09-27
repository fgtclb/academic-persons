..  _deprecation-persons-plugin-controller-action-context:

==========================================================
Deprecation: The plugin action context of academic_persons
==========================================================

Description
===========

:php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface`
and the class implementing it,
:php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContext`,
are deprecated and will be removed in 4.0. Since 3.0 the interface is the one
of :guilabel:`academic_base` under another name, see
:ref:`breaking-plugin-controller-action-context-interface-extends-base`, and
the academic extensions need only one.

The page title placeholder event keeps declaring the persons interface
throughout 3.x: changing the declared type within a minor release would break a
listener that hands the context on to code typed against it. It is the only
persons event left that declares it; the list, detail, selected profiles and
selected contracts events are removed in 3.0, see
:ref:`breaking-removed-profile-view-events`. In 4.0 the event declares
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`,
and the persons plugins hand it the :guilabel:`academic_base` context.

No deprecation notice is logged: an interface cannot raise one, and a notice
from the context class would fire on every request that renders a persons
plugin.

Impact
======

Nothing changes in 3.x. In 4.0, code that names the persons interface or class
is a fatal error.

Affected Installations
======================

Installations whose code names either type: typically an event listener of the
page title placeholder event that declares the type of the context it reads,
and code that builds a persons context itself, for example to call
:php:`ProfileTitleProvider::setFromProfile()`.

Migration
=========

Type against
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
instead. That works today, because every persons context satisfies it:

..  code-block:: php

    use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
    use FGTCLB\AcademicPersons\Event\ModifyProfileTitlePlaceholderReplacementEvent;

    public function __invoke(ModifyProfileTitlePlaceholderReplacementEvent $event): void
    {
        $this->addCanonicalLink($event->getPluginControllerActionContext());
    }

    private function addCanonicalLink(PluginControllerActionContextInterface $context): void
    {
        // ...
    }

Code that builds a context itself keeps building the persons one until 4.0,
because :php:`ProfileTitleProvider::setFromProfile()` declares it.

..  index:: PHP-API, NotScanned, ext:academic_persons
