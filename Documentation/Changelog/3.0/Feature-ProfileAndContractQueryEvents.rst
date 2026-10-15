..  _feature-ace-715-academic-persons-query-event-context:

====================================================
Feature: The query events say which plugin is asking
====================================================

Description
===========

:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent` and
:php:`\FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent`, see
:ref:`feature-profile-and-contract-query-events`, carry the plugin they narrow
the query for now. In version 2.4 a listener could not tell the plugins apart
and narrowed every one of them alike.

:php:`getPluginControllerActionContext()` returns the plugin name, the settings
of the content element, the request, the site and its language, and the content
object of the element. A listener can therefore act for one plugin only, or read
a setting an editor made. The plugin names are the ones the plugins are
registered with, :php:`List`, :php:`ListAndDetail`, :php:`Card`,
:php:`SelectedProfiles`, :php:`SelectedContracts` and :php:`Detail`, not the
content element types:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ShowOnlyConsentingProfiles.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class ShowOnlyConsentingProfiles
    {
        #[AsEventListener(identifier: 'my-extension/show-only-consenting-profiles')]
        public function __invoke(ModifyProfileQueryEvent $event): void
        {
            // Both of these render a profile list, and they are two plugins.
            if (!in_array($event->getPluginControllerActionContext()?->getPluginName(), ['List', 'ListAndDetail'], true)) {
                return;
            }
            $query = $event->getQuery();
            $event->addConstraint($query->equals('publicDisplayConsent', true));
        }
    }

The context is :php:`null` where a query has no plugin behind it.

See :ref:`developers-event-modify-profile-query-event` and
:ref:`developers-event-modify-contract-query-event`.

Impact
======

A listener written for version 2.4 keeps working and keeps narrowing every
plugin. One that is meant for some plugins only can check the context now.

..  index:: Frontend, PHP-API, ext:academic_persons
