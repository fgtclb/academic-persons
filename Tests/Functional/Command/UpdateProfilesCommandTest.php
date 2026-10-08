<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Command;

use FGTCLB\AcademicPersons\Command\UpdateProfilesCommand;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * `academic:updateprofiles` as a console command: what it accepts and what it reports.
 * What it writes is covered by the tests of `ProfileUpdateCommandService`, whose
 * fixture is used here. Page 100 holds the frontend users 10 and 14, page 110 the
 * users 12 and 16, each with a profile. Frontend user 10 is named "Max1", its
 * profile 1 still "Max".
 */
final class UpdateProfilesCommandTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->addCoreExtension('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Service/ProfileUpdateCommandService/Fixtures/DataSets/site-structure.csv');
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
        $tester = new CommandTester($this->get(UpdateProfilesCommand::class));
        $tester->execute($input);
        return $tester;
    }

    private function getFirstNameOfProfileOne(): string
    {
        return (string)$this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->select(['first_name'], 'tx_academicpersons_domain_model_profile', ['uid' => 1])
            ->fetchOne();
    }

    #[Test]
    public function theUpdatedFrontendUsersAreCounted(): void
    {
        $tester = $this->runCommand(['--include-pids' => '100']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame('Profiles of 2 frontend user(s) updated.' . PHP_EOL, $tester->getDisplay());
        $this->assertSame('Max1', $this->getFirstNameOfProfileOne());
    }

    #[Test]
    public function aRunWithNothingToUpdateSaysSo(): void
    {
        $tester = $this->runCommand(['--include-pids' => '3']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame('Profiles of 0 frontend user(s) updated.' . PHP_EOL, $tester->getDisplay());
    }

    public static function invalidPageListDataSets(): \Generator
    {
        yield 'include, no number at all' => [['--include-pids' => 'abc']];
        yield 'include, one part no number' => [['--include-pids' => '100,abc']];
        yield 'include, a negative uid' => [['--include-pids' => '-100']];
        yield 'exclude, wrong separator' => [['--exclude-pids' => '110;1100']];
    }

    /**
     * Such a list used to shrink to the uids it could read, and `--exclude-pids 110;1100`
     * excluded page 110 only and updated the profiles of page 1100, without a word.
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
        $this->assertSame('Max', $this->getFirstNameOfProfileOne());
    }
}
