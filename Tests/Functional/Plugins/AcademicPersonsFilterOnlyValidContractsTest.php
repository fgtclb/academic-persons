<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TESTS\TestProfileQueryConstraints\EventListener\ReplaceProfileDemandListener;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Which profiles a list selects through their contracts while it shows only the contracts
 * valid today: through its restriction to units or function types, and through a visitor
 * filter, a profile counts only by a contract the list can show.
 *
 * | Profile    | Contract                           | Unit    | Function type | Valid               |
 * |------------|------------------------------------|---------|---------------|---------------------|
 * | Ann Archer | Professor of Physics               | Physics | Professor     | always, stored as 0 |
 * | Bob Brown  | Professor of Physics, retired      | Physics | Professor     | until 2020-12-31    |
 * |            | Lecturer in Biology                | Biology | Lecturer      | always              |
 * | Cid Cole   | Professor of Physics, appointed    | Physics | Professor     | from in 30 days     |
 * | Dee Dunn   | Professor of Biology               | Biology | Professor     | from 2021-01-01     |
 * | Eve Evans  | Lecturer in Physics, retired       | Physics | Lecturer      | until 2020-12-31    |
 *
 * A backend save stores an empty date as 0, a record written otherwise may carry NULL:
 * Ann's dates are 0 and Dee's end is 0, Bob's, Cid's and Eve's other dates are NULL.
 */
final class AcademicPersonsFilterOnlyValidContractsTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const PROFESSOR = 1;
    private const PHYSICS = 1;
    private const APPOINTED_CONTRACT = 4;

    private const EVERYBODY = ['Ann Archer', 'Bob Brown', 'Cid Cole', 'Dee Dunn', 'Eve Evans'];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        // The testing framework replaces the page cache by a NullBackend. The
                        // database backend is restored, so the lifetime of the entry a rendering
                        // writes can be read back.
                        'pages' => [
                            'backend' => Typo3DatabaseBackend::class,
                        ],
                    ],
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-profile-query-constraints');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsFilterOnlyValidContracts/records.csv');
        // Relative to the run, so "not started yet" stays true whenever the suite runs.
        $this->setValidFrom(self::APPOINTED_CONTRACT, $this->todayMidnight()->modify('+30 days'));
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/DetailPage.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/CachePeriodTwoDays.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration('EN', '/'),
        ]);
    }

    protected function tearDown(): void
    {
        ReplaceProfileDemandListener::$functionTypes = null;
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function todayMidnight(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp((int)$GLOBALS['EXEC_TIME'])->setTime(0, 0);
    }

    private function setValidFrom(int $contract, \DateTimeImmutable $day): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_academicpersons_domain_model_contract')
            ->update('tx_academicpersons_domain_model_contract', ['valid_from' => $day->getTimestamp()], ['uid' => $contract]);
    }

    /**
     * @param array<string, string> $settings FlexForm fields below `settings.`
     */
    private function addListElement(array $settings): void
    {
        $fields = '';
        foreach (array_merge(['fallbackForNonTranslated' => '0'], $settings) as $name => $value) {
            $fields .= sprintf('<field index="settings.%s"><value index="vDEF">%s</value></field>', $name, htmlspecialchars($value));
        }
        $this->getConnectionPool()->getConnectionForTable('tt_content')->insert('tt_content', [
            'uid' => 1,
            'pid' => 2,
            'CType' => 'academicpersons_list',
            'header' => '',
            'pages' => '100',
            'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
                . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                . $fields
                . '</language></sheet></data></T3FlexForms>',
        ]);
    }

    /**
     * The list with demand arguments, carrying the cHash a link of the list would carry.
     *
     * @param array<string, mixed> $demand
     */
    private function render(array $demand = []): \DOMXPath
    {
        $url = 'https://www.acme.com/home';
        if ($demand !== []) {
            $query = HttpUtility::buildQueryString(['tx_academicpersons_list' => ['demand' => $demand]]);
            $url .= '?' . $query . '&cHash=' . GeneralUtility::makeInstance(CacheHashCalculator::class)
                ->generateForParameters('id=2&' . $query);
        }
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $this->renderFrontendPage($url), LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * @return list<string>
     */
    private function texts(\DOMXPath $xpath, string $query): array
    {
        $nodes = $xpath->query($query);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);
        $texts = [];
        foreach ($nodes as $node) {
            $texts[] = trim((string)preg_replace('#\s+#u', ' ', $node->textContent));
        }

        return $texts;
    }

    private function hasClass(string $class): string
    {
        return sprintf("contains(concat(' ', normalize-space(@class), ' '), ' %s ')", $class);
    }

    /**
     * @return list<string>
     */
    private function listedNames(\DOMXPath $xpath): array
    {
        return $this->texts($xpath, sprintf('//*[%s]', $this->hasClass('academic-persons-item__name')));
    }

    /**
     * The letters the letter navigation links, the active one included.
     *
     * @return list<string>
     */
    private function availableLetters(\DOMXPath $xpath): array
    {
        $letters = $this->texts($xpath, sprintf(
            '//nav[%s]//li[not(%s)]/*[1]',
            $this->hasClass('academic-persons-list__alphabet-pagination'),
            $this->hasClass('disabled'),
        ));
        return array_values(array_filter(array_map('strtolower', $letters), static fn(string $letter): bool => strlen($letter) === 1));
    }

    /**
     * The numbers of the pages the pagination offers.
     *
     * @return list<string>
     */
    private function pageNumbers(\DOMXPath $xpath): array
    {
        $numbers = $this->texts($xpath, sprintf('//nav[%s]//li/*[1]', $this->hasClass('academic-persons-list__pagination')));
        return array_values(array_filter($numbers, static fn(string $number): bool => ctype_digit($number)));
    }

    private function pageCacheExpires(): int
    {
        $rows = $this->getConnectionPool()->getConnectionForTable('cache_pages')
            ->select(['expires'], 'cache_pages')
            ->fetchAllAssociative();
        $this->assertCount(1, $rows, 'The rendered page did not reach the page cache.');

        return (int)$rows[0]['expires'];
    }

    /**
     * @return \Generator<string, array{0: string, 1: list<string>}>
     */
    public static function onlyValidDataProvider(): \Generator
    {
        yield 'only valid' => ['1', ['Ann Archer', 'Dee Dunn']];
        yield 'all contracts' => ['0', ['Ann Archer', 'Bob Brown', 'Cid Cole', 'Dee Dunn']];
    }

    /**
     * Bob is a professor only by a contract that has ended, Cid only by one that has not
     * started. While the list shows only valid contracts, the filter does not find them by
     * those contracts.
     *
     * @param list<string> $expected
     */
    #[DataProvider('onlyValidDataProvider')]
    #[Test]
    public function aVisitorFilterMatchesOnlyThroughContractsTheListShows(string $onlyValid, array $expected): void
    {
        $this->addListElement(['filter.functionType' => '1', 'contracts.onlyValid' => $onlyValid]);

        $this->assertSame($expected, $this->listedNames($this->render(['functionTypeFilter' => self::PROFESSOR])));
    }

    /**
     * @return \Generator<string, array{0: string, 1: list<string>}>
     */
    public static function restrictionDataProvider(): \Generator
    {
        yield 'only valid' => ['1', ['Ann Archer']];
        yield 'all contracts' => ['0', ['Ann Archer', 'Bob Brown', 'Cid Cole', 'Eve Evans']];
    }

    /**
     * The restriction of the element to a unit selects the same way: in Physics, only Ann
     * holds a contract valid today.
     *
     * @param list<string> $expected
     */
    #[DataProvider('restrictionDataProvider')]
    #[Test]
    public function theRestrictionOfTheElementMatchesOnlyThroughContractsTheListShows(string $onlyValid, array $expected): void
    {
        $this->addListElement(['organisationalUnits' => (string)self::PHYSICS, 'contracts.onlyValid' => $onlyValid]);

        $this->assertSame($expected, $this->listedNames($this->render()));
    }

    /**
     * Without a condition on contracts the list selects nobody by a contract: Eve, whose only
     * contract has ended, stays listed without one.
     */
    #[Test]
    public function aListWithoutConditionsOnContractsKeepsEveryProfile(): void
    {
        $this->addListElement(['contracts.onlyValid' => '1']);

        $this->assertSame(self::EVERYBODY, $this->listedNames($this->render()));
    }

    /**
     * The pagination and the letter navigation count the profiles the filtered list shows.
     */
    #[Test]
    public function thePaginationAndTheLettersCountTheSameProfiles(): void
    {
        $this->addListElement([
            'filter.functionType' => '1',
            'contracts.onlyValid' => '1',
            'paginationEnabled' => '1',
            'pagination.resultsPerPage' => '1',
            'alphabetPaginationEnabled' => '1',
        ]);

        $xpath = $this->render(['functionTypeFilter' => self::PROFESSOR]);

        $this->assertSame(['Ann Archer'], $this->listedNames($xpath));
        $this->assertSame(['1', '2'], $this->pageNumbers($xpath));
        $this->assertSame(['a', 'd'], $this->availableLetters($xpath));
    }

    /**
     * @return \Generator<string, array{0: array<string, string>, 1: array<string, mixed>, 2: list<string>}>
     */
    public static function conditionsDataProvider(): \Generator
    {
        yield 'visitor filter' => [['filter.functionType' => '1'], ['functionTypeFilter' => self::PROFESSOR], ['Ann Archer', 'Dee Dunn']];
        yield 'restriction of the element' => [['organisationalUnits' => (string)self::PHYSICS], [], ['Ann Archer']];
    }

    /**
     * Cid is appointed from tomorrow. The list does not show him today, and the page
     * rendered today is not served any more once tomorrow has begun - whether the list
     * finds professors by a visitor filter or Physics by the restriction of the element.
     *
     * @param array<string, string> $settings
     * @param array<string, mixed> $demand
     * @param list<string> $listedToday
     */
    #[DataProvider('conditionsDataProvider')]
    #[Test]
    public function aMatchingContractOfAnUnlistedProfileEndsThePageCacheWhenItStarts(array $settings, array $demand, array $listedToday): void
    {
        $tomorrow = $this->todayMidnight()->modify('+1 day');
        $this->setValidFrom(self::APPOINTED_CONTRACT, $tomorrow);
        $this->addListElement($settings + ['contracts.onlyValid' => '1']);

        $this->assertSame($listedToday, $this->listedNames($this->render($demand)));
        $this->assertSame($tomorrow->getTimestamp(), $this->pageCacheExpires());
    }

    /**
     * A listener of `ModifyProfileDemandEvent` that restricts the list to professors makes
     * it depend on the date the same way a restriction of the element does, and Cid's
     * appointment from tomorrow caps the page cache as well.
     */
    #[Test]
    public function aConditionAListenerAddsCapsThePageCache(): void
    {
        $tomorrow = $this->todayMidnight()->modify('+1 day');
        $this->setValidFrom(self::APPOINTED_CONTRACT, $tomorrow);
        ReplaceProfileDemandListener::$functionTypes = [self::PROFESSOR];
        $this->addListElement(['contracts.onlyValid' => '1']);

        $this->assertSame(['Ann Archer', 'Dee Dunn'], $this->listedNames($this->render()));
        $this->assertSame($tomorrow->getTimestamp(), $this->pageCacheExpires());
    }

    /**
     * The storage pages of the list bound the contracts that cap its page cache: a
     * professorship that starts tomorrow in a folder the list does not read changes
     * nothing on this page.
     */
    #[Test]
    public function aMatchingContractOutsideTheStoragePagesDoesNotCapThePageCache(): void
    {
        $tomorrow = $this->todayMidnight()->modify('+1 day');
        $this->setValidFrom(self::APPOINTED_CONTRACT, $tomorrow);
        $this->getConnectionPool()->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->update('tx_academicpersons_domain_model_profile', ['pid' => 101], ['uid' => 3]);
        $this->addListElement(['filter.functionType' => '1', 'contracts.onlyValid' => '1']);

        $this->assertSame(['Ann Archer', 'Dee Dunn'], $this->listedNames($this->render(['functionTypeFilter' => self::PROFESSOR])));
        $this->assertGreaterThan($tomorrow->getTimestamp(), $this->pageCacheExpires());
    }

    /**
     * Dee's professorship ends today. The first page of the filtered list shows Ann only, so
     * nothing it renders ends, but tomorrow the list has one page instead of two: the page
     * rendered today is not served any more once tomorrow has begun.
     */
    #[Test]
    public function aMatchingContractThatEndsTodayOnAnotherPageEndsThePageCacheAtMidnight(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_academicpersons_domain_model_contract')
            ->update('tx_academicpersons_domain_model_contract', ['valid_to' => $this->todayMidnight()->getTimestamp()], ['uid' => 5]);
        $this->addListElement([
            'filter.functionType' => '1',
            'contracts.onlyValid' => '1',
            'paginationEnabled' => '1',
            'pagination.resultsPerPage' => '1',
        ]);

        $xpath = $this->render(['functionTypeFilter' => self::PROFESSOR]);

        $this->assertSame(['Ann Archer'], $this->listedNames($xpath));
        $this->assertSame(['1', '2'], $this->pageNumbers($xpath));
        $this->assertSame($this->todayMidnight()->modify('+1 day')->getTimestamp(), $this->pageCacheExpires());
    }

    /**
     * Without "only valid" nothing the list selects depends on the date, so the page keeps
     * a lifetime beyond tomorrow even though Cid is appointed from tomorrow.
     */
    #[Test]
    public function withoutOnlyValidThePageCacheIsNotCappedByMatchingContracts(): void
    {
        $tomorrow = $this->todayMidnight()->modify('+1 day');
        $this->setValidFrom(self::APPOINTED_CONTRACT, $tomorrow);
        $this->addListElement(['filter.functionType' => '1', 'contracts.onlyValid' => '0']);

        $this->render(['functionTypeFilter' => self::PROFESSOR]);

        $this->assertGreaterThan($tomorrow->getTimestamp(), $this->pageCacheExpires());
    }

    /**
     * Whether only valid contracts count is the editor's choice, never the visitor's: a
     * request that asks for it changes nothing.
     */
    #[Test]
    public function aVisitorCannotSwitchTheValidityRuleOn(): void
    {
        $this->addListElement(['filter.functionType' => '1', 'contracts.onlyValid' => '0']);

        $this->assertSame(
            ['Ann Archer', 'Bob Brown', 'Cid Cole', 'Dee Dunn'],
            $this->listedNames($this->render(['functionTypeFilter' => self::PROFESSOR, 'onlyValidContracts' => '1'])),
        );
    }
}
