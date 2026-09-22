<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\ViewHelpers;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * `persons:contracts` rendered from a fixture template, outside a page rendering: the ViewHelper
 * is resolved from the container with its selector, reads plugin settings or a detail
 * block, and limits the lifetime of a cache data collector only when the request carries
 * one. The plugin tests render it inside a frontend page.
 */
final class ContractsViewHelperTest extends AbstractAcademicPersonsTestCase
{
    private const NOW = '2026-03-10 14:30:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->get(Context::class)->setAspect('date', new DateTimeAspect(new \DateTimeImmutable(self::NOW)));
    }

    private function profile(): Profile
    {
        /** @var ObjectStorage<Contract> $contracts */
        $contracts = new ObjectStorage();
        foreach ([
            ['Emeritus', null, '2025-12-31'],
            ['Professor', '2026-01-01', '2026-03-20'],
            ['Dean', null, null],
        ] as [$position, $validFrom, $validTo]) {
            $contract = new Contract();
            $contract->setPosition($position);
            $contract->setValidFrom($validFrom === null ? null : new \DateTime($validFrom . ' 00:00:00'));
            $contract->setValidTo($validTo === null ? null : new \DateTime($validTo . ' 00:00:00'));
            $contracts->attach($contract);
        }
        $profile = new Profile();
        $profile->setContracts($contracts);

        return $profile;
    }

    /**
     * @param array<string, mixed> $variables
     * @param bool $withRequest False renders with no request in the rendering context at all
     */
    private function render(
        string $template,
        array $variables,
        ?CacheDataCollector $cacheDataCollector = null,
        bool $withRequest = true,
    ): string {
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        if ($cacheDataCollector !== null) {
            $request = $request->withAttribute('frontend.cache.collector', $cacheDataCollector);
        }
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: [__DIR__ . '/../Fixtures/Templates/ContractsViewHelper/'],
            request: $withRequest ? $request : null,
        ));
        $view->assignMultiple($variables);

        return trim($view->render($template));
    }

    #[Test]
    public function withoutSettingsEveryContractIsReturned(): void
    {
        $this->assertSame('[Emeritus][Professor][Dean]', $this->render('PluginSettings', ['profile' => $this->profile()]));
    }

    #[Test]
    public function pluginSettingsSelectTheContracts(): void
    {
        $this->assertSame(
            '[Professor]',
            $this->render('PluginSettings', [
                'profile' => $this->profile(),
                'settings' => ['contracts' => ['display' => 'first', 'onlyValid' => '1']],
            ]),
        );
    }

    /**
     * A detail block and plugin settings reach the same template in the list-and-detail
     * plugin; the block is what the detail view is configured with.
     */
    #[Test]
    public function aDetailBlockWinsOverThePluginSettings(): void
    {
        $this->assertSame(
            '[Professor][Dean]',
            $this->render('DetailBlock', [
                'profile' => $this->profile(),
                'settings' => ['contracts' => ['display' => 'first']],
                'detailBlock' => ['special' => 'datasFromContracts', 'contracts' => 'all', 'onlyValid' => true],
            ]),
        );
    }

    /**
     * No page is rendered, so there is no page cache to limit: the contracts are returned
     * all the same.
     */
    #[Test]
    public function withoutACacheDataCollectorValidityIsAppliedAndNothingFails(): void
    {
        $this->assertSame(
            '[Professor][Dean]',
            $this->render('PluginSettings', [
                'profile' => $this->profile(),
                'settings' => ['contracts' => ['onlyValid' => '1']],
            ]),
        );
    }

    /**
     * "Professor" ends on 2026-03-20, so the output changes at midnight on 2026-03-21.
     */
    #[Test]
    public function theLifetimeEndsWhenTheSelectionChanges(): void
    {
        $cacheDataCollector = new CacheDataCollector();

        $this->render(
            'PluginSettings',
            ['profile' => $this->profile(), 'settings' => ['contracts' => ['onlyValid' => '1']]],
            $cacheDataCollector,
        );

        $this->assertSame(
            (new \DateTimeImmutable('2026-03-21 00:00:00'))->getTimestamp() - (new \DateTimeImmutable(self::NOW))->getTimestamp(),
            $cacheDataCollector->resolveLifetime(),
        );
    }

    /**
     * The detail view limits the lifetime the same way, from its block of `Settings.yaml`.
     */
    #[Test]
    public function aDetailBlockWithOnlyValidLimitsTheLifetime(): void
    {
        $cacheDataCollector = new CacheDataCollector();

        $this->render(
            'DetailBlock',
            [
                'profile' => $this->profile(),
                'detailBlock' => ['special' => 'datasFromContracts', 'contracts' => 'all', 'onlyValid' => true],
            ],
            $cacheDataCollector,
        );

        $this->assertSame(
            (new \DateTimeImmutable('2026-03-21 00:00:00'))->getTimestamp() - (new \DateTimeImmutable(self::NOW))->getTimestamp(),
            $cacheDataCollector->resolveLifetime(),
        );
    }

    /**
     * A rendering context without a request - a template rendered from a command or a
     * backend module that sets none - has no page cache to limit and must not fail.
     */
    #[Test]
    public function withoutARequestValidityIsAppliedAndNothingFails(): void
    {
        $this->assertSame(
            '[Professor][Dean]',
            $this->render(
                'PluginSettings',
                ['profile' => $this->profile(), 'settings' => ['contracts' => ['onlyValid' => '1']]],
                withRequest: false,
            ),
        );
    }

    /**
     * Without a profile there is no contract to show, as there was none when a template
     * looped over the contracts of a missing profile.
     */
    #[Test]
    public function withoutAProfileNoContractIsReturned(): void
    {
        $this->assertSame('', $this->render('PluginSettings', ['settings' => ['contracts' => ['onlyValid' => '1']]]));
    }

    #[Test]
    public function withoutOnlyValidTheLifetimeIsLeftAlone(): void
    {
        $cacheDataCollector = new CacheDataCollector();

        $this->render(
            'PluginSettings',
            ['profile' => $this->profile(), 'settings' => ['contracts' => ['display' => 'first']]],
            $cacheDataCollector,
        );

        $this->assertSame(PHP_INT_MAX, $cacheDataCollector->resolveLifetime());
    }
}
