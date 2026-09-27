..  _breaking-removed-profile-view-events:

=======================================================================
Breaking: The list, detail and selection events of the plugins are gone
=======================================================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

The plugins of this extension no longer dispatch four events, and the classes
are removed:

*   :php:`\FGTCLB\AcademicPersons\Event\ModifyListProfilesEvent`, dispatched by
    the list and list-and-detail plugins after the query,
*   :php:`\FGTCLB\AcademicPersons\Event\ModifyDetailProfileEvent`, dispatched by
    the detail and list-and-detail plugins before a profile was rendered,
*   :php:`\FGTCLB\AcademicPersons\Event\ModifySelectedProfilesEvent`, dispatched
    by the selected profiles plugin after the query,
*   :php:`\FGTCLB\AcademicPersons\Event\ModifySelectedContractsEvent`, dispatched
    by the selected contracts plugin after the query.

Every plugin of this extension, the card plugin included, which had no event
of this kind, dispatches
:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` of
:guilabel:`academic_base` instead, once each time it renders. It is one event
for every academic plugin, so a view variable is added the same way everywhere.
See :ref:`feature-plugin-view-event` and the `changelog of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/3.0/Feature-ModifyPluginViewEvent.html>`__.

The new event carries the view and the plugin context, but no data of this
extension. What the removed events let a listener change has other places:

..  list-table::
    :header-rows: 1

    *   -   Removed
        -   Instead
    *   -   Assigning a view variable, in any of the four events
        -   :php:`ModifyPluginViewEvent`, checking the action name on its
            context
    *   -   :php:`ModifyListProfilesEvent::setProfileDemand()`
        -   :php:`ModifyProfileDemandEvent`, before the query; see below for
            what follows that demand
    *   -   :php:`ModifyListProfilesEvent::setProfiles()`,
            :php:`ModifySelectedProfilesEvent::setProfiles()`
        -   :php:`ModifyProfileQueryEvent` or, for the list,
            :php:`ModifyProfileDemandEvent` to change which profiles are
            queried; for the selected profiles and a list without pagination,
            assigning the view variable :fluid:`profiles` in
            :php:`ModifyPluginViewEvent` to replace what is rendered. A
            paginated list renders the items of :fluid:`paginator`, which
            that does not change.
    *   -   :php:`ModifySelectedContractsEvent::setContracts()`
        -   :php:`ModifyContractQueryEvent`, or assigning the view variable
            :fluid:`contracts`
    *   -   :php:`ModifyDetailProfileEvent::setProfile()`
        -   assigning the view variable :fluid:`profile` in
            :php:`ModifyPluginViewEvent`
    *   -   :php:`ModifyDetailProfileEvent::setDefaultPageTitleFormat()` and
            :php:`setSettingsPageTitleFormat()`
        -   the page title format of the detail content element, the setting
            :typoscript:`plugin.tx_academicpersons.settings.pageTitleFormat`
            for every detail content element that sets none, or
            :php:`ModifyProfileTitlePlaceholderReplacementEvent` for the value
            of a placeholder

A demand a listener of :php:`ModifyListProfilesEvent` handed back came after
the query and drove the rest of the list action: the letters offered, the
active letter, the switch that turns the pagination off under a letter, the
current page and the order of a manual selection. A demand a listener of
:php:`ModifyProfileDemandEvent` hands back reaches the repository only: it
decides which profiles are queried and which letters are offered. The active
letter, the pagination and the order of a manual selection keep following the
demand of the request. A listener that set a letter or a manual selection
through the removed event and moves to :php:`ModifyProfileDemandEvent` narrows
the query, which the removed event never did, but the active letter, the
pagination and the order of a manual selection do not follow it.

What the action computed from the query keeps using the queried result when a
listener of the new event assigns another value to a view variable: the
pagination and the letter navigation of the list, and the page title of the
detail view.

Impact
======

A listener of one of the four events is no longer called. It causes no error:
TYPO3 registers the event of a listener as a class name, read from the type of
its parameter or from the :php:`event` argument of the attribute, without
loading the class, and PHP checks the type of a parameter only when the method
is called. What the listener did silently stops happening.

PHPStan reports the listener, because the class of its parameter does not
exist any more:

..  code-block:: text

    Parameter $event of method MyVendor\MySitepackage\EventListener\AddOfficeHours::__invoke()
    has invalid type FGTCLB\AcademicPersons\Event\ModifyListProfilesEvent.

Affected installations
======================

Installations with an event listener of
:php:`ModifyListProfilesEvent`, :php:`ModifyDetailProfileEvent`,
:php:`ModifySelectedProfilesEvent` or :php:`ModifySelectedContractsEvent`,
registered with an attribute or in :file:`Configuration/Services.yaml`.
Searching the project code for the four class names finds them.

Migration
=========

Register the listener for :php:`ModifyPluginViewEvent` and return early for
every plugin and action it is not meant for:

..  code-block:: php
    :caption: Before

    use FGTCLB\AcademicPersons\Event\ModifyDetailProfileEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddOfficeHours
    {
        #[AsEventListener]
        public function __invoke(ModifyDetailProfileEvent $event): void
        {
            $event->getView()->assign('officeHoursPageId', 42);
        }
    }

..  code-block:: php
    :caption: After

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddOfficeHours
    {
        #[AsEventListener]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context->getControllerExtensionName() !== 'AcademicPersons'
                || $context->getActionName() !== 'detail'
            ) {
                return;
            }
            $event->getView()->assign('officeHoursPageId', 42);
        }
    }

The action names are :php:`list`, :php:`detail`, :php:`card`,
:php:`selectedProfiles` and :php:`selectedContracts`; the plugin names
:php:`List`, :php:`ListAndDetail`, :php:`Detail`, :php:`Card`,
:php:`SelectedProfiles` and :php:`SelectedContracts`. The profile the detail
view renders is its view variable :fluid:`profile`, and the context is typed
against the interface of :guilabel:`academic_base`.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_persons
