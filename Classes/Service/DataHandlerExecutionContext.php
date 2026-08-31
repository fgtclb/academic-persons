<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides the backend user context a {@see \TYPO3\CMS\Core\DataHandling\DataHandler}
 * run needs, independent of how the current request came in (frontend, backend, CLI).
 *
 * DataHandler itself takes a user object through `start()`, but parts of the localization
 * path (`BackendUtility::getRecordLocalization()`, `BackendUtility::workspaceOL()`) read
 * `$GLOBALS['BE_USER']` directly and ignore the injected object. `runAsBackendUser()`
 * therefore swaps the global in for the duration of the callback and restores the previous
 * state in any case. When no backend user is available - the frontend and parts of the CLI
 * context - a synthetic in-memory admin user is used: uid 0 (no `be_users` row backs it),
 * admin flag set (bypasses all permission and workspace access checks), acting in the
 * workspace of the current {@see Context} workspace aspect. The `workspace` property
 * default of {@see BackendUserAuthentication} is -99 ("offline"), so it is always set
 * explicitly.
 *
 * This service is stateless: all run state lives in local variables and callback arguments.
 *
 * @internal owned by the record synchronization of EXT:academic_persons, no public API.
 */
#[Autoconfigure(public: true)]
final class DataHandlerExecutionContext
{
    public function __construct(
        private readonly Context $context,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    /**
     * Whether the current call acts from a frontend request inside a non-live workspace.
     *
     * This is the (for now hardcoded) refusal policy of the record synchronization: a
     * frontend-triggered synchronization must not write workspace versions, so callers
     * are expected to skip the run entirely when this returns true. Backend and CLI
     * contexts act in the workspace of the acting backend user, which DataHandler
     * handles by itself.
     */
    public function isFrontendRequestInWorkspace(): bool
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface
            || !is_int($request->getAttribute('applicationType'))
            || !ApplicationType::fromRequest($request)->isFrontend()
        ) {
            return false;
        }
        return $this->getActingWorkspaceId() > 0;
    }

    /**
     * Executes $action with a guaranteed `$GLOBALS['BE_USER']`, restoring the previous
     * global state afterwards - also when the callback throws.
     *
     * @param \Closure(BackendUserAuthentication): void $action
     */
    public function runAsBackendUser(\Closure $action): void
    {
        $previousBackendUser = $GLOBALS['BE_USER'] ?? null;
        $previousLanguageService = $GLOBALS['LANG'] ?? null;
        $backendUser = $previousBackendUser instanceof BackendUserAuthentication
            ? $previousBackendUser
            : $this->createSyntheticBackendUser();
        $GLOBALS['BE_USER'] = $backendUser;
        // DataHandler error paths render backend labels through `$GLOBALS['LANG']`,
        // so it is set defensively when nothing else provided it.
        $GLOBALS['LANG'] ??= $this->languageServiceFactory->create('default');
        try {
            $action($backendUser);
        } finally {
            $GLOBALS['BE_USER'] = $previousBackendUser;
            if ($previousBackendUser === null) {
                unset($GLOBALS['BE_USER']);
            }
            $GLOBALS['LANG'] = $previousLanguageService;
            if ($previousLanguageService === null) {
                unset($GLOBALS['LANG']);
            }
        }
    }

    private function createSyntheticBackendUser(): BackendUserAuthentication
    {
        $backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $backendUser->user = [
            'uid' => 0,
            'admin' => 1,
            'username' => '_record_synchronizer_',
        ];
        $backendUser->workspace = $this->getActingWorkspaceId();
        return $backendUser;
    }

    private function getActingWorkspaceId(): int
    {
        return (int)$this->context->getPropertyFromAspect('workspace', 'id', 0);
    }
}
