<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Controller;

use Fgtclb\AcademicPersons\Domain\Model\Dto\ProfileDemand;
use Fgtclb\AcademicPersons\Domain\Model\Profile;
use Fgtclb\AcademicPersons\Domain\Repository\ProfileRepository;
use Fgtclb\AcademicPersons\Event\ModifyDetailProfileEvent;
use Fgtclb\AcademicPersons\Event\ModifyListProfilesEvent;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

final class ProfileController extends ActionController
{
    private ProfileRepository $profileRepository;

    private ContentObjectRenderer $contentObject;

    private Typo3Version $versionInformation;

    /** @var array<string, mixed> */
    private array $contentObjectData;

    public function injectProfileRepository(ProfileRepository $profileRepository): void
    {
        $this->profileRepository = $profileRepository;
    }

    public function initializeAction(): void
    {
        // Initialize version information
        $this->versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

        // Initialize content object
        $this->contentObject = $this->getContentObject();

        // Initialize query settings
        $this->profileRepository->setDefaultQuerySettings($this->getQuerySettings());
    }

    public function initializeListAction(): void
    {
        $demandArray = [];
        if ($this->request->hasArgument('demand')) {
            $demandArray = $this->request->getArgument('demand');
            if (!is_array($demandArray)) {
                $demandArray = [];
            }
        }

        $demandArray = array_replace_recursive($demandArray, $this->settings['demand'] ?? []);
        $propertyMappingConfiguration = $this->arguments->getArgument('demand')->getPropertyMappingConfiguration();
        $propertyMappingConfiguration->allowProperties(...array_keys(array_merge($this->settings['demand'] ?? [], ['currentPage' => 1])));
        $propertyMappingConfiguration->allowProperties(...array_keys(array_merge($this->settings['demand'] ?? [], ['alphabetFilter' => ''])));
        $propertyMappingConfiguration->skipUnknownProperties();

        $this->request = $this->request->withArgument('demand', $demandArray);
    }

    public function listAction(ProfileDemand $demand): ResponseInterface
    {
        $profiles = $this->profileRepository->findByDemand($demand);

        /** @var ModifyListProfilesEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyListProfilesEvent($profiles, $this->view));
        $profiles = $event->getProfiles();

        if ($demand->getAlphabetFilter() !== '') {
            $this->settings['paginationEnabled'] = '0';
        }

        if (($this->settings['paginationEnabled'] ?? null) === '1') {
            $resultsPerPage = (int)($this->settings['pagination']['resultsPerPage'] ?? 10);
            $numberOfPaginationLinks = (int)($this->settings['pagination']['numberOfLinks'] ?? 5);
            $paginator = new QueryResultPaginator($profiles, $demand->getCurrentPage(), $resultsPerPage);
            if (
                ExtensionManagementUtility::isLoaded('numbered_pagination')
                && class_exists('\\GeorgRinger\\NumberedPagination\\NumberedPagination')
            ) {
                $pagination = new \GeorgRinger\NumberedPagination\NumberedPagination($paginator, $numberOfPaginationLinks);
            } else {
                $pagination = new SimplePagination($paginator, $numberOfPaginationLinks);
            }

            $this->view->assignMultiple([
                'paginator' => $paginator,
                'pagination' => $pagination,
            ]);
        }

        // If profiles were selected manually, sort them by order in selection
        if (!empty($this->settings['demand']['profileList'])) {
            $selectedProfiles = [];
            $profileUidArray = GeneralUtility::intExplode(',', $this->settings['demand']['profileList'], true);
            foreach ($profileUidArray as $uid) {
                foreach ($profiles as $profile) {
                    if ($profile->getUid() === $uid) {
                        $selectedProfiles[] = $profile;
                    }
                }
            }
            $profiles = $selectedProfiles;
        }

        $this->view->assignMultiple([
            'data' => $this->contentObject->data,
            'profiles' => $profiles,
            'demand' => $demand,
        ]);

        $this->addCacheTag('profile_list_view');

        return $this->htmlResponse();
    }

    /**
     * @IgnoreValidation("profile")
     */
    public function detailAction(?Profile $profile = null): ResponseInterface
    {
        if ($profile === null) {
            GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $this->request,
                'The requested profile does not exist',
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
            die();
        }

        /** @var ModifyDetailProfileEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyDetailProfileEvent($profile, $this->view));
        $profile = $event->getProfile();

        $this->view->assign('profile', $profile);

        $this->addCacheTag('profile_detail_view');
        $this->addCacheTag(sprintf('profile_detail_view_%d', $profile->getUid()));

        return $this->htmlResponse();
    }

    private function getContentObject(): ContentObjectRenderer
    {
        // With version TYPO3 v12 the access to the content object renderer has changed
        // @see https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/RequestLifeCycle/RequestAttributes/CurrentContentObject.html
        if (version_compare($this->versionInformation->getVersion(), '12.0.0', '>=')) {
            $contentObject = $this->request->getAttribute('currentContentObject');
        } else {
            $contentObject = $this->configurationManager->getContentObject();
        }

        return $contentObject;
    }

    private function addCacheTag(string $tag): void
    {
        // With version TYPO3 v13.3 the method addCacheTags() has been marked as deprecated.
        // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.3/Deprecation-102422-TypoScriptFrontendController-addCacheTags.html
        if (version_compare($this->versionInformation->getVersion(), '12.0.0', '>=')) {
            $this->request->getAttribute('frontend.cache.collector')->addCacheTags(
                new CacheTag($tag)
            );
        } else {
            $this->contentObject->getTypoScriptFrontendController()?->addCacheTags([
                $tag,
            ]);
        }
    }

    private function getQuerySettings(): Typo3QuerySettings
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $querySettings = new Typo3QuerySettings($context, $this->configurationManager);

        if (!empty($this->contentObjectData['pages'])) {
            $querySettings->setStoragePageIds(
                GeneralUtility::intExplode(',', $this->contentObjectData['pages'])
            );
        } else {
            $querySettings->setRespectStoragePage(false);
        }

        if (isset($this->settings['fallbackForNonTranslated'])
            && (int)$this->settings['fallbackForNonTranslated'] === 1
        ) {
            // With version TYPO3 v12.0 some the method setLanguageOverlayMode() is removed.
            // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-97926-ExtbaseQuerySettingsMethodsRemoved.html
            if (version_compare($this->versionInformation->getVersion(), '12.0.0', '>=')) {
                $currentLanguageAspect = $querySettings->getLanguageAspect();
                $changedLanguageAspect = new LanguageAspect(
                    $currentLanguageAspect->getId(),
                    $currentLanguageAspect->getContentId(),
                    LanguageAspect::OVERLAYS_ON,
                    $currentLanguageAspect->getFallbackChain()
                );
                $querySettings->setLanguageAspect($changedLanguageAspect);
            } else {
                $querySettings->setLanguageOverlayMode(true);
            }
        }

        return $querySettings;
    }
}
