<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Hook;

use FGTCLB\AcademicPersons\DataHandling\ProfileWriteCorrelation;
use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use FGTCLB\AcademicPersons\Event\ProfileUpdateOrigin;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The announcement of a DataHandler save: which runs dispatch
 * {@see AfterProfileUpdateEvent}, how often, and with which site and origin. A
 * recording listener takes the place of the ones `academic_persons_edit` adds, so
 * that what is counted is the dispatch, not what a listener does with it.
 */
final class DataHandlerHooksProfileUpdateTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    private const TABLE_PROFILE = 'tx_academicpersons_domain_model_profile';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
        'typo3/cms-workspaces',
    ];

    /**
     * @var \ArrayObject<int, AfterProfileUpdateEvent>
     */
    private \ArrayObject $events;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/BeUsers.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PageTree.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AnnouncedProfiles.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $this->writeSiteConfiguration(
            identifier: 'main',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
        $events = new \ArrayObject();
        $this->events = $events;
        $container = $this->get('service_container');
        $container->set(
            'recording-profile-update-listener',
            static function (AfterProfileUpdateEvent $event) use ($events): void {
                $events->append($event);
            },
        );
        $container->get(ListenerProvider::class)
            ->addListener(AfterProfileUpdateEvent::class, 'recording-profile-update-listener');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    #[Test]
    public function aBackendSaveAnnouncesTheProfileOnceWithItsSite(): void
    {
        $this->runDataHandler([self::TABLE_PROFILE => [1 => ['last_name' => 'McDuck']]]);

        $this->assertSame([[1, 'main', ProfileUpdateOrigin::Backend]], $this->recordedEvents());
    }

    #[Test]
    public function aSaveOfOnlyATranslationAnnouncesNothing(): void
    {
        $this->runDataHandler([self::TABLE_PROFILE => [2 => ['first_name' => 'Onkel']]]);

        $this->assertSame([], $this->recordedEvents());
    }

    #[Test]
    public function everyProfileOfARunIsAnnouncedOnceWithItsUid(): void
    {
        $dataHandler = $this->runDataHandler([
            self::TABLE_PROFILE => [
                'NEW1' => ['pid' => 2, 'first_name' => 'Huey', 'last_name' => 'Duck'],
                'NEW2' => ['pid' => 2, 'first_name' => 'Dewey', 'last_name' => 'Duck'],
                1 => ['last_name' => 'McDuck'],
            ],
        ]);

        $newUids = [(int)$dataHandler->substNEWwithIDs['NEW1'], (int)$dataHandler->substNEWwithIDs['NEW2']];
        $this->assertSame(
            [
                [1, 'main', ProfileUpdateOrigin::Backend],
                [$newUids[0], 'main', ProfileUpdateOrigin::Backend],
                [$newUids[1], 'main', ProfileUpdateOrigin::Backend],
            ],
            $this->recordedEvents(),
        );
    }

    /**
     * Hidden, and its end time is in 2000: the synchronisation keeps such a profile
     * up to date, so it is announced like any other (ACE-667). The run happens in a
     * frontend request, the one context that tells the lookup from a narrower one: a
     * backend request makes Extbase ignore every enable field, and without a request
     * its backend path drops every one of them as soon as any is ignored.
     */
    #[Test]
    public function aProfileOutsideItsVisibilityWindowIsAnnounced(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.acme.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);

        $this->runDataHandler([self::TABLE_PROFILE => [3 => ['last_name' => 'Gearloose-Duck']]]);

        $this->assertSame([[3, 'main', ProfileUpdateOrigin::Backend]], $this->recordedEvents());
    }

    #[Test]
    public function aProfileOnAPageOfNoSiteIsAnnouncedWithoutASite(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);

        $this->runDataHandler([self::TABLE_PROFILE => [1 => ['last_name' => 'McDuck']]]);

        $this->assertSame([[1, null, ProfileUpdateOrigin::Backend]], $this->recordedEvents());
    }

    #[Test]
    public function aRunMarkedAsAnImportIsAnnouncedWithTheImportOrigin(): void
    {
        $this->runDataHandler(
            [self::TABLE_PROFILE => [1 => ['last_name' => 'McDuck']]],
            ProfileWriteCorrelation::Import,
        );

        $this->assertSame([[1, 'main', ProfileUpdateOrigin::Import]], $this->recordedEvents());
    }

    #[Test]
    public function aRunMarkedAsInternalIsNotAnnounced(): void
    {
        $this->runDataHandler(
            [self::TABLE_PROFILE => [1 => ['last_name' => 'McDuck']]],
            ProfileWriteCorrelation::Internal,
        );

        $this->assertSame([], $this->recordedEvents());
    }

    #[Test]
    public function aSaveInAWorkspaceIsNotAnnounced(): void
    {
        $GLOBALS['BE_USER']->workspace = 1;

        $this->runDataHandler([self::TABLE_PROFILE => [1 => ['last_name' => 'McDuck']]]);

        $this->assertSame([], $this->recordedEvents());
    }

    /**
     * A copy is a command, and commands are not announced. The DataHandler writes the
     * copied profile through a nested instance of its own, whose datamap would
     * otherwise be taken for a save. Profile 3 is copied because it has no
     * translation, which the storage folder could not take.
     */
    #[Test]
    public function aCopiedProfileIsNotAnnounced(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [self::TABLE_PROFILE => [3 => ['copy' => 2]]]);
        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertNotSame([], $dataHandler->copyMappingArray_merged[self::TABLE_PROFILE] ?? [], 'Precondition: the profile was copied.');
        $this->assertSame([], $this->recordedEvents());
    }

    /**
     * @param array<string, array<int|string, array<string, mixed>>> $datamap
     */
    private function runDataHandler(array $datamap, ?ProfileWriteCorrelation $correlation = null): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, []);
        if ($correlation !== null) {
            $dataHandler->setCorrelationId($correlation->create());
        }
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog);
        return $dataHandler;
    }

    /**
     * @return list<array{0: int|null, 1: string|null, 2: ProfileUpdateOrigin}>
     */
    private function recordedEvents(): array
    {
        $recorded = [];
        foreach ($this->events as $event) {
            $recorded[] = [$event->getProfile()->getUid(), $event->getSite()?->getIdentifier(), $event->getOrigin()];
        }
        return $recorded;
    }
}
