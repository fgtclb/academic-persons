..  _developers-event-modify-plugin-view-event:

=====================
ModifyPluginViewEvent
=====================

:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` belongs to
:guilabel:`academic_base`, and the plugins of seven academic extensions
dispatch it when they render. A listener assigns further variables to the
view, which the templates, partials and sections of the plugin then render.
The event, its contract and an example are documented in the :ref:`manual of
academic_base
<fgtclb/academic-base:developers-event-modify-plugin-view-event>`.
This page describes what the plugins of this extension pass in.

When it is dispatched
=====================

Every plugin of this extension dispatches the event once each time an action
renders its view, after the action assigned its own variables:

..  list-table::
    :header-rows: 1

    *   -   Plugin name
        -   Action
        -   Dispatched
    *   -   :php:`List`, :php:`ListAndDetail`
        -   :php:`list`
        -   after the profile query, the pagination and the letters
    *   -   :php:`ListAndDetail`, :php:`Detail`
        -   :php:`detail`
        -   after the page title was set
    *   -   :php:`Card`
        -   :php:`card`
        -   after the query of the selected profiles
    *   -   :php:`SelectedProfiles`
        -   :php:`selectedProfiles`
        -   after the query of the selection, and also while the selection
            is empty
    *   -   :php:`SelectedContracts`
        -   :php:`selectedContracts`
        -   after the query of the selection, and also while the selection
            is empty

The filter form of the list posts to the action :php:`filter`, which redirects
to the list and renders nothing, so it dispatches no event. A detail view of a
profile that does not exist answers with the page-not-found response before
the event.

What this extension passes in
=============================

:php:`getPluginControllerActionContext()` returns the context the queries of
the same rendering received: the extension name :php:`AcademicPersons`, the
plugin and action names above, the settings of the content element and the
request. See :ref:`developers-plugin-action-context`. While a letter is
selected, the setting `paginationEnabled` of that context is ``'0'``, because
a letter switches the pagination off.

:php:`getView()` holds the variables the action assigned:

..  list-table::
    :header-rows: 1

    *   -   Action
        -   Variables
    *   -   :php:`list`
        -   `profiles`, `demand`, `activeListArguments`, `filterOptions`,
            `data`, `record`, `viewMode`, `viewModePartial`, `viewModes`,
            `defaultViewMode`, plus `paginator` and `pagination` while the
            pagination is on, and `alphabetFilterLetters` while the letter
            navigation is on and no profiles are selected by hand
    *   -   :php:`detail`
        -   `profile`, `publicProfile`, `data`, `record`
    *   -   :php:`card`
        -   `profiles`, `data`, `record`
    *   -   :php:`selectedProfiles`
        -   `profiles` while profiles are selected, `data`, `record`,
            `viewMode`, `viewModePartial`, `viewModes`, `defaultViewMode`,
            `viewModeArguments`
    *   -   :php:`selectedContracts`
        -   `contracts` while contracts are selected, `data`, `record`,
            `viewMode`, `viewModePartial`, `viewModes`, `defaultViewMode`,
            `viewModeArguments`

Example
=======

A site links the detail view of every profile to a page with an appointment
form. The page is a site setting of the site package,
``mySitepackage.appointmentPage``, and the listener hands its uid to the
detail templates as `appointmentPageUid`:

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/EventListener/AddAppointmentPage.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MySitepackage\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddAppointmentPage
    {
        #[AsEventListener(identifier: 'my-sitepackage/add-appointment-page')]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context->getControllerExtensionName() !== 'AcademicPersons'
                || $context->getActionName() !== 'detail'
            ) {
                return;
            }
            $appointmentPage = (int)$context->getSite()?->getSettings()->get('mySitepackage.appointmentPage', 0);
            if ($appointmentPage > 0) {
                $event->getView()->assign('appointmentPageUid', $appointmentPage);
            }
        }
    }

A template override of the detail view renders the link with
:html:`<f:link.page pageUid="{appointmentPageUid}">`.

Rules and pitfalls
==================

*   Check the extension name and the action name, or the plugin name. Every
    academic plugin dispatches the same event, and plugin names repeat
    between extensions.
*   A variable the listener assigns again replaces the one of the action in
    the view, and only there. The pagination, the letters of the letter
    navigation and the page title keep what the query returned. Replacing
    `profiles` of a paginated list changes nothing the visitor sees, because
    the template renders the items of `paginator`. Which profiles are shown
    belongs in :ref:`developers-event-modify-profile-demand-event` or
    :ref:`developers-event-modify-profile-query-event`.
*   The event carries no profile data of its own. A listener reads the
    profile of the detail view from its view variable `profile`.

Introduced in
=============

*   :ref:`feature-plugin-view-event` made the plugins of this extension
    dispatch the event.
*   :ref:`breaking-removed-profile-view-events` replaced the list, detail,
    selected profiles and selected contracts events with it.
*   :ref:`important-1790607226` hands it the context the queries receive.
