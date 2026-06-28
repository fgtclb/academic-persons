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
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
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
    protected ProfileRepository $profileRepository;
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
    }

    #[Required]
    public function injectPersistenceManagerInterface(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    #[Required]
    public function injectProfileRepository(ProfileRepository $profileRepository): void
    {
        $this->profileRepository = $profileRepository;
    }

    public function initializeObject(): void
    {
        $this->initializeExtensionConfigurationOptions($this->extensionConfiguration);
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

        $frontendUser = $this->findFrontendUserIgnoringVisibility((int)$userData['uid']);
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
     * Resolves the frontend user for the profile creation ignoring its visibility, so a profile is
     * created for a disabled frontend user as well. The visibility itself is never changed here.
     * Deleted frontend users stay excluded.
     */
    private function findFrontendUserIgnoringVisibility(int $frontendUserUid): ?FrontendUser
    {
        $query = $this->persistenceManager->createQueryForType(FrontendUser::class);
        $query->getQuerySettings()
            ->setRespectStoragePage(false)
            ->setIgnoreEnableFields(true);
        $query->matching($query->equals('uid', $frontendUserUid));
        /** @var FrontendUser|null $frontendUser */
        $frontendUser = $query->execute()->getFirst();
        return $frontendUser;
    }

    public function shouldUpdateProfileForUser(FrontendUserAuthentication $frontendUserAuthentication): bool
    {
        // @todo consider options / record flag and/or PSR-14 event to determine if profile should be updated
        //       updating with `EXT:academic_person_edit` does not make much sense or requires a extended custom
        //       factory and field disabling or similar.
        //       @see self::shouldCreateProfileForUser() for extension configuration option example !
        return true;
    }

    public function updateProfileForUser(FrontendUserAuthentication $frontendUserAuthentication): void
    {
        /** @var array<string, int|string|null>|null $userData */
        $userData = $frontendUserAuthentication->user;
        if ($userData === null) {
            return;
        }

        $frontendUserUid = (int)$userData['uid'];
        // Include hidden profiles so they keep being synchronized. The visibility (`hidden` enable
        // field) is never changed here, therefore a manually hidden profile stays hidden.
        $profiles = $this->profileRepository->findByFrontendUser($frontendUserUid, true);
        if ($profiles->count() === 0) {
            return;
        }

        foreach ($profiles as $profile) {
            $this->updateProfileFromFrontendUser($userData, $profile);
            $this->persistenceManager->update($profile);
        }

        $this->persistenceManager->persistAll();
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    abstract protected function createProfileFromFrontendUser(array $frontendUserData): Profile;

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    protected function updateProfileFromFrontendUser(array $frontendUserData, Profile $profile): void
    {
        // NOOP - does not update anything on abstract level intentionally.
    }

    private function initializeExtensionConfigurationOptions(ExtensionConfiguration $extensionConfiguration): void
    {
        try {
            $academicPersonsConfiguration = $extensionConfiguration->get('academic_persons');
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException) {
            $academicPersonsConfiguration = [];
        }
        $this->autoCreateProfiles = ((int)($academicPersonsConfiguration['profile']['autoCreateProfiles'] ?? 0) !== 0);
        $createProfileForUserGroups = (string)($academicPersonsConfiguration['profile']['createProfileForUserGroups'] ?? '');
        $this->userGroupsToCreateProfilesFor = $createProfileForUserGroups === ''
            ? []
            : GeneralUtility::intExplode(',', $createProfileForUserGroups, true);
    }
}
