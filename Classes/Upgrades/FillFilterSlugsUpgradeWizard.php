<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Upgrades;

use FGTCLB\AcademicPersons\Service\DataHandlerExecutionContext;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Gives every function type and organisational unit without a slug the slug a save
 * would give it, so the route enhancers of the list build a speaking filter URL for
 * it. Without a slug the filter of that record stays a query argument.
 *
 * The slugs are written by the DataHandler, acting as a backend user in the live
 * workspace, one run per table: an empty slug is what makes the DataHandler generate
 * one from the name, unique in the table for its language, exactly as a backend save
 * does. Records are written in uid order, so of two records with the same name the
 * older one gets the plain slug. A slug that is set is never touched.
 *
 * Only live records are written. A record can lose its slug again after the wizard
 * ran: a workspace version made before the update is published with an empty one,
 * and an import that writes the tables directly leaves it empty. So the wizard is
 * {@see RepeatableInterface}: it is offered again whenever a live record has no slug,
 * and running it with nothing to do changes nothing.
 */
#[UpgradeWizard('academicPersons_fillFilterSlugs')]
final readonly class FillFilterSlugsUpgradeWizard implements UpgradeWizardInterface, RepeatableInterface
{
    private const TABLES = [
        'tx_academicpersons_domain_model_function_type',
        'tx_academicpersons_domain_model_organisational_unit',
    ];

    public function __construct(
        private ConnectionPool $connectionPool,
        private DataHandlerExecutionContext $executionContext,
        private LoggerInterface $logger,
    ) {}

    public function getTitle(): string
    {
        return 'Generate the URL segments of academic function types and organisational units';
    }

    public function getDescription(): string
    {
        return 'Gives every function type and organisational unit without a URL segment (slug) the one a'
            . ' save would generate from its name, so a persons list filtered by it gets a speaking URL.'
            . ' Segments that are set are left as they are.';
    }

    public function updateNecessary(): bool
    {
        foreach (self::TABLES as $table) {
            if ($this->findLiveRecordsWithoutSlug($table) !== []) {
                return true;
            }
        }
        return false;
    }

    public function executeUpdate(): bool
    {
        $complete = true;
        foreach (self::TABLES as $table) {
            $uids = $this->findLiveRecordsWithoutSlug($table);
            if ($uids === []) {
                continue;
            }
            $errors = $this->generateSlugs($table, $uids);
            $missing = array_values(array_intersect($uids, $this->findLiveRecordsWithoutSlug($table)));
            if ($missing !== []) {
                $this->logger->error('No slug was generated for {table} records {uids}: {errors}', [
                    'table' => $table,
                    'uids' => implode(', ', $missing),
                    'errors' => implode(' ', $errors),
                ]);
                $complete = false;
            }
        }
        return $complete;
    }

    /**
     * @return list<class-string>
     */
    public function getPrerequisites(): array
    {
        return [DatabaseUpdatedPrerequisite::class];
    }

    /**
     * @return list<int>
     */
    private function findLiveRecordsWithoutSlug(string $table): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $uids = $queryBuilder
            ->select('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('slug', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->isNull('slug'),
                ),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();
        return array_map(intval(...), $uids);
    }

    /**
     * @param list<int> $uids
     * @return list<string> the errors the DataHandler reported
     */
    private function generateSlugs(string $table, array $uids): array
    {
        $dataMap = [$table => array_fill_keys($uids, ['slug' => ''])];
        $errors = [];
        $this->executionContext->runAsLiveBackendUser(
            function (BackendUserAuthentication $backendUser) use ($dataMap, &$errors): void {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start($dataMap, [], $backendUser);
                $dataHandler->process_datamap();
                $errors = array_values(array_map('strval', $dataHandler->errorLog));
            },
        );
        return $errors;
    }
}
