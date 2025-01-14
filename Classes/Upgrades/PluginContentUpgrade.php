<?php

declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Upgrades;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('academicPerson_pluginContent')]
final class PluginContentUpgrade implements UpgradeWizardInterface
{
    /**
     * @deprecated with v11 and remove with v12, remove after drop support for v11
     */
    public function getIdentifier(): string
    {
        return 'academicPerson_pluginContent';
    }

    public function getTitle(): string
    {
        return 'Migrate plugin list element form academic_persons to normal content elements';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');
        foreach (['academicpersons_detail', 'academicpersons_listanddetail', 'academicpersons_list'] as $contentType) {
            $connection->update(
                'tt_content',
                [
                    'CType' => $contentType,
                ],
                [
                    'CType' => 'list',
                    'list_type' => $contentType,
                ]
            );
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        return true;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }
}
