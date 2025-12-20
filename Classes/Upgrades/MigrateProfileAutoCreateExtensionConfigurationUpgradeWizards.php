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
        return '';
    }

    public function executeUpdate(): bool
    {
        $this->extensionConfiguration->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions();
        $persons = $this->getExtensionConfiguration('academic_persons');
        $update = $persons;
        $personsEdit = $this->getExtensionConfiguration('academic_persons_edit');
        if ((int)($persons['profile']['autoCreateProfiles']) === 0 && (int)($personsEdit['profile']['autoCreateProfiles']) === 1) {
            $update['profile']['autoCreateProfiles'] = 1;
        }
        if ($persons['profile']['createProfileForUserGroups'] === '' && $personsEdit['profile']['createProfileForUserGroups'] !== '') {
            $update['profile']['createProfileForUserGroups'] = $personsEdit['profile']['createProfileForUserGroups'];
        }
        if ($update !== $persons) {
            $this->extensionConfiguration->set('academic_persons', $update);
        }
        $this->extensionConfiguration->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions();
        return true;
    }

    public function updateNecessary(): bool
    {
        return ExtensionManagementUtility::isLoaded('academic_persons_edit');
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
