<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service;

use Doctrine\DBAL\Result;
use FGTCLB\AcademicBase\Environment\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;
use FGTCLB\AcademicBase\Environment\StateBuildContext;
use FGTCLB\AcademicBase\Environment\StateInterface;
use FGTCLB\AcademicBase\Environment\StateManagerInterface;
use FGTCLB\AcademicPersons\Command\CreateProfilesCommand;
use FGTCLB\AcademicPersons\Event\ChooseProfileFactoryEvent;
use FGTCLB\AcademicPersons\Profile\ProfileFactory;
use FGTCLB\AcademicPersons\Profile\ProfileFactoryInterface;
use FGTCLB\AcademicPersons\Provider\FrontendUserProvider;
use FGTCLB\AcademicPersons\Service\Event\ModifyProfileCreateEnvironmentStateBuildContextForFrontendUserEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * This class provides the service methods for the {@see CreateProfilesCommand},
 * which allows easier testing of them.
 *
 * @internal to be used only in {@see CreateProfilesCommand::execute()} and not part of public API.
 */
#[Autoconfigure(public: true)]
final class ProfileCreateCommandService
{
    public function __construct(
        #[Autowire(service: ProfileFactory::class)]
        private readonly ProfileFactoryInterface $defaultFactory,
        private readonly FrontendUserProvider $frontendUserProvider,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly StateManagerInterface $stateManager,
    ) {}

    /**
     * @param int[] $includePids
     * @param int[] $excludePids
     * @throws \Doctrine\DBAL\Exception
     */
    public function execute(array $includePids = [], array $excludePids = []): void
    {
        $this->stateManager->backup();
        $this->stateManager->reset();
        try {
            $frontendUsersResult = $this->getUsersWithoutProfileResult($includePids, $excludePids);
            while ($frontendUserRecord = $frontendUsersResult->fetchAssociative()) {
                $this->processFrontendUserRecord($frontendUserRecord);
            }
        } finally {
            $this->stateManager->restore();
        }
    }

    /**
     * @param int[] $includePids
     * @param int[] $excludePids
     */
    private function getUsersWithoutProfileResult(array $includePids, array $excludePids = []): Result
    {
        return $this->frontendUserProvider->getUsersWithoutProfileResult($includePids, $excludePids);
    }

    /**
     * @param array<string, mixed> $frontendUserRecord
     */
    private function processFrontendUserRecord(array $frontendUserRecord): void
    {
        $this->stateManager->backup();
        $this->stateManager->reset();
        try {
            $environmentState = $this->bootstrapSuitableEnvironmentForFrontendUser($frontendUserRecord);
            $frontendUserAuthentication = $this->prepareFrontendUserAuthentication(
                $frontendUserRecord,
                $environmentState
            );
            $profileFactory = $this->getSuitableProfileFactory($frontendUserAuthentication);
            if (!$profileFactory->shouldCreateProfileForUser($frontendUserAuthentication)) {
                return;
            }
            // Reapply build environment state to be sure that project implementation do not messup with the environment.
            if ($environmentState !== null) {
                $this->stateManager->apply($environmentState);
            }
            $profileFactory->createProfileForUser($frontendUserAuthentication);
        } finally {
            $this->stateManager->restore();
        }
    }

    /**
     * Create a state for `$pageId` and populate the environment with it,
     * returning the created state elements as {@see StateInterface}.
     *
     * **Be aware** that this method changes the environment without doing and backup
     * of it nor restores it if {@see StateBuildContext::$autoApplyBootstrappedEnvironment}
     * is set to true. For snapshot handling see following methods:
     *
     * - {@see StateManagerInterface::backup()}
     * - {@see StateManagerInterface::restore()}
     *
     * @param array<string, mixed> $frontendUserRecord
     * @throws NoTypo3VersionCompatibleEnvironmentBuilderFound
     */
    private function bootstrapSuitableEnvironmentForFrontendUser(array $frontendUserRecord): ?StateInterface
    {
        $stateBuildContext = new StateBuildContext(
            ApplicationType::FRONTEND,
            (int)($frontendUserRecord['pid'] ?? 0),
            0,
        );
        $event = new ModifyProfileCreateEnvironmentStateBuildContextForFrontendUserEvent(
            frontendUserRecord: $frontendUserRecord,
            defaultStateBuildContext: $stateBuildContext,
            stateBuildContext: $stateBuildContext,
        );
        $event = $this->eventDispatcher->dispatch($event);
        /** @var ModifyProfileCreateEnvironmentStateBuildContextForFrontendUserEvent $event */
        $stateBuildContext = $event->getStateBuildContext();
        if ($stateBuildContext === null) {
            return null;
        }
        $state = $this->stateManager->bootstrap($stateBuildContext);
        $this->stateManager->apply($state);
        return $state;
    }

    /**
     * @param array<string, mixed> $frontendUserRecord
     */
    private function prepareFrontendUserAuthentication(array $frontendUserRecord, ?StateInterface $state = null): FrontendUserAuthentication
    {
        $frontendUserAuthentication = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $frontendUserAuthentication->user = $frontendUserRecord;
        $frontendUserAuthentication->fetchGroupData($state?->request() ?? $GLOBALS['TYPO3_REQUEST'] ?? new ServerRequest());
        return $frontendUserAuthentication;
    }

    private function getSuitableProfileFactory(FrontendUserAuthentication $frontendUserAuthentication): ProfileFactoryInterface
    {
        /** @var ChooseProfileFactoryEvent $chooseProfileFactoryEvent */
        $chooseProfileFactoryEvent = $this->eventDispatcher->dispatch(new ChooseProfileFactoryEvent(
            frontendUserAuthentication: $frontendUserAuthentication,
        ));
        return $chooseProfileFactoryEvent->getProfileFactory()
            // Use EXT:academic_person default profile factory implementation
            ?? $this->defaultFactory;
    }
}
