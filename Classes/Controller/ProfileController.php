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
use Fgtclb\AcademicPersons\Domain\Repository\ContractRepository;
use Fgtclb\AcademicPersons\Domain\Repository\ProfileRepository;
use Fgtclb\AcademicPersons\Event\ModifyDetailProfileEvent;
use Fgtclb\AcademicPersons\Event\ModifyListProfilesEvent;
use GeorgRinger\NumberedPagination\NumberedPagination;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

final class ProfileController extends ActionController
{
    private ProfileRepository $profileRepository;

    public function injectProfileRepository(ProfileRepository $profileRepository): void
    {
        $this->profileRepository = $profileRepository;
    }

    private ContractRepository $contractRepository;

    public function injectContractRepository(ContractRepository $contractRepository): void
    {
        $this->contractRepository = $contractRepository;
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

        $this->settings['showFields'] = !empty($this->settings['showFields']) ? GeneralUtility::trimExplode(',', $this->settings['showFields']) : null;
    }

    public function listAction(ProfileDemand $demand): ResponseInterface
    {
        $this->adoptSettings($demand);
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
            if (ExtensionManagementUtility::isLoaded('numbered_pagination')
                && class_exists(NumberedPagination::class)
            ) {
                $pagination = new NumberedPagination($paginator, $numberOfPaginationLinks);
            } else {
                $pagination =new SimplePagination($paginator);
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
            'data' => $this->getContentObject()?->data,
            'profiles' => $profiles,
            'demand' => $demand,
        ]);
        $this->addCacheTags('profile_list_view');

        return $this->htmlResponse();
    }

    /**
     * @todo This action is literally broken in multi language sites. Needs to be adopted and covered
     *       with functional tests.
     *
     * @return ResponseInterface
     */
    public function cardAction(): ResponseInterface
    {
        $profiles = [];
        if (isset($this->settings['demand'])
            && is_array($this->settings['demand'])
            && isset($this->settings['demand']['profileList'])
            && is_string($this->settings['demand']['profileList'])
            && $this->settings['demand']['profileList'] !== ''
        ) {
            $profileDemand = new ProfileDemand();
            $profileDemand->setProfileList($this->settings['demand']['profileList']);
            /**
             * Introduced with https://github.com/fgtclb/academic-persons/pull/30 to have the option to display profiles in
             * fallback mode even when site language (non-default) is configured to be in strict mode.
             *
             * {@see AcademicPersonsListAndDetailPluginTest::fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder()}
             * {@see AcademicPersonsListPluginTest::fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder()}
             */
            $fallbackForNonTranslated = (int)($this->settings['fallbackForNonTranslated'] ?? 0);
            if ($fallbackForNonTranslated === 1) {
                $profileDemand->setFallbackForNonTranslated($fallbackForNonTranslated);
            }
            $profiles = $this->profileRepository->findByDemand($profileDemand);
        }

        // @todo Add enforced sorting based on selected uid's order, similar to listAction/selectedProfilesAction ?

        $this->view->assignMultiple([
            'data' => $this->getContentObject()?->data,
            'profiles' => $profiles,
        ]);

        return $this->htmlResponse();
    }

    /**
     * @IgnoreValidation("profile")
     */
    public function detailAction(?Profile $profile = null): ResponseInterface
    {
        if ($profile === null) {
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $this->request,
                'The requested profile does not exist.',
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
        }

        /** @var ModifyDetailProfileEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyDetailProfileEvent($profile, $this->view));
        $profile = $event->getProfile();

        $this->view->assign('profile', $profile);
        $this->addCacheTags(
            'profile_detail_view',
            sprintf('profile_detail_view_%d', $profile->getUid()),
        );

        return $this->htmlResponse();
    }

    public function initializeSelectedProfilesAction(): void
    {
        $this->settings['showFields'] = !empty($this->settings['showFields']) ? GeneralUtility::trimExplode(',', $this->settings['showFields']) : null;
    }

    public function selectedProfilesAction(): ResponseInterface
    {
        if (empty($this->settings['selectedProfiles'])) {
            return $this->htmlResponse();
        }

        $profileUids = GeneralUtility::intExplode(',', $this->settings['selectedProfiles'], true);
        $profiles = $this->profileRepository->findByUids($profileUids);

        /** @var ModifyListProfilesEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyListProfilesEvent($profiles, $this->view));
        $profiles = $event->getProfiles();

        // Sort profiles by order in selection
        $sortedProfiles = [];
        foreach ($profileUids as $uid) {
            foreach ($profiles as $profile) {
                if ($profile->getUid() === $uid) {
                    $sortedProfiles[] = $profile;
                }
            }
        }

        $this->view->assignMultiple([
            'data' => $this->getContentObject()?->data,
            'profiles' => $sortedProfiles,
        ]);

        return $this->htmlResponse();
    }

    public function initializeSelectedContractsAction(): void
    {
        $this->settings['showFields'] = !empty($this->settings['showFields']) ? GeneralUtility::trimExplode(',', $this->settings['showFields']) : null;
    }

    public function selectedContractsAction(): ResponseInterface
    {
        if (empty($this->settings['selectedContracts'])) {
            return $this->htmlResponse();
        }

        $contractUids = GeneralUtility::intExplode(',', $this->settings['selectedContracts'], true);
        $contracts = $this->contractRepository->findByUids($contractUids);

        // Sort profiles by order in selection
        $sortedContracts = [];
        foreach ($contractUids as $uid) {
            foreach ($contracts as $contract) {
                if ($contract->getUid() === $uid) {
                    $sortedContracts[] = $contract;
                }
            }
        }

        $this->view->assignMultiple([
            'data' => $this->getContentObject()?->data,
            'contracts' => $sortedContracts,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Adopt plugin settings and `tt_content.pages`.
     */
    private function adoptSettings(ProfileDemand $demand): void
    {
        $contentObjectData = $this->getContentObject()?->data;
        $hasStoragePids = (
            is_array($contentObjectData)
            && !empty($contentObjectData['pages'])
            && is_string($contentObjectData['pages'])
        );
        if ($hasStoragePids) {
            $demand->setStoragePages($contentObjectData['pages']);
        }
        /**
         * Introduced with https://github.com/fgtclb/academic-persons/pull/30 to have the option to display profiles in
         * fallback mode even when site language (non-default) is configured to be in strict mode.
         *
         * {@see AcademicPersonsListAndDetailPluginTest::fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder()}
         * {@see AcademicPersonsListPluginTest::fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder()}
         */
        $fallbackForNonTranslated = (int)($this->settings['fallbackForNonTranslated'] ?? 0);
        if ($fallbackForNonTranslated === 1) {
            $demand->setFallbackForNonTranslated($fallbackForNonTranslated);
        }
    }

    /**
     * Add cache tags to the current page.
     *
     * This method adds provided $tags to the current page,
     * using the correct API based on the current TYPO3
     * version.
     *
     * @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.3/Deprecation-102422-TypoScriptFrontendController-addCacheTags.html
     *
     * @param string ...$tags
     */
    private function addCacheTags(string ...$tags): void
    {
        if ((new Typo3Version())->getMajorVersion() < 13) {
            // @todo Drop this when removing TYPO3 v12 support.
            $this->getContentObject()?->getTypoScriptFrontendController()?->addCacheTags($tags);
            // return;
        }
        // @todo Enable when TYPO3 v13 support is added with 2.x.x
        //        $cacheCollector = $this->request->getAttribute('frontend.cache.collector');
        //        foreach ($tags as $tag) {
        //            $cacheCollector?->addCacheTags(new CacheTag($tag));
        //        }
    }

    private function getContentObject(): ?ContentObjectRenderer
    {
        // @todo Simply return the attribute when TYPO3 v11 support is removed with 2.x.x.
        // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.4/Deprecation-100662-ConfigurationManager-getContentObject.html
        return match((new Typo3Version())->getMajorVersion()) {
            11 => $this->configurationManager->getContentObject(),
            default => $this->request->getAttribute('currentContentObject'),
        };
    }
}
