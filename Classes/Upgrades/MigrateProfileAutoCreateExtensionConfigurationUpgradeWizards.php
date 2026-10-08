<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Upgrades;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard(identifier: 'academicPersons_MigrateProfileAutoCreateExtensionsConfiguration')]
final class MigrateProfileAutoCreateExtensionConfigurationUpgradeWizards implements UpgradeWizardInterface
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function getTitle(): string
    {
        return sprintf(
            'Migrate profile auto create options from "%s" to "%s"',
            'EXT:academic_persons_edit',
            'EXT:academic_persons',
        );
    }

    public function getDescription(): string
    {
        return 'Profiles are created by academic_persons since 2.1, which reads "profile.autoCreateProfiles" and'
            . ' "profile.createProfileForUserGroups" from its own extension configuration. The options of'
            . ' academic_persons_edit have no effect any more. Copies an enabled automatic creation and a list of'
            . ' user groups from academic_persons_edit to academic_persons, where the option still has its default.';
    }

    public function executeUpdate(): bool
    {
        $this->extensionConfiguration->synchronizeExtConfTemplateWithLocalConfiguration('academic_persons');
        $this->extensionConfiguration->synchronizeExtConfTemplateWithLocalConfiguration('academic_persons_edit');
        $persons = $this->getExtensionConfiguration('academic_persons');
        $update = $this->getMigratedConfiguration($persons, $this->getExtensionConfiguration('academic_persons_edit'));
        if ($update !== $persons) {
            $this->extensionConfiguration->set('academic_persons', $update);
        }
        $this->extensionConfiguration->synchronizeExtConfTemplateWithLocalConfiguration('academic_persons');
        $this->extensionConfiguration->synchronizeExtConfTemplateWithLocalConfiguration('academic_persons_edit');
        return true;
    }

    /**
     * Necessary only while academic_persons_edit holds a value that would change the
     * configuration of academic_persons. Loading the extension alone is no reason, its
     * options default to the defaults of academic_persons.
     */
    public function updateNecessary(): bool
    {
        if (!ExtensionManagementUtility::isLoaded('academic_persons_edit')) {
            return false;
        }
        $persons = $this->getExtensionConfiguration('academic_persons');
        return $this->getMigratedConfiguration($persons, $this->getExtensionConfiguration('academic_persons_edit')) !== $persons;
    }

    /**
     * @param array{profile: array{autoCreateProfiles: int, createProfileForUserGroups: string}} $persons
     * @param array{profile: array{autoCreateProfiles: int, createProfileForUserGroups: string}} $personsEdit
     * @return array{profile: array{autoCreateProfiles: int, createProfileForUserGroups: string}}
     */
    private function getMigratedConfiguration(array $persons, array $personsEdit): array
    {
        $update = $persons;
        if ((int)($persons['profile']['autoCreateProfiles']) === 0 && (int)($personsEdit['profile']['autoCreateProfiles']) === 1) {
            $update['profile']['autoCreateProfiles'] = 1;
        }
        if ($persons['profile']['createProfileForUserGroups'] === '' && $personsEdit['profile']['createProfileForUserGroups'] !== '') {
            $update['profile']['createProfileForUserGroups'] = $personsEdit['profile']['createProfileForUserGroups'];
        }
        return $update;
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    /**
     * @param string $extensionKey
     * @return array{
     *     profile: array{
     *         autoCreateProfiles: int,
     *         createProfileForUserGroups: string,
     *     },
     * }
     */
    private function getExtensionConfiguration(string $extensionKey): array
    {
        try {
            $configuration = $this->extensionConfiguration->get($extensionKey);
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException) {
            $configuration = [];
        }
        $configuration['profile']['autoCreateProfiles'] ??= 0;
        $configuration['profile']['createProfileForUserGroups'] ??= '';
        return $configuration;
    }
}
