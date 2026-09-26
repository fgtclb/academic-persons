<?php

declare(strict_types=1);

namespace TESTS\TestPluginActionContext\EventListener;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface as PersonsPluginControllerActionContextInterface;
use FGTCLB\AcademicPersons\Event\ModifyDetailProfileEvent;
use FGTCLB\AcademicPersons\Event\ModifyListProfilesEvent;
use FGTCLB\AcademicPersons\Event\ModifyProfileTitlePlaceholderReplacementEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Records the content element each persons event's context names, by the type a project
 * listener would declare: the list event's context goes through a parameter typed against the
 * `academic_base` interface, the detail event's through one typed against the deprecated
 * persons interface. Changes nothing. A test resets it before it renders.
 */
final class RecordPluginActionContextListener
{
    /**
     * @var array<string, list<int|null>> Content element uids by event, `null` for none.
     */
    public static array $contentElements = [];

    #[AsEventListener(identifier: 'test-plugin-action-context/list')]
    public function recordList(ModifyListProfilesEvent $event): void
    {
        $this->recordShared('list', $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-action-context/detail')]
    public function recordDetail(ModifyDetailProfileEvent $event): void
    {
        $this->recordPersons('detail', $event->getPluginControllerActionContext());
    }

    #[AsEventListener(identifier: 'test-plugin-action-context/title')]
    public function recordTitle(ModifyProfileTitlePlaceholderReplacementEvent $event): void
    {
        $this->recordShared('title', $event->getPluginControllerActionContext());
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
