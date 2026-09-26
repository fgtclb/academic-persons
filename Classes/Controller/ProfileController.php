<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Controller;

use FGTCLB\AcademicBase\Controller\GetCurrentContentRecordMethodTrait;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContext as PersonsPluginControllerActionContext;
use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileDemand;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Event\ModifyDetailProfileEvent;
use FGTCLB\AcademicPersons\Event\ModifyListProfilesEvent;
use FGTCLB\AcademicPersons\Event\ModifySelectedContractsEvent;
use FGTCLB\AcademicPersons\Event\ModifySelectedProfilesEvent;
use FGTCLB\AcademicPersons\PageTitle\ProfileTitleProvider;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use GeorgRinger\NumberedPagination\NumberedPagination;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

final class ProfileController extends ActionController
{
    use GetCurrentContentRecordMethodTrait;

    /**
     * The demand properties of the list a visitor sets through the request, and the only
     * ones its navigation links carry: the page, the letter and the view mode. Every other
     * property the property mapping allows is one of `settings.demand`, which the content
     * element sets and which wins over the request.
     *
     * A change that lets a visitor set another value adds it here, and the pagination and
     * the letter navigation carry it without an edit of their own.
     */
    private const VISITOR_DEMAND_PROPERTIES = ['currentPage', 'alphabetFilter', 'viewMode'];

    /**
     * A view mode names the partial `Profile/ViewMode/<Mode>.html` that renders it, so it
     * is never taken as it is: it has to be one of the modes the site allows, and it has to
     * be a plain name. The second check keeps a path out of a partial name even when the
     * allowed modes are misconfigured.
     */
    private const VIEW_MODE_PATTERN = '/^[a-z][a-zA-Z0-9]*$/';

    /**
     * The tile grid, the mode every list rendered before view modes existed. The stored
     * FlexForm value keeps its name, "list", although its label says "Tiles".
     */
    private const TILES_VIEW_MODE = 'list';

    /**
     * The shipped columns of the table that show a contract field, each the field
     * `contracts.<column>` of `settings.showFields`.
     */
    private const CONTRACT_TABLE_COLUMNS = ['position', 'organisationalUnit', 'emailAddresses', 'phoneNumbers', 'room'];

    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly ProfileTitleProvider $profileTitleProvider,
        private readonly AcademicPersonsSettings $academicPersonsSettings,
    ) {}

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
        $propertyMappingConfiguration->allowProperties(
            ...array_keys(array_merge($this->settings['demand'] ?? [], array_flip(self::VISITOR_DEMAND_PROPERTIES))),
        );
        $propertyMappingConfiguration->skipUnknownProperties();

        $this->request = $this->request->withArgument('demand', $demandArray);

        $this->settings['showFields'] = !empty($this->settings['showFields']) ? GeneralUtility::trimExplode(',', $this->settings['showFields']) : null;
        $this->settings['table']['columns'] = $this->tableColumns();
    }

    public function listAction(ProfileDemand $demand): ResponseInterface
    {
        $this->adoptSettings($demand);
        $activeListArguments = $this->activeListArguments($demand);
        // Read before the list event as well: the mode names a partial, and a demand a
        // listener hands back is not resolved again.
        $viewMode = $demand->getViewMode() !== '' ? $demand->getViewMode() : $this->defaultViewMode();
        $profiles = $this->profileRepository->findByDemand($demand, $this->queryContext());

        /** @var ModifyListProfilesEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyListProfilesEvent(
            profiles: $profiles,
            view: $this->view,
            pluginControllerActionContext: new PersonsPluginControllerActionContext($this->request, $this->settings),
            profileDemand: $demand,
        ));
        $demand = $event->getProfileDemand();
        $profiles = $event->getProfiles();

        if ($demand->getAlphabetFilter() !== '') {
            $this->settings['paginationEnabled'] = '0';
        }

        // If profiles were selected manually, sort them by order in selection. This has to
        // happen before the pagination below, which splits exactly this list into pages.
        $manualSelection = !empty($demand->getProfileList());
        if ($manualSelection) {
            $profiles = $this->sortBySelectionOrder(
                $profiles,
                GeneralUtility::intExplode(',', $demand->getProfileList(), true),
            );
        }

        if (($this->settings['paginationEnabled'] ?? null) === '1') {
            $resultsPerPage = (int)($this->settings['pagination']['resultsPerPage'] ?? 10);
            $numberOfPaginationLinks = (int)($this->settings['pagination']['numberOfLinks'] ?? 5);
            // A manual selection is ordered in PHP and not by the database, so its pages are
            // cut out of that ordered array. A QueryResultPaginator would page the query
            // result instead, which is in uid order rather than in the order of the selection.
            $paginator = $manualSelection
                ? new ArrayPaginator($profiles, $demand->getCurrentPage(), $resultsPerPage)
                : new QueryResultPaginator($profiles, $demand->getCurrentPage(), $resultsPerPage);
            if (ExtensionManagementUtility::isLoaded('numbered_pagination')
                && class_exists(NumberedPagination::class)
            ) {
                $pagination = new NumberedPagination($paginator, $numberOfPaginationLinks);
            } else {
                $pagination = new SimplePagination($paginator);
            }
            $this->view->assignMultiple([
                'paginator' => $paginator,
                'pagination' => $pagination,
            ]);
        }

        // Which letters lead to a list that is not empty - only when the navigation is rendered,
        // which it is not for a manual selection: that ignores the letter filter. The demand is
        // the one the listeners handed back, so the letters answer for the list they changed.
        if ((bool)($this->settings['alphabetPaginationEnabled'] ?? false) && !$manualSelection) {
            $this->view->assign(
                'alphabetFilterLetters',
                $this->profileRepository->findAlphabetFilterLetters($demand, $this->queryContext()),
            );
        }

        $this->assignContentElement();
        $this->view->assignMultiple([
            'profiles' => $profiles,
            'demand' => $demand,
            'activeListArguments' => $activeListArguments,
        ]);
        $this->assignViewMode($viewMode);
        $this->addCacheTags('profile_list_view');

        return $this->htmlResponse();
    }

    /**
     * Bring records into the order the editor put them in.
     *
     * A selection is an ordered list in the FlexForm, but the query that fetches it
     * matches `uid IN (...)`, which does not preserve that order - the `profileList` branch
     * of `ProfileRepository::resolveDemandForQuery()` orders by uid, for a reproducible result
     * and nothing more. So the order the editor chose has to be restored here.
     *
     * Records not in the selection are dropped and a uid listed twice yields the record
     * twice, which is what the three hand written copies of this loop did before it was
     * extracted.
     *
     * @template T of AbstractEntity
     * @param iterable<T> $records
     * @param int[] $uids
     * @return list<T>
     */
    private function sortBySelectionOrder(iterable $records, array $uids): array
    {
        $sorted = [];
        foreach ($uids as $uid) {
            foreach ($records as $record) {
                if ($record->getUid() === $uid) {
                    $sorted[] = $record;
                }
            }
        }
        return $sorted;
    }

    public function initializeCardAction(): void
    {
        $this->settings['showFields'] = !empty($this->settings['showFields']) ? GeneralUtility::trimExplode(',', $this->settings['showFields']) : null;
    }

    /**
     * This action used to carry a `@todo` calling it broken in multi language sites. That
     * did not survive being reproduced: rendered across the language matrix in
     * {@see \FGTCLB\AcademicPersons\Tests\Functional\Plugins\AcademicPersonsCardPluginLocalizationTest}
     * it returns what the list plugin returns from the same selection.
     *
     * One result in that matrix is still not desirable, and it does not belong to this
     * action: before TYPO3 v14.3.6 a `strict` site keeps untranslated profiles (forge
     * #88886, core). The other one - a `fallbackType: free` site rendering default
     * language profiles - was ours and is fixed, see
     * `ProfileRepository::matchSelectedUidsAcrossLanguages()` and ACE-341. Both came out
     * of the shared `profileList` branch of `resolveDemandForQuery()` and hit
     * `listAction()` in the same way.
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
            $profileDemand->setShowHiddenRecords((bool)($this->settings['showHiddenRecords'] ?? false));
            $profiles = $this->sortBySelectionOrder(
                $this->profileRepository->findByDemand($profileDemand, $this->queryContext()),
                GeneralUtility::intExplode(',', $this->settings['demand']['profileList'], true),
            );
        }

        $this->assignContentElement();
        $this->view->assignMultiple([
            'profiles' => $profiles,
        ]);

        return $this->htmlResponse();
    }

    public function initializeDetailAction(): void
    {
        // By default Extbase argument mapping resolves the `profile` argument
        // respecting enable fields, so a hidden profile would map to null. When
        // the plugin option "show hidden records" is enabled, re-resolve the
        // referenced profile including hidden (disabled) records and inject the
        // already hydrated object as the argument value.
        $showHiddenRecords = (bool)($this->settings['showHiddenRecords'] ?? false);
        if (!$showHiddenRecords || !$this->request->hasArgument('profile')) {
            return;
        }
        $profileArgument = $this->request->getArgument('profile');
        // Only a scalar uid reference needs manual resolution; an already
        // mapped object or a complex value is left untouched.
        if (!is_scalar($profileArgument)) {
            return;
        }
        $profileUid = (int)$profileArgument;
        if ($profileUid <= 0) {
            return;
        }
        $profile = $this->profileRepository->findByUidIncludingHidden($profileUid);
        if ($profile !== null) {
            $this->request = $this->request->withArgument('profile', $profile);
        }
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

        $pluginControllerActionContext = new PersonsPluginControllerActionContext($this->request, $this->settings);
        /** @var ModifyDetailProfileEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyDetailProfileEvent(
            $profile,
            $this->view,
            $pluginControllerActionContext,
            ProfileTitleProvider::DETAIL_PAGE_TITLE_FORMAT,
            $this->resolveDetailPageTitleFormat(),
        ));
        $profile = $event->getProfile();

        // Add page title based on profile name
        $this->profileTitleProvider->setFromProfile(
            $pluginControllerActionContext,
            $profile,
            $event->getPageTitleFormatToUse(),
        );

        // Set additional detail page cache tags
        $this->addCacheTags(
            'profile_detail_view',
            sprintf('profile_detail_view_%d', $profile->getUid()),
        );

        $this->assignContentElement();
        $this->view->assignMultiple([
            'profile' => $profile,
            // The public layout: which elements the template renders, in which column and
            // order, and what each of them shows. See `Templates/Profile/Detail.html`.
            'publicProfile' => $this->academicPersonsSettings->publicProfile,
        ]);
        return $this->htmlResponse();
    }

    public function initializeSelectedProfilesAction(): void
    {
        $this->settings['showFields'] = !empty($this->settings['showFields']) ? GeneralUtility::trimExplode(',', $this->settings['showFields']) : null;
        $this->settings['table']['columns'] = $this->tableColumns();
    }

    public function selectedProfilesAction(): ResponseInterface
    {
        $this->assignViewMode($this->resolveViewMode($this->requestedViewMode()));
        if (empty($this->settings['selectedProfiles'])) {
            // Nothing is selected, and the header of the content element still renders.
            $this->assignContentElement();
            return $this->htmlResponse();
        }

        $profileUids = GeneralUtility::intExplode(',', $this->settings['selectedProfiles'], true);
        $showHiddenRecords = (bool)($this->settings['showHiddenRecords'] ?? false);
        $profiles = $this->profileRepository->findByUidsWithContext($profileUids, $this->queryContext(), $showHiddenRecords);

        /** @var ModifySelectedProfilesEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifySelectedProfilesEvent(
            $profiles,
            $this->view,
            new PersonsPluginControllerActionContext($this->request, $this->settings),
        ));
        $profiles = $event->getProfiles();

        $this->assignContentElement();
        $this->view->assignMultiple([
            'profiles' => $this->sortBySelectionOrder($profiles, $profileUids),
        ]);

        return $this->htmlResponse();
    }

    public function initializeSelectedContractsAction(): void
    {
        $this->settings['showFields'] = !empty($this->settings['showFields']) ? GeneralUtility::trimExplode(',', $this->settings['showFields']) : null;
        $this->settings['table']['columns'] = $this->tableColumns();
    }

    public function selectedContractsAction(): ResponseInterface
    {
        $this->assignViewMode($this->resolveViewMode($this->requestedViewMode()));
        if (empty($this->settings['selectedContracts'])) {
            // Nothing is selected, and the header of the content element still renders.
            $this->assignContentElement();
            return $this->htmlResponse();
        }

        $contractUids = GeneralUtility::intExplode(',', $this->settings['selectedContracts'], true);
        $showHiddenRecords = (bool)($this->settings['showHiddenRecords'] ?? false);
        $contracts = $this->contractRepository->findByUidsWithContext($contractUids, $this->queryContext(), $showHiddenRecords);

        /** @var ModifySelectedContractsEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifySelectedContractsEvent(
            $contracts,
            $this->view,
            new PersonsPluginControllerActionContext($this->request, $this->settings),
        ));
        $contracts = $event->getContracts();

        $this->assignContentElement();
        $this->view->assignMultiple([
            'contracts' => $this->sortBySelectionOrder($contracts, $contractUids),
        ]);

        return $this->htmlResponse();
    }

    /**
     * The visitor's choices the list is shown with, as its navigation links carry them.
     *
     * Read from the mapped demand, so a value the property mapping rejected never reaches a
     * link, and before the list event, as the partner list reads its link arguments: a
     * listener acts again on the request a link leads to, so what it changes need not
     * travel in the URL. A value equal to its default is left out, so the first page and
     * the list without a letter keep the URLs they always had.
     *
     * The page is the one requested, not the one the paginator clamps it to. A page link
     * replaces it and a letter link drops it; the view mode switch keeps it as it is, and
     * the paginator clamps it again on the request the switch leads to.
     *
     * @return array<string, mixed>
     */
    private function activeListArguments(ProfileDemand $demand): array
    {
        $defaults = new ProfileDemand();
        $arguments = [];
        foreach (self::VISITOR_DEMAND_PROPERTIES as $property) {
            $value = ObjectAccess::getProperty($demand, $property);
            if ($value !== ObjectAccess::getProperty($defaults, $property)) {
                $arguments[$property] = $value;
            }
        }
        return $arguments;
    }

    /**
     * Adopt plugin settings and `tt_content.pages`.
     */
    private function adoptSettings(ProfileDemand $demand): void
    {
        if (isset($this->settings['functionTypes'])
            && is_string($this->settings['functionTypes'])
            && $this->settings['functionTypes'] !== ''
        ) {
            $functionTypeUids = GeneralUtility::intExplode(',', $this->settings['functionTypes'], true);
            if (!empty($functionTypeUids)) {
                $demand->setFunctionTypes($functionTypeUids);
            }
        }

        if (isset($this->settings['organisationalUnits'])
            && is_string($this->settings['organisationalUnits'])
            && $this->settings['organisationalUnits'] !== ''
        ) {
            $organisationalUnitUids = GeneralUtility::intExplode(',', $this->settings['organisationalUnits'], true);
            if (!empty($organisationalUnitUids)) {
                $demand->setOrganisationalUnits($organisationalUnitUids);
            }
        }

        /** @var array<string, mixed> $contentObjectData */
        $contentObjectData = $this->getCurrentContentObjectRenderer()?->data;
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

        $demand->setShowHiddenRecords((bool)($this->settings['showHiddenRecords'] ?? false));

        // The mode the list renders, written back so the navigation links carry it: empty
        // for the default mode, and for a mode the resolution rejected, which never reaches
        // a link that way.
        $viewMode = $this->resolveViewMode($demand->getViewMode());
        $demand->setViewMode($viewMode === $this->defaultViewMode() ? '' : $viewMode);
    }

    /**
     * The view mode to render: the one the visitor asked for while the content element
     * offers the switch and the site allows it, the default mode otherwise.
     */
    private function resolveViewMode(string $requested): string
    {
        if ((bool)($this->settings['viewMode']['enabled'] ?? false)
            && in_array($requested, $this->allowedViewModes(), true)
        ) {
            return $requested;
        }
        return $this->defaultViewMode();
    }

    /**
     * The default view mode of the content element, while the site allows it. A content
     * element saved before view modes rendered, or one whose mode the site no longer
     * allows, gets the tiles, or the first allowed mode where the tiles are not allowed.
     */
    private function defaultViewMode(): string
    {
        $allowed = $this->allowedViewModes();
        $default = $this->settings['viewMode']['default'] ?? '';
        if (is_string($default) && in_array($default, $allowed, true)) {
            return $default;
        }
        return in_array(self::TILES_VIEW_MODE, $allowed, true) ? self::TILES_VIEW_MODE : $allowed[0];
    }

    /**
     * The modes `settings.viewMode.allowed` names, once each, without one that is not a
     * plain name. Without any, the tiles are the one mode there is.
     *
     * @return non-empty-list<string>
     */
    private function allowedViewModes(): array
    {
        $allowed = $this->settings['viewMode']['allowed'] ?? '';
        $modes = array_values(array_unique(array_filter(
            GeneralUtility::trimExplode(',', is_string($allowed) ? $allowed : '', true),
            static fn(string $mode): bool => preg_match(self::VIEW_MODE_PATTERN, $mode) === 1,
        )));
        return $modes !== [] ? $modes : [self::TILES_VIEW_MODE];
    }

    /**
     * The view mode the request of a selected profiles or contracts element asks for. They
     * have no demand, so it is a plugin argument of its own.
     */
    private function requestedViewMode(): string
    {
        $requested = $this->request->hasArgument('viewMode') ? $this->request->getArgument('viewMode') : '';
        return is_string($requested) ? $requested : '';
    }

    /**
     * Assign the resolved mode, `viewModePartial` - its partial below `Profile/ViewMode/`,
     * the mode with an upper case first letter - and what the switch needs: `viewModes`,
     * the modes it offers, empty while the content element does not offer it or there is
     * nothing to switch between, and `defaultViewMode`, whose link carries no mode.
     */
    private function assignViewMode(string $viewMode): void
    {
        $viewModes = (bool)($this->settings['viewMode']['enabled'] ?? false) ? $this->allowedViewModes() : [];
        $this->view->assignMultiple([
            'viewMode' => $viewMode,
            'viewModePartial' => ucfirst($viewMode),
            'viewModes' => count($viewModes) > 1 ? $viewModes : [],
            'defaultViewMode' => $this->defaultViewMode(),
        ]);
    }

    /**
     * The columns of the table view mode, `settings.table.columns` as a list. While the
     * content element restricts its fields, `settings.showFields` - already a list here -
     * a contract column it does not name is left out, as the tiles leave the field out.
     *
     * @return list<string>
     */
    private function tableColumns(): array
    {
        $columns = $this->settings['table']['columns'] ?? '';
        $columns = GeneralUtility::trimExplode(',', is_string($columns) ? $columns : '', true);
        $showFields = $this->settings['showFields'] ?? null;
        if (!is_array($showFields)) {
            return $columns;
        }
        return array_values(array_filter(
            $columns,
            static fn(string $column): bool => !in_array($column, self::CONTRACT_TABLE_COLUMNS, true)
                || in_array('contracts.' . $column, $showFields, true),
        ));
    }

    /**
     * Add cache tags to the current page.
     *
     * @param string ...$tags
     */
    private function addCacheTags(string ...$tags): void
    {
        $cacheCollector = $this->request->getAttribute('frontend.cache.collector');
        foreach ($tags as $tag) {
            $cacheCollector?->addCacheTags(new CacheTag($tag));
        }
    }

    /**
     * The content element the plugin renders: `data`, its row, and `record`, the record the
     * header partial of EXT:fluid_styled_content renders the header through on TYPO3 v14.
     * The templates render that partial while `settings.renderContentElementHeader` is on.
     */
    private function assignContentElement(): void
    {
        $contentObjectRenderer = $this->getCurrentContentObjectRenderer();
        $this->view->assignMultiple([
            'data' => $contentObjectRenderer?->data,
            'record' => $this->getCurrentContentRecord($contentObjectRenderer),
        ]);
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }

    /**
     * The context the repositories hand to the listeners of `ModifyProfileQueryEvent` and
     * `ModifyContractQueryEvent`.
     *
     * It is the `academic_base` context rather than the one this extension ships, because the
     * repository API is new and the persons interface is the one that goes away: it is the base
     * interface minus `getContentObjectRenderer()`, and a listener of the query events is the
     * kind of listener that wants exactly that. The events of the actions keep the persons
     * context until that interface is retired.
     *
     * @todo Three actions therefore build two context objects from the same request and
     *       settings. They collapse into one once the persons interface extends the
     *       `academic_base` one - change `ace-tbd-single-action-context-interface`.
     */
    private function queryContext(): PluginControllerActionContext
    {
        return new PluginControllerActionContext($this->request, $this->settings);
    }

    private function resolveDetailPageTitleFormat(): string
    {
        // Determine pageTitleFormat form FlexForm settings
        if (isset($this->settings['pageTitleFormat'])
            && is_string($this->settings['pageTitleFormat'])
            && trim($this->settings['pageTitleFormat'], ' ') !== ''
        ) {
            return trim($this->settings['pageTitleFormat'], ' ');
        }
        return '';
    }
}
