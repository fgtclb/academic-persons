<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service;

use FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Synchronizes a default-language record into the allowed site languages of a
 * {@see SynchronizerContext} by routing every write through the TYPO3 DataHandler.
 *
 * A missing translation is created with a `localize` command, which carries the full
 * inline child tree, file references, MM relations, `l10n_diffsource`, the reference
 * index, history and all DataHandler hooks. For an existing translation, the current
 * values of the `l10n_mode=exclude` columns of the default record are re-submitted as
 * a datamap, so core's DataMapProcessor propagates them into every translation, and an
 * `inlineLocalizeSynchronize` command per TCA inline column carries children added to
 * the default record after the translation was created - including their own children.
 *
 * Each command runs through its own DataHandler instance: a cmdmap can hold only one
 * command per record uid (the command name is the array key), so `localize` per
 * language and `inlineLocalizeSynchronize` per inline column and language cannot be
 * combined into a single map.
 *
 * Workspace behaviour falls out of the acting backend user provided by
 * {@see DataHandlerExecutionContext}: in a non-live workspace DataHandler writes
 * versioned rows (`t3ver_wsid`, `t3ver_state=1`) and never touches the live state. A
 * frontend request acting in a non-live workspace is refused entirely - see
 * {@see DataHandlerExecutionContext::isFrontendRequestInWorkspace()}.
 *
 * @internal being experimental for now until implementation has been streamlined, tested and covered with tests.
 * @final not marked as final for functional testing reasons (for now). Class should not be extended otherwise.
 */
#[AsAlias(id: RecordSynchronizerInterface::class, public: true)]
#[Autoconfigure(public: true)]
class RecordSynchronizer implements RecordSynchronizerInterface
{
    /**
     * TCA column types whose database value does not survive a verbatim datamap
     * re-submission: relational values are stored as counters or CSV uid lists the
     * DataHandler would reinterpret. Their create-path synchronization is covered by
     * `localize`; on the update path they are deliberately left alone.
     */
    private const NON_PROPAGATABLE_COLUMN_TYPES = ['inline', 'file', 'group', 'category', 'folder', 'passthrough'];

    public function __construct(
        private readonly DataHandlerExecutionContext $executionContext,
        private readonly LoggerInterface $logger,
    ) {}

    public function synchronize(SynchronizerContext $context): void
    {
        if ($context->allowedSiteLanguages === []) {
            return;
        }
        if ($this->executionContext->isFrontendRequestInWorkspace()) {
            $this->logger->notice(
                'Refused translation synchronization of {tableName}:{uid}: frontend request acting in a non-live workspace.',
                [
                    'tableName' => $context->tableName,
                    'uid' => $context->uid,
                ],
            );
            return;
        }
        $this->executionContext->runAsBackendUser(
            function (BackendUserAuthentication $backendUser) use ($context): void {
                $this->synchronizeRecord($context, $backendUser);
            },
        );
    }

    private function synchronizeRecord(SynchronizerContext $context, BackendUserAuthentication $backendUser): void
    {
        $defaultRecord = $this->getSynchronizableDefaultRecord($context, $backendUser);
        if ($defaultRecord === null) {
            return;
        }
        $languagesWithExistingTranslation = [];
        foreach ($context->allowedSiteLanguages as $allowedSiteLanguage) {
            $languageId = $allowedSiteLanguage->getLanguageId();
            if ($this->hasTranslation($context->tableName, $context->uid, $languageId, $backendUser)) {
                $languagesWithExistingTranslation[] = $languageId;
                continue;
            }
            $this->executeDataHandler($backendUser, cmdmap: [
                $context->tableName => [
                    $context->uid => [
                        'localize' => $languageId,
                    ],
                ],
            ]);
        }
        if ($languagesWithExistingTranslation === []) {
            return;
        }
        $this->propagateExcludeColumnValues($context, $defaultRecord, $backendUser);
        foreach ($languagesWithExistingTranslation as $languageId) {
            foreach ($this->getInlineColumnNames($context->tableName) as $inlineColumnName) {
                $this->executeDataHandler($backendUser, cmdmap: [
                    $context->tableName => [
                        $context->uid => [
                            'inlineLocalizeSynchronize' => [
                                'field' => $inlineColumnName,
                                'language' => $languageId,
                                'action' => 'synchronize',
                            ],
                        ],
                    ],
                ]);
            }
        }
    }

    /**
     * Returns the record the context points at when it is synchronizable, null otherwise.
     *
     * The record must exist (deleted records are excluded), carry the default language
     * of the context and be reachable in the acting workspace. A workspace version row
     * (`t3ver_oid > 0`) is refused deliberately: DataHandler addresses versioned records
     * through their live uid and overlays them itself, so accepting the version uid here
     * would publish draft values as live translations.
     *
     * @return array<string, mixed>|null
     */
    private function getSynchronizableDefaultRecord(
        SynchronizerContext $context,
        BackendUserAuthentication $backendUser,
    ): ?array {
        $record = BackendUtility::getRecord($context->tableName, $context->uid);
        if ($record === null) {
            return null;
        }
        $languageField = $this->getTcaCtrlField($context->tableName, 'languageField');
        if ($languageField === null) {
            return null;
        }
        if ((int)($record[$languageField] ?? 0) !== $context->defaultLanguage->getLanguageId()) {
            return null;
        }
        if ((int)($record['t3ver_oid'] ?? 0) > 0) {
            $this->logger->notice(
                'Refused translation synchronization of {tableName}:{uid}: uid addresses a workspace version row, not a live record.',
                [
                    'tableName' => $context->tableName,
                    'uid' => $context->uid,
                ],
            );
            return null;
        }
        $recordWorkspaceId = (int)($record['t3ver_wsid'] ?? 0);
        if ($recordWorkspaceId !== 0 && $recordWorkspaceId !== $backendUser->workspace) {
            // A record created in another workspace is invisible to the acting one.
            return null;
        }
        return $record;
    }

    /**
     * Workspace-aware check whether a translation of the record exists, on both
     * supported core versions: TYPO3 v14 deprecated
     * `BackendUtility::getRecordLocalization()` (removed in v15) in favour of
     * `LocalizationRepository::getRecordTranslation()`, which does not exist on v13 -
     * the `method_exists()` gate selects the API the running core provides.
     */
    private function hasTranslation(
        string $tableName,
        int $uid,
        int $languageId,
        BackendUserAuthentication $backendUser,
    ): bool {
        $localizationRepository = GeneralUtility::makeInstance(LocalizationRepository::class);
        if (method_exists($localizationRepository, 'getRecordTranslation')) {
            return $localizationRepository->getRecordTranslation($tableName, $uid, $languageId, $backendUser->workspace) !== null;
        }
        $rows = BackendUtility::getRecordLocalization($tableName, $uid, $languageId);
        return is_array($rows) && $rows !== [];
    }

    /**
     * Re-submits the current default-record values of all propagatable
     * `l10n_mode=exclude` columns, so `DataMapProcessor` carries them into every
     * translation of the record.
     *
     * @param array<string, mixed> $defaultRecord
     */
    private function propagateExcludeColumnValues(
        SynchronizerContext $context,
        array $defaultRecord,
        BackendUserAuthentication $backendUser,
    ): void {
        $values = [];
        foreach ($this->getPropagatableExcludeColumnNames($context->tableName) as $columnName) {
            if (array_key_exists($columnName, $defaultRecord)) {
                $values[$columnName] = $defaultRecord[$columnName];
            }
        }
        if ($values === []) {
            return;
        }
        $this->executeDataHandler($backendUser, datamap: [
            $context->tableName => [
                $context->uid => $values,
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    private function getPropagatableExcludeColumnNames(string $tableName): array
    {
        $columnNames = [];
        foreach ($this->getTcaColumns($tableName) as $columnName => $columnDefinition) {
            if (($columnDefinition['l10n_mode'] ?? '') !== 'exclude') {
                continue;
            }
            $columnType = $columnDefinition['config']['type'] ?? '';
            if (in_array($columnType, self::NON_PROPAGATABLE_COLUMN_TYPES, true)) {
                continue;
            }
            if (($columnDefinition['config']['MM'] ?? '') !== '') {
                continue;
            }
            $columnNames[] = $columnName;
        }
        return $columnNames;
    }

    /**
     * @return list<string>
     */
    private function getInlineColumnNames(string $tableName): array
    {
        $columnNames = [];
        foreach ($this->getTcaColumns($tableName) as $columnName => $columnDefinition) {
            if (($columnDefinition['config']['type'] ?? '') === 'inline') {
                $columnNames[] = $columnName;
            }
        }
        return $columnNames;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getTcaColumns(string $tableName): array
    {
        $tcaColumns = $GLOBALS['TCA'][$tableName]['columns'] ?? null;
        return is_array($tcaColumns) ? $tcaColumns : [];
    }

    private function getTcaCtrlField(string $tableName, string $ctrlField): ?string
    {
        $value = $GLOBALS['TCA'][$tableName]['ctrl'][$ctrlField] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Runs one DataHandler pass. Deliberately one instance per call - see the class
     * docblock for why commands cannot be combined into a single map.
     *
     * @param array<string, array<int|string, array<string, mixed>>> $datamap
     * @param array<string, array<int|string, array<string, mixed>>> $cmdmap
     */
    private function executeDataHandler(
        BackendUserAuthentication $backendUser,
        array $datamap = [],
        array $cmdmap = [],
    ): void {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, $cmdmap, $backendUser);
        if ($datamap !== []) {
            $dataHandler->process_datamap();
        }
        if ($cmdmap !== []) {
            $dataHandler->process_cmdmap();
        }
        if ($dataHandler->errorLog !== []) {
            $this->logger->error(
                'DataHandler reported errors during translation synchronization.',
                [
                    'errors' => $dataHandler->errorLog,
                    'datamap' => $datamap,
                    'cmdmap' => $cmdmap,
                ],
            );
        }
    }
}
