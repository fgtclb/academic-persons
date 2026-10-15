..  _developers-plugin-action-context:

=========================
The plugin action context
=========================

The events in the table below carry the context the plugin renders in: the
request, the site and language, the plugin and action name, the settings of the
content element and the content element itself.
:php:`getPluginControllerActionContext()` returns it.

..  list-table::
    :header-rows: 1

    *   -   Event
        -   Declared context type
    *   -   :ref:`ModifyProfileTitlePlaceholderReplacementEvent <developers-event-modify-profile-title-placeholder-replacement-event>`
        -   the interface of this extension, deprecated
    *   -   :ref:`ModifyProfileQueryEvent <developers-event-modify-profile-query-event>`,
            :ref:`ModifyContractQueryEvent <developers-event-modify-contract-query-event>`
        -   the interface of :guilabel:`academic_base`, :php:`null` where the
            query has no plugin behind it
    *   -   :ref:`ModifyPluginViewEvent <developers-event-modify-plugin-view-event>`
            of :guilabel:`academic_base`, which every plugin of this extension
            dispatches when it renders
        -   the interface of :guilabel:`academic_base`

Type a listener against
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`,
whichever of the events it listens to. The interface of this extension extends
it and declares nothing of its own, so every context a persons event carries
satisfies it, and the same code serves the events of the other academic
extensions. The interface of this extension is removed in 4.0, and the title
placeholder event then declares the :guilabel:`academic_base` one, see
:ref:`deprecation-persons-plugin-controller-action-context`.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/LogPluginContentElement.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
    use FGTCLB\AcademicPersons\Event\ModifyProfileTitlePlaceholderReplacementEvent;
    use Psr\Log\LoggerInterface;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class LogPluginContentElement
    {
        public function __construct(private readonly LoggerInterface $logger) {}

        #[AsEventListener(identifier: 'my-extension/log-plugin-content-element')]
        public function __invoke(ModifyProfileTitlePlaceholderReplacementEvent $event): void
        {
            $this->log($event->getPluginControllerActionContext());
        }

        private function log(PluginControllerActionContextInterface $context): void
        {
            $this->logger->info('Profile page title built', [
                'plugin' => $context->getPluginName(),
                'contentElement' => $context->getContentObjectRenderer()?->data['uid'] ?? null,
            ]);
        }
    }

:php:`getContentObjectRenderer()` is the content element of the plugin, also
for the page title placeholder event, which is dispatched while the detail
action builds the page title. It is :php:`null` for a context built from a
request without a content element.

..  _developers-plugin-names:

The plugin names
================

:php:`getPluginName()` is the name a plugin is **registered** with, not its
content element type. There are six, and two of them render a profile list:

..  list-table::
    :header-rows: 1

    *   - Plugin name
        - Content element type
    *   - :php:`List`
        - :sql:`academicpersons_list`
    *   - :php:`ListAndDetail`
        - :sql:`academicpersons_listanddetail`
    *   - :php:`Card`
        - :sql:`academicpersons_card`
    *   - :php:`SelectedProfiles`
        - :sql:`academicpersons_selectedprofiles`
    *   - :php:`SelectedContracts`
        - :sql:`academicpersons_selectedcontracts`
    *   - :php:`Detail`
        - :sql:`academicpersons_detail`

:php:`Detail` never reaches the two query events, which are not dispatched
for the detail action. It reaches the page title placeholder event and the
plugin view event. A detail view rendered by the :php:`ListAndDetail` plugin
reports that plugin's name, not :php:`Detail`. The action name tells the list
and the detail view of that plugin apart: :php:`list` or :php:`detail`.
