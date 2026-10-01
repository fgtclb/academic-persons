<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Import;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Finds the record an import wrote before, by the import identifier it carries.
 *
 * The identifier names the source and the key of the record there,
 * `<source>:<key>`, for instance `fe_users:12`. Import code writes it into the
 * `import_identifier` column and looks the record up by it on the next run:
 *
 * ```php
 * $uid = $this->importedRecordFinder->findUid('tx_academicpersons_domain_model_profile', 'hr:4711');
 * ```
 *
 * Only the live record of the default language, or of all languages, is found,
 * hidden or not and whatever its start and end time, since an import updates
 * what it wrote even after an editor has hidden it. Deleted records, workspace
 * versions and translations are never found. Nothing keeps two records from
 * carrying one identifier: the one with the lowest uid is found then.
 *
 * The identifier is compared in PHP. `utf8mb4_unicode_ci`, the collation TYPO3
 * creates its MySQL and MariaDB tables with, ignores case and trailing spaces,
 * so the database alone would find `HR:4711` for `hr:4711` there and not on
 * PostgreSQL or SQLite.
 *
 * @api
 */
#[Autoconfigure(public: true)]
final readonly class ImportedRecordFinder
{
    private const COLUMN = 'import_identifier';

    public function __construct(
        private ConnectionPool $connectionPool,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * @return int|null the uid, or null when no live record carries the identifier
     * @throws \InvalidArgumentException when the table has no import identifier
     */
    public function findUid(string $tableName, string $importIdentifier): ?int
    {
        if (!$this->tcaSchemaFactory->has($tableName)
            || !$this->tcaSchemaFactory->get($tableName)->hasField(self::COLUMN)
        ) {
            throw new \InvalidArgumentException(
                sprintf('The table "%s" has no import identifier.', $tableName),
                1790866812,
            );
        }
        if ($importIdentifier === '') {
            // Every record an editor created carries the empty identifier.
            return null;
        }
        $schema = $this->tcaSchemaFactory->get($tableName);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());
        $queryBuilder
            ->select('uid', self::COLUMN)
            ->from($tableName)
            ->where($queryBuilder->expr()->eq(
                self::COLUMN,
                $queryBuilder->createNamedParameter($importIdentifier),
            ))
            ->orderBy('uid');
        if ($schema->isWorkspaceAware()) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            );
        }
        if ($schema->isLanguageAware()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in(
                $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName(),
                $queryBuilder->quoteArrayBasedValueListToIntegerList([0, -1]),
            ));
        }
        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            if ((string)$row[self::COLUMN] === $importIdentifier) {
                return (int)$row['uid'];
            }
        }
        return null;
    }
}
