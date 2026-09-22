<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicPersons\DemandValues\AlphabetFilterLetters;
use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileDemand;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TESTS\TestProfileQueryConstraints\EventListener\ReplaceProfileDemandListener;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `ProfileRepository::findAlphabetFilterLetters()` answers, for every letter, whether the list
 * the same demand renders for that letter is empty (ACE-597).
 *
 * Two kinds of test, because either alone can pass with a wrong answer: the parity test
 * compares every letter with the list's own result, and would pass if both sides were wrong
 * in the same way; the absolute expectations pin what the fixtures mean, and would pass
 * with a query that happens to match these fixtures only. The fixtures give every rule of the
 * list query a letter of its own, so a rule the letter query misses shows up as one wrong
 * letter.
 *
 * Dunn's start time is 2038-01-01, the last new year a signed 32-bit column holds. Core
 * declares the start time unsigned, which PostgreSQL has no type for, so there it is signed.
 * The fixture has to move before that date.
 *
 * Every scenario runs under a frontend request, because the letter navigation is rendered in
 * the frontend only and Extbase applies the frontend's enable fields and versioning rules
 * there - without one, a group restricted profile and a record new in a workspace would be
 * listed live. The language and the workspace come from the context aspects, which each test
 * sets before the repository creates its query.
 */
final class ProfileRepositoryAlphabetFilterLettersTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->addCoreExtension('typo3/cms-workspaces');
        $this->addTestExtension('tests/test-profile-query-constraints');
        parent::setUp();
        // Extbase reads the TypoScript setup of a frontend request to build the query settings;
        // an empty one is enough, the repository sets everything it relies on itself.
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.acme.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
    }

    protected function tearDown(): void
    {
        ReplaceProfileDemandListener::$functionTypes = null;
        ReplaceProfileDemandListener::$alphabetFilter = null;
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    /**
     * The scenarios the parity test runs: the fixture set, the language and its overlay
     * type, the workspace, and what the content element would have put into the demand.
     *
     * @return array<string, array{
     *     fixture: string,
     *     language: int,
     *     overlayType: string,
     *     workspace: int,
     *     storagePages?: string,
     *     showHiddenRecords?: bool,
     *     functionTypes?: list<int>,
     *     organisationalUnits?: list<int>,
     *     fallbackForNonTranslated?: bool
     * }>
     */
    private static function scenarios(): array
    {
        $live = ['language' => 0, 'overlayType' => LanguageAspect::OVERLAYS_ON, 'workspace' => 0];
        $german = ['language' => 1, 'workspace' => 0];

        return [
            'enable fields' => ['fixture' => 'enableFields'] + $live,
            'enable fields, hidden records shown' => ['fixture' => 'enableFields', 'showHiddenRecords' => true] + $live,
            'storage folder 100' => ['fixture' => 'storagePages', 'storagePages' => '100'] + $live,
            'storage folder 101' => ['fixture' => 'storagePages', 'storagePages' => '101'] + $live,
            'no storage folder' => ['fixture' => 'storagePages', 'storagePages' => ''] + $live,
            'contracts' => ['fixture' => 'contracts'] + $live,
            'function type 1' => ['fixture' => 'contracts', 'functionTypes' => [1]] + $live,
            'organisational unit 2' => ['fixture' => 'contracts', 'organisationalUnits' => [2]] + $live,
            'default language' => ['fixture' => 'languages'] + $live,
            'German, strict' => ['fixture' => 'languages', 'overlayType' => LanguageAspect::OVERLAYS_ON] + $german,
            'German, fallback' => ['fixture' => 'languages', 'overlayType' => LanguageAspect::OVERLAYS_MIXED] + $german,
            'German, free' => ['fixture' => 'languages', 'overlayType' => LanguageAspect::OVERLAYS_OFF] + $german,
            'German, strict with floating records' => ['fixture' => 'languages', 'overlayType' => LanguageAspect::OVERLAYS_ON_WITH_FLOATING] + $german,
            'German, strict with the fallback option' => ['fixture' => 'languages', 'overlayType' => LanguageAspect::OVERLAYS_ON, 'fallbackForNonTranslated' => true] + $german,
            'live workspace' => ['fixture' => 'workspaces'] + $live,
            'workspace 1' => ['fixture' => 'workspaces', 'workspace' => 1] + $live,
            'umlaut' => ['fixture' => 'umlauts'] + $live,
        ];
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function scenarioDataProvider(): \Generator
    {
        foreach (array_keys(self::scenarios()) as $scenario) {
            yield $scenario => [$scenario];
        }
    }

    /**
     * The letters each scenario makes available, every other one is not. The umlaut is
     * missing on purpose: under which letter "Özil" is listed is a question of the DBMS
     * collation - O on MariaDB and MySQL, none on PostgreSQL and SQLite - and only its parity
     * with the list is asserted.
     *
     * @return \Generator<string, array{0: string, 1: list<string>}>
     */
    public static function expectedLettersDataProvider(): \Generator
    {
        // Hidden, deleted, not yet started, expired, group restricted and nameless all drop out.
        yield 'enable fields' => ['enable fields', ['a']];
        // The option lifts the hidden flag and no other enable field.
        yield 'enable fields, hidden records shown' => ['enable fields, hidden records shown', ['a', 'b']];
        yield 'storage folder 100' => ['storage folder 100', ['g']];
        yield 'storage folder 101' => ['storage folder 101', ['h']];
        yield 'no storage folder' => ['no storage folder', ['g', 'h']];
        yield 'contracts' => ['contracts', ['i', 'j', 'k']];
        yield 'function type 1' => ['function type 1', ['i', 'k']];
        yield 'organisational unit 2' => ['organisational unit 2', ['j', 'k']];
        // Nash exists in German only, Price's default record is hidden.
        yield 'default language' => ['default language', ['l', 'm', 'o']];
        // Moore has no translation, Nash has no default record.
        yield 'German, strict' => ['German, strict', ['l', 'o']];
        yield 'German, fallback' => ['German, fallback', ['l', 'm', 'o']];
        // Records in the language on their own: Price's German record no longer depends on its hidden default.
        yield 'German, free' => ['German, free', ['l', 'n', 'o', 'p']];
        yield 'German, strict with floating records' => ['German, strict with floating records', ['l', 'n', 'o']];
        yield 'German, strict with the fallback option' => ['German, strict with the fallback option', ['l', 'm', 'o']];
        // Quinn exists in workspace 1 only, Reed's delete placeholder is not live either.
        yield 'live workspace' => ['live workspace', ['r', 's']];
        // Within the documented limitation: Reed still counts, and Stone's version renamed
        // to Young is found under S - exactly as the list's own count finds them.
        yield 'workspace 1' => ['workspace 1', ['q', 'r', 's']];
    }

    /**
     * Sets the context of a scenario, imports its fixture and returns its demand.
     */
    private function setUpScenario(string $scenario): ProfileDemand
    {
        $settings = self::scenarios()[$scenario];
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AlphabetFilterLetters/' . $settings['fixture'] . '.csv');

        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(
            $settings['language'],
            $settings['language'],
            $settings['overlayType'],
            $settings['language'] > 0 ? [0] : [],
        ));
        $context->setAspect('workspace', new WorkspaceAspect($settings['workspace']));

        $demand = new ProfileDemand();
        $demand->setStoragePages($settings['storagePages'] ?? '');
        $demand->setShowHiddenRecords($settings['showHiddenRecords'] ?? false);
        $demand->setFunctionTypes($settings['functionTypes'] ?? []);
        $demand->setOrganisationalUnits($settings['organisationalUnits'] ?? []);
        if ($settings['fallbackForNonTranslated'] ?? false) {
            $demand->setFallbackForNonTranslated(1);
        }

        return $demand;
    }

    private function getProfileRepository(): ProfileRepository
    {
        return $this->get(ProfileRepository::class);
    }

    /**
     * @param list<string> $available
     * @return array<string, bool>
     */
    private function lettersWith(array $available): array
    {
        $letters = array_fill_keys(AlphabetFilterLetters::LETTERS, false);
        foreach ($available as $letter) {
            $letters[$letter] = true;
        }

        return $letters;
    }

    #[Test]
    public function everyLetterOfTheNavigationIsAnswered(): void
    {
        $demand = $this->setUpScenario('enable fields');

        $letters = $this->getProfileRepository()->findAlphabetFilterLetters($demand);

        $this->assertSame(AlphabetFilterLetters::LETTERS, array_keys($letters));
    }

    /**
     * @param list<string> $available
     */
    #[DataProvider('expectedLettersDataProvider')]
    #[Test]
    public function lettersAreAvailableExactlyWhereTheScenarioHasProfiles(string $scenario, array $available): void
    {
        $demand = $this->setUpScenario($scenario);

        $this->assertSame($this->lettersWith($available), $this->getProfileRepository()->findAlphabetFilterLetters($demand));
    }

    /**
     * The core property: a letter is available exactly when the list for it is not empty.
     * Compared with the list's own count, which is SQL like the letters and therefore holds in
     * a workspace as well, and live also with the records the list hands to the view, after
     * the language overlay.
     */
    #[DataProvider('scenarioDataProvider')]
    #[Test]
    public function everyLetterIsAvailableExactlyWhenItsListIsNotEmpty(string $scenario): void
    {
        $demand = $this->setUpScenario($scenario);
        $repository = $this->getProfileRepository();

        $letters = $repository->findAlphabetFilterLetters($demand);

        $live = self::scenarios()[$scenario]['workspace'] === 0;
        foreach (AlphabetFilterLetters::LETTERS as $letter) {
            $letterDemand = clone $demand;
            $letterDemand->setAlphabetFilter($letter);
            $this->assertSame(
                $repository->findByDemand($letterDemand)->count() > 0,
                $letters[$letter],
                sprintf('Letter "%s" disagrees with the count of its list.', $letter),
            );
            if ($live) {
                $this->assertSame(
                    count($repository->findByDemand($letterDemand)->toArray()) > 0,
                    $letters[$letter],
                    sprintf('Letter "%s" disagrees with the records of its list.', $letter),
                );
            }
        }
    }

    /**
     * A caller outside the frontend - a command, a backend module - has no frontend
     * versioning rules in the query, and records of a workspace would be counted live. The
     * letters restrict to the current workspace the way the list's own count does, so Quinn,
     * new in workspace 1, stays out of the live answer there as well.
     */
    #[Test]
    public function withoutAFrontendRequestOnlyLiveRecordsCountLive(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        $demand = $this->setUpScenario('live workspace');
        $repository = $this->getProfileRepository();

        $letters = $repository->findAlphabetFilterLetters($demand);

        $this->assertSame($this->lettersWith(['r', 's']), $letters);
        $quinn = (clone $demand)->setAlphabetFilter('q');
        $this->assertSame(0, $repository->findByDemand($quinn)->count(), 'The list\'s own count agrees.');
    }

    /**
     * The letter a visitor selected narrows the list, never the navigation: with A selected,
     * the other letters are answered as if none was.
     */
    #[Test]
    public function theSelectedLetterDoesNotNarrowTheOtherLetters(): void
    {
        $demand = $this->setUpScenario('contracts');
        $demand->setAlphabetFilter('i');

        $this->assertSame($this->lettersWith(['i', 'j', 'k']), $this->getProfileRepository()->findAlphabetFilterLetters($demand));
        $this->assertSame('i', $demand->getAlphabetFilter(), 'The demand of the caller is left alone.');
    }

    /**
     * A manual selection ignores the letter filter, so every letter yields the whole
     * selection - which is what the list does with a letter there.
     */
    #[Test]
    public function everyLetterIsAvailableForAManualSelection(): void
    {
        $demand = $this->setUpScenario('enable fields');
        $demand->setProfileList('1');

        $this->assertSame($this->lettersWith(AlphabetFilterLetters::LETTERS), $this->getProfileRepository()->findAlphabetFilterLetters($demand));
    }

    #[Test]
    public function aReplacedDemandOfAListenerNarrowsTheLetters(): void
    {
        $demand = $this->setUpScenario('contracts');
        ReplaceProfileDemandListener::$functionTypes = [1];

        $this->assertSame($this->lettersWith(['i', 'k']), $this->getProfileRepository()->findAlphabetFilterLetters($demand));
    }

    /**
     * A listener that asks for a letter of its own narrows the list it is called for, and
     * still not the navigation.
     */
    #[Test]
    public function aLetterAListenerAsksForDoesNotNarrowTheLetters(): void
    {
        $demand = $this->setUpScenario('contracts');
        ReplaceProfileDemandListener::$alphabetFilter = 'j';

        $this->assertSame($this->lettersWith(['i', 'j', 'k']), $this->getProfileRepository()->findAlphabetFilterLetters($demand));
    }

    /**
     * The constraint of a `ModifyProfileQueryEvent` listener narrows the list, so it narrows
     * the letters. The listener reads the plugin settings from the context, which proves the
     * context arrives as well.
     */
    #[Test]
    public function aConstraintOfAQueryListenerNarrowsTheLetters(): void
    {
        $demand = $this->setUpScenario('contracts');
        $context = new PluginControllerActionContext(new ServerRequest('https://www.acme.com/'), ['testQueryLastName' => 'Kerr']);

        $this->assertSame($this->lettersWith(['k']), $this->getProfileRepository()->findAlphabetFilterLetters($demand, $context));
    }
}
