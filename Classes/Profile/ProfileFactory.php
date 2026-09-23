<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Profile;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * The profile factory used when no listener of the ChooseProfileFactoryEvent
 * names another one. What it writes is the `frontendUserSync` map of
 * `Configuration/AcademicPersons/Settings.yaml`, applied by
 * {@see FrontendUserProfileMapper}; this class owns the records: the profile,
 * and the imported contract it creates, updates or removes. It refuses a map
 * that reads a column the frontend user record does not have, before it
 * writes anything.
 */
#[Autoconfigure(public: true, shared: true)]
final class ProfileFactory extends AbstractProfileFactory
{
    public function __construct(
        private readonly FrontendUserProfileMapper $frontendUserProfileMapper,
    ) {}

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    protected function createProfileFromFrontendUser(array $frontendUserData): Profile
    {
        $pid = (int)$frontendUserData['pid'];

        if ($pid < 0) {
            throw new \InvalidArgumentException(
                'The PID must be a positive integer.',
                1627471234
            );
        }
        /** @var int<0, max> $pid */
        $profile = new Profile();
        $profile->setPid($pid);
        $this->applyProfileData($frontendUserData, $profile);
        if (!$this->frontendUserProfileMapper->mapsContract()) {
            return $profile;
        }

        $importIdentifier = sprintf('%s:%s', 'fe_users', $frontendUserData['uid']);
        $contract = new Contract();
        $contract->setImportIdentifier($importIdentifier);
        $contract->setPid($pid);
        $profile->getContracts()->attach($contract);
        $this->frontendUserProfileMapper->applyContract($frontendUserData, $contract, $pid);

        return $profile;
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    protected function updateProfileFromFrontendUser(array $frontendUserData, Profile $profile): void
    {
        /** @var int<0, max> $pid */
        $pid = (int)$frontendUserData['pid'];
        $this->applyProfileData($frontendUserData, $profile);
        if (!$this->frontendUserProfileMapper->mapsContract()) {
            // The map names no source of the contract: it is not synchronised, and an
            // imported one stays as it is rather than being removed for want of data.
            return;
        }
        $importIdentifier = sprintf('%s:%s', 'fe_users', $frontendUserData['uid']);
        $contracts = $profile->getContracts();
        $contract = null;
        foreach ($contracts as $checkContract) {
            if ($checkContract->getImportIdentifier() === $importIdentifier) {
                $contract = $checkContract;
                break;
            }
        }
        $hasContractData = $this->frontendUserProfileMapper->hasContractData($frontendUserData);
        if ($contract !== null && !$hasContractData) {
            // No contract data, remove previous attached contract.
            // Note that $contracts->detach() would only remove the relation and making the record
            // orphan (unconnected) and removing (deleting) it is used keep the database clean
            // and allows to use the history to restore them.
            $this->persistenceManager->remove($contract);
            return;
        }
        if ($contract === null && !$hasContractData) {
            // No contract and no contract data, nothing to do.
            return;
        }
        if ($contract === null) {
            $contract = new Contract();
            $contract->setImportIdentifier($importIdentifier);
            $contract->setPid($pid);
            $profile->getContracts()->attach($contract);
        }
        $this->frontendUserProfileMapper->applyContract($frontendUserData, $contract, $pid);
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    private function applyProfileData(array $frontendUserData, Profile $profile): void
    {
        $this->frontendUserProfileMapper->assertColumnsExist($frontendUserData);
        $importIdentifier = sprintf('%s:%s', 'fe_users', $frontendUserData['uid']);
        $profile->setImportIdentifier($importIdentifier);
        $this->frontendUserProfileMapper->applyProfile($frontendUserData, $profile);
    }
}
