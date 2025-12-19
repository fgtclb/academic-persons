<?php

declare(strict_types=1);

namespace TESTS\TestMessyProfileFactory\Event\Listener;

use FGTCLB\AcademicPersons\Event\ChooseProfileFactoryEvent;
use FGTCLB\AcademicPersons\Profile\ProfileFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TESTS\TestMessyProfileFactory\Persons\MessyProfileFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\SiteFinder;

#[Autoconfigure(
    tags: [
        [
            'name' => 'event.listener',
            'identifier' => 'academic-persons-resolve-messy-profile-factory',
            'method' => 'resolveProfileFactory',
            'event' => ChooseProfileFactoryEvent::class,
        ],
    ],
    public: true,
)]
final class ProfileFactoryForFrontendUserAuthenticationResolver
{
    public function __construct(
        #[Autowire(service: MessyProfileFactory::class)]
        private readonly ProfileFactoryInterface $messyProfileFactory,
        private readonly SiteFinder $siteFinder,
    ) {}

    public function resolveProfileFactory(ChooseProfileFactoryEvent $event): void
    {
        // This simulates faking a global request like done in the one or other project,
        // which should be removed after having the environment state manager and builder
        // in place. We keep this to have test coverage that this does not break generic
        // working order.
        //
        // Main purpose is to ensure that this does not lead to
        //
        //  RuntimeException: Setup array has not been initialized. This happens in cached
        //  Frontend scope where full TypoScript is not needed by the system.
        //
        //  with TYPO3 v13.
        //  `CustomProfileFactoryMessingAroundWithEnvironmentStateTest::executeCreatesExpectedRecordsInDatabase()`
        //  fails in case is not properly handled by `EXT:academic_persons` in case project implementation is way
        //  off.
        if (!isset($GLOBALS['TYPO3_REQUEST'])) {
            $GLOBALS['TYPO3_REQUEST'] = $this->createProjectFakeServerRequest();
        }
        $event->setProfileFactory($this->messyProfileFactory);
    }

    private function createProjectFakeServerRequest(): ServerRequestInterface
    {
        $site = $this->siteFinder->getSiteByIdentifier('site-one');
        $request = new ServerRequest((string)$site->getBase(), 'GET');
        $request = $request
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('site', $site)
            ->withAttribute('language', $site->getDefaultLanguage());
        return $request;
    }
}
