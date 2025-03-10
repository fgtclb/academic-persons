<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Controller;

use Fgtclb\AcademicPersons\Domain\Model\Dto\DemandInterface;
use Fgtclb\AcademicPersons\Domain\Model\Dto\ProfileDemand;
use Fgtclb\AcademicPersons\Domain\Model\Profile;
use Fgtclb\AcademicPersons\Domain\Repository\ContractRepository;
use Fgtclb\AcademicPersons\Domain\Repository\ProfileRepository;
use Fgtclb\AcademicPersons\Event\ModifyDetailProfileEvent;
use Fgtclb\AcademicPersons\Event\ModifyListProfilesEvent;
use GeorgRinger\NumberedPagination\NumberedPagination;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
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
            $pagination = new NumberedPagination($paginator, $numberOfPaginationLinks);
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
            'data' => $this->configurationManager->getContentObject()?->data,
            'profiles' => $profiles,
            'demand' => $demand,
        ]);

        $this->configurationManager->getContentObject()?->getTypoScriptFrontendController()?->addCacheTags([
            'profile_list_view',
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

        $this->configurationManager->getContentObject()?->getTypoScriptFrontendController()?->addCacheTags([
            'profile_detail_view',
            sprintf('profile_detail_view_%d', $profile->getUid()),
        ]);

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
            'data' => $this->configurationManager->getContentObject()?->data,
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
            'data' => $this->configurationManager->getContentObject()?->data,
            'contracts' => $sortedContracts,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Adopt plugin settings and `tt_content.pages`.
     *
     * @param DemandInterface $demand
     */
    private function adoptSettings(ProfileDemand $demand): void
    {
        $contentObjectData = $this->configurationManager->getContentObject()?->data;
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
}
