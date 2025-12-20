<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Profile;

use FGTCLB\AcademicPersons\Domain\Model\FrontendUser;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

abstract class AbstractProfileFactory implements ProfileFactoryInterface
{
    protected PersistenceManagerInterface $persistenceManager;
    protected ExtensionConfiguration $extensionConfiguration;
    protected EventDispatcherInterface $eventDispatcher;
    protected bool $autoCreateProfiles = false;
    /**
     * @var int[]
     */
    protected array $userGroupsToCreateProfilesFor = [];

    #[Required]
    public function injectEventDispatcherInterface(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    #[Required]
    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration): void
    {
        $this->extensionConfiguration = $extensionConfiguration;
        $this->initializeExtensionConfigurationOptions($extensionConfiguration);
    }

    #[Required]
    public function injectPersistenceManagerInterface(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    public function shouldCreateProfileForUser(FrontendUserAuthentication $frontendUserAuthentication): bool
    {
        $userAspect = new UserAspect($frontendUserAuthentication);
        if ($this->autoCreateProfiles === false) {
            // Auto create not enabled, return false.
            return false;
        }
        if ($this->userGroupsToCreateProfilesFor === []) {
            // Auto create enabled and no user group restrictions set, return true.
            return true;
        }
        $userGroupId = $userAspect->getGroupIds();
        $userIsInUserGroup = array_intersect($userGroupId, $this->userGroupsToCreateProfilesFor) !== [];

        return $userIsInUserGroup;
    }

    public function createProfileForUser(FrontendUserAuthentication $frontendUserAuthentication): ?int
    {
        /** @var array<string, int|string|null>|null $userData */
        $userData = $frontendUserAuthentication->user;
        if ($userData === null) {
            return null;
        }

        /** @var FrontendUser|null $frontendUser */
        $frontendUser = $this->persistenceManager->getObjectByIdentifier($userData['uid'], FrontendUser::class);
        if (!$frontendUser instanceof FrontendUser) {
            return null;
        }

        $profileForDefaultLanguage = $this->createProfileFromFrontendUser($userData);
        $profileForDefaultLanguage->getFrontendUsers()->attach($frontendUser);
        $this->persistenceManager->add($profileForDefaultLanguage);

        $this->persistenceManager->persistAll();

        $afterProfileUpdatedEvent = new AfterProfileUpdateEvent($profileForDefaultLanguage);
        $this->eventDispatcher->dispatch($afterProfileUpdatedEvent);

        return $profileForDefaultLanguage->getUid();
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    abstract protected function createProfileFromFrontendUser(array $frontendUserData): Profile;

    private function initializeExtensionConfigurationOptions(ExtensionConfiguration $extensionConfiguration): void
    {
        try {
            $academicPersonsConfiguration = $extensionConfiguration->get('academic_persons');
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException) {
            $academicPersonsConfiguration = [];
        }
        $this->autoCreateProfiles = ((int)($academicPersonsConfiguration['autoCreateProfiles'] ?? 0) !== 0);
        $createProfileForUserGroups = (string)($academicPersonsConfiguration['createProfileForUserGroups'] ?? '');
        $this->userGroupsToCreateProfilesFor = $createProfileForUserGroups === ''
            ? []
            : GeneralUtility::intExplode(',', $createProfileForUserGroups, true);
    }
}
