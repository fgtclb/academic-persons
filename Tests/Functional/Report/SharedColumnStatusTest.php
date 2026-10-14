<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Report;

use FGTCLB\AcademicPersons\Report\LegacySettingsStatus;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\Registry\StatusRegistry;
use TYPO3\CMS\Reports\Status;

/**
 * A site package declares a second profile field on the column of the shipped
 * teaching area field. The persons settings entry of the status report names
 * the column with the severity the upgrade check gives it, see
 * `UpgradeCheckSharedColumnTest`.
 */
final class SharedColumnStatusTest extends AbstractAcademicPersonsTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
        'typo3/cms-reports',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'tests/test-settings-shared-column',
    ];

    #[Test]
    public function theSharedColumnIsReportedAsAWarning(): void
    {
        $statuses = array_values(array_filter(
            $this->statuses(),
            static fn(Status $status): bool => $status->getTitle() === 'Shared column',
        ));

        $this->assertCount(1, $statuses);
        $status = $statuses[0];
        $this->assertSame('tx_academicpersons_domain_model_profile.teaching_area', $status->getValue());
        $this->assertSame(ContextualFeedbackSeverity::WARNING, $status->getSeverity());
        $this->assertStringContainsString(
            'The fields "teachingArea", "teachingAreaSummary" of the persons settings all name this column.',
            $status->getMessage(),
        );
        $this->assertStringContainsString(
            'It takes the backend flags of "teachingAreaSummary", the field applied last,',
            $status->getMessage(),
        );
    }

    /**
     * The provider is registered only while EXT:reports is loaded and is not
     * public, so it is taken from the registry that collects it.
     *
     * @return Status[]
     */
    private function statuses(): array
    {
        $providers = array_filter(
            $this->get(StatusRegistry::class)->getProviders(),
            static fn(object $provider): bool => $provider instanceof LegacySettingsStatus,
        );
        $this->assertCount(1, $providers);
        return array_pop($providers)->getStatus();
    }
}
