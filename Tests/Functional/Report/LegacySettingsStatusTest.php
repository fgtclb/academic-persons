<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Report;

use FGTCLB\AcademicPersons\Report\LegacySettingsStatus;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\Registry\StatusRegistry;

/**
 * With EXT:reports loaded, the compiler pass of `Services.php` registers the
 * status provider and the autoconfiguration of EXT:reports tags it, so it is
 * one of the providers the status registry collects and reports a warning per
 * package still shipping the pre-3.0 keys.
 *
 * The registry is asked rather than the report that renders it: the two core
 * versions aggregate the providers in different classes -
 * `Report\Status\Status` on v13, `Service\StatusService` on v14 - while
 * `StatusRegistry`, `StatusProviderInterface` and `Status` are identical on
 * both.
 */
final class LegacySettingsStatusTest extends AbstractAcademicPersonsTestCase
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
        'tests/test-legacy-settings',
    ];

    #[Test]
    public function theLegacyPackageIsReportedAsAWarning(): void
    {
        $providers = array_filter(
            $this->get(StatusRegistry::class)->getProviders(),
            static fn(object $provider): bool => $provider instanceof LegacySettingsStatus,
        );
        $this->assertCount(1, $providers, 'The provider is registered and tagged as a status provider');
        $provider = array_pop($providers);

        $statuses = $provider->getStatus();

        $this->assertCount(1, $statuses);
        $status = $statuses[0];
        $this->assertSame('Settings overrides', $status->getTitle());
        $this->assertSame('test_legacy_settings', $status->getValue());
        $this->assertSame(ContextualFeedbackSeverity::WARNING, $status->getSeverity());
        $this->assertStringContainsString('legacy keys "validations"', $status->getMessage());
        $this->assertStringContainsString('academic:persons:settings:migrate', $status->getMessage());
        // The report resolves the provider label itself, so it is a language reference.
        $label = 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_reports.xlf:status.label';
        $this->assertSame($label, $provider->getLabel());
        $this->assertSame(
            'Academic Persons',
            $this->get(LanguageServiceFactory::class)->create('default')->sL($label),
        );
    }
}
