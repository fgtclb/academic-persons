<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides the backend user a {@see \TYPO3\CMS\Core\DataHandling\DataHandler} run needs
 * from the command line, where there is none.
 *
 * Three things matter, the same ones `GeocodeWriteContext` of academic_partners handles:
 *
 * 1. Passing the user to `DataHandler::start()` is not enough. Parts of the localization
 *    path read `$GLOBALS['BE_USER']` directly, so the global is swapped in for the
 *    duration of the run and restored in a `finally`, also when the callback throws.
 * 2. `BackendUserAuthentication::$workspace` defaults to -99 ("offline"), not to live,
 *    so it is always set.
 * 3. DataHandler error paths render labels through `$GLOBALS['LANG']`, which is set
 *    when nothing else provided it.
 *
 * The user is a synthetic admin: uid 0, no `be_users` row behind it, acting in the live
 * workspace whatever workspace a logged-in user selected. An installation-wide cleanup
 * has no business writing into anyone's workspace.
 *
 * This service is stateless: all run state lives in local variables and callback
 * arguments.
 *
 * @internal for the DataHandler runs of academic_persons, no public API.
 */
final class DataHandlerExecutionContext
{
    public function __construct(
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    /**
     * Executes $action with a synthetic admin as `$GLOBALS['BE_USER']` acting in the live
     * workspace, restoring the previous global state afterwards.
     *
     * @param \Closure(BackendUserAuthentication): void $action
     */
    public function runAsLiveBackendUser(\Closure $action): void
    {
        $previousBackendUser = $GLOBALS['BE_USER'] ?? null;
        $previousLanguageService = $GLOBALS['LANG'] ?? null;

        $backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $backendUser->user = [
            'uid' => 0,
            'admin' => 1,
            'username' => '_academic_persons_',
        ];
        $backendUser->workspace = 0;

        $GLOBALS['BE_USER'] = $backendUser;
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
}
