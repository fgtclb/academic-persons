..  _developers-event-modify-profile-title-placeholder-replacement-event:

=============================================
ModifyProfileTitlePlaceholderReplacementEvent
=============================================

:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileTitlePlaceholderReplacementEvent`
lets a listener decide what a placeholder of the page title format of the
detail view is replaced with. It is the way to give the format placeholders of
a project's own, without replacing the page title provider.

When it is dispatched
=====================

The detail action of the detail plugin and of the list-and-detail plugin sets
the page title of the profile it shows, before it renders. The title is built
from the page title format of the content element, from the setting
:typoscript:`plugin.tx_academicpersons.settings.pageTitleFormat` while the
content element sets none, or from the built-in format
``%%TITLE%% %%FIRST_NAME%% %%MIDDLE_NAME%% %%LAST_NAME%%``.

The event is dispatched once for every placeholder of the format, a text
between two ``%%``, in the order the placeholders appear. Before it, the
extension has already replaced what it knows:

#.  A placeholder that names a public getter of the profile, one without a
    required argument, is replaced by its value when that is a string or a
    number: ``%%FIRST_NAME%%`` by :php:`getFirstName()`, ``%%UID%%`` by the
    uid as a string. Any other value, :php:`null`, a boolean, an object or an
    array, leaves the placeholder unreplaced.
#.  A placeholder that starts with ``LLL:EXT:`` is replaced by the translation
    of that label in the language of the site.
#.  The event is dispatched with what these two steps produced.

The detail view never dispatches the event for a profile that does not exist:
it answers with the page-not-found response of the site before.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   What it does
    *   -   :php:`getPlaceholder()`
        -   The placeholder as it stands in the format, without the
            surrounding ``%%``, for example ``FIRST_NAME``.
    *   -   :php:`getReplacement()`
        -   The value the placeholder is going to be replaced with. Equal to
            :php:`getPlaceholder()` while nothing replaced it.
    *   -   :php:`setReplacement()`
        -   Sets that value.
    *   -   :php:`getProfile()`
        -   The profile of the detail view, in the language of the page.
    *   -   :php:`getPluginControllerActionContext()`
        -   The plugin the title is built for, see
            :ref:`developers-plugin-action-context`. Never :php:`null` here.

Example
=======

A site wants the organisational unit of the first contract in the title of the
detail page, with the format
``%%FIRST_NAME%% %%LAST_NAME%% - %%ORGANISATIONAL_UNIT%%``. The profile has no
getter of that name, so the placeholder reaches the listener unreplaced:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ReplaceOrganisationalUnitPlaceholder.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPersons\Domain\Model\Contract;
    use FGTCLB\AcademicPersons\Event\ModifyProfileTitlePlaceholderReplacementEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class ReplaceOrganisationalUnitPlaceholder
    {
        #[AsEventListener(identifier: 'my-extension/replace-organisational-unit-placeholder')]
        public function __invoke(ModifyProfileTitlePlaceholderReplacementEvent $event): void
        {
            if ($event->getPlaceholder() !== 'ORGANISATIONAL_UNIT') {
                return;
            }
            $unitName = '';
            foreach ($event->getProfile()->getContracts() as $contract) {
                if ($contract instanceof Contract && $contract->getOrganisationalUnit() !== null) {
                    $unitName = $contract->getOrganisationalUnit()->getUnitName();
                    break;
                }
            }
            $event->setReplacement($unitName);
        }
    }

An empty replacement removes the placeholder from the title. Spaces at the
start and the end of the title are removed and repeated spaces are collapsed
into one, so a missing middle name leaves no gap. The dash of the format above
stays, though, when the profile has no unit.

Rules and pitfalls
==================

*   **A replacement equal to the placeholder counts as no replacement.** The
    placeholder then stays in the title as it is, with its ``%%``. A listener
    that wants a placeholder gone sets an empty string.
*   Name a placeholder of your own so that it matches no getter of the profile:
    a placeholder that matches one is replaced by the value of that getter
    before the listener is called, and the listener sees that value as the
    replacement.
*   A placeholder whose getter returns no string and no number reaches the
    listener unreplaced, just like one that matches no getter. A listener can
    therefore give ``%%IMAGE%%``, ``%%CONTRACTS%%`` or ``%%HIDDEN%%`` a text of
    its own. Without a listener such a placeholder stays in the title.
*   The event is dispatched for every placeholder, the shipped ones included.
    Return early for every placeholder the listener does not handle.
*   A placeholder may contain letters, digits, the underscore, spaces and the
    characters ``;:./-\``. Anything else ends the match, and the text stays in
    the title.
*   The context declares the interface of this extension, which is deprecated.
    Type code that receives it against the interface of
    :guilabel:`academic_base`, see :ref:`developers-plugin-action-context`.

Introduced in
=============

*   :ref:`feature-1746706500` (2.1) introduced the event.
*   :ref:`feature-1746706600` (2.1) added the ``LLL:EXT:`` placeholders that
    are replaced before it.
*   :ref:`important-ace-915-academic-persons` (2.4) leaves a placeholder
    unreplaced for the listener when its getter returns no string and no
    number, instead of failing the page.
*   :ref:`breaking-plugin-controller-action-context-interface-extends-base`
    made its context satisfy the interface of :guilabel:`academic_base` in
    3.0.
*   :ref:`deprecation-persons-plugin-controller-action-context` deprecates the
    context interface it declares.
*   :ref:`breaking-removed-profile-view-events` points the removed page title
    methods of the detail event here.
