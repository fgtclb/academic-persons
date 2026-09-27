<?php

declare(strict_types=1);

namespace TESTS\TestPluginActionContext\EventListener;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface as PersonsPluginControllerActionContextInterface;
use FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent;
use FGTCLB\AcademicPersons\Event\ModifyProfileTitlePlaceholderReplacementEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Records the content element the context of each event names, by the type a project listener
 * would declare: the contexts of the profile query event, which the persons actions build, and
 * of the plugin view event, which `academic_base` builds, go through a parameter typed against
 * the `academic_base` interface, the title placeholder event's through one of each - the
 * `academic_base` interface, and the deprecated persons interface that event still declares.
 * Changes nothing. A test resets it before it renders.
 */
final class RecordPluginActionContextListener
{
    /**
     * @var array<string, list<int|null>> Content element uids by event, `null` for none.
     */
    public static array $contentElements = [];

    #[AsEventListener(identifier: 'test-plugin-action-context/query')]
    public function recordQuery(ModifyProfileQueryEvent $event): void
    {
        $context = $event->getPluginControllerActionContext();
        if ($context !== null) {
            $this->recordShared('query', $context);
        }
    }

    #[AsEventListener(identifier: 'test-plugin-action-context/view')]
    public function recordView(ModifyPluginViewEvent $event): void
    {
        $this->recordShared('view', $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-action-context/title')]
    public function recordTitle(ModifyProfileTitlePlaceholderReplacementEvent $event): void
    {
        $this->recordShared('title', $event->getPluginControllerActionContext());
        $this->recordPersons('title (persons context)', $event->getPluginControllerActionContext());
    }

    private function recordShared(string $event, PluginControllerActionContextInterface $context): void
    {
        self::$contentElements[$event][] = $this->contentElementUid($context->getContentObjectRenderer()?->data);
    }

    private function recordPersons(string $event, PersonsPluginControllerActionContextInterface $context): void
    {
        self::$contentElements[$event][] = $this->contentElementUid($context->getContentObjectRenderer()?->data);
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function contentElementUid(?array $data): ?int
    {
        return isset($data['uid']) ? (int)$data['uid'] : null;
    }
}
