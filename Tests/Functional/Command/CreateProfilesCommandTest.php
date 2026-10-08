<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Command;

use FGTCLB\AcademicPersons\Command\CreateProfilesCommand;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * `academic:createprofiles` as a console command: what it accepts and what it reports.
 * Which profiles it creates is covered by the tests of `ProfileCreateCommandService`,
 * whose fixture is used here. Page 100 holds the frontend users 10 and 14 of the
 * synchronised record type, page 110 the users 12 and 16, none of them has a profile.
 */
final class CreateProfilesCommandTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    private const FIXTURES = __DIR__ . '/../Service/ProfileCreateCommandService/Fixtures';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->addCoreExtension('typo3/cms-fluid-styled-content');
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            ['EXTENSIONS' => ['academic_persons' => ['profile' => ['autoCreateProfiles' => 1]]]],
        );
        parent::setUp();
        $this->importCSVDataSet(self::FIXTURES . '/site-structure.csv');
        foreach ([1 => 'site-one', 1001 => 'site-two'] as $pageId => $identifier) {
            $this->setUpFrontendRootPage($pageId, [
                'constants' => ['EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript'],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Service/ProfileCreateCommandService/Fixtures/TypoScript/Setup/setup.typoscript',
                ],
            ]);
            $this->writeSiteConfiguration(
                $identifier,
                $this->buildSiteConfiguration($pageId, sprintf('https://%s.acme.com/', $identifier)),
                [$this->buildDefaultLanguageConfiguration('EN', '/')],
            );
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    private function runCommand(array $input = []): CommandTester
    {
        $tester = new CommandTester($this->get(CreateProfilesCommand::class));
        $tester->execute($input);
        return $tester;
    }

    private function countProfiles(): int
    {
        return $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->count('*', 'tx_academicpersons_domain_model_profile', []);
    }

    #[Test]
    public function theCreatedProfilesAreCounted(): void
    {
        $tester = $this->runCommand(['--include-pids' => '100']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame('2 profile(s) created.' . PHP_EOL, $tester->getDisplay());
        $this->assertSame(2, $this->countProfiles());
    }

    #[Test]
    public function aRunWithNothingToCreateSaysSo(): void
    {
        $this->runCommand(['--include-pids' => '100']);

        $tester = $this->runCommand(['--include-pids' => '100']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame('0 profile(s) created.' . PHP_EOL, $tester->getDisplay());
    }

    #[Test]
    public function aDisabledAutomaticCreationIsNamed(): void
    {
        // Read once, when the profile factory is built, which happens with the command.
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['academic_persons']['profile']['autoCreateProfiles'] = 0;

        $tester = $this->runCommand();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringStartsWith('0 profile(s) created.' . PHP_EOL, $tester->getDisplay());
        $this->assertStringContainsString(
            'The automatic profile creation is disabled, so the default profile factory creates no profile.',
            $tester->getDisplay(),
        );
        $this->assertStringContainsString('profile.autoCreateProfiles', $tester->getDisplay());
        $this->assertSame(0, $this->countProfiles());
    }

    public static function invalidPageListDataSets(): \Generator
    {
        yield 'include, no number at all' => [['--include-pids' => 'abc']];
        yield 'include, one part no number' => [['--include-pids' => '100,abc']];
        yield 'include, a negative uid' => [['--include-pids' => '-100']];
        yield 'exclude, wrong separator' => [['--exclude-pids' => '100;110']];
    }

    /**
     * Such a list used to shrink to the uids it could read, page 0 for `abc`, and the
     * run created nothing or more than was meant, without a word.
     *
     * @param array<string, string> $input
     */
    #[DataProvider('invalidPageListDataSets')]
    #[Test]
    public function aPageListWithAPartThatIsNoUidIsRefused(array $input): void
    {
        $tester = $this->runCommand($input);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString(
            '--include-pids and --exclude-pids take a comma-separated list of page uids.',
            $tester->getDisplay(),
        );
        $this->assertSame(0, $this->countProfiles());
    }
}
