<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Profile;

use FGTCLB\AcademicPersons\Domain\Model\FunctionType;
use FGTCLB\AcademicPersons\Domain\Model\OrganisationalUnit;
use FGTCLB\AcademicPersons\Settings\FrontendUserSyncRelation;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Finds the organisational unit or function type a frontend user's value
 * names, for a relation of the `frontendUserSync.contract` map, and creates a
 * missing one where the map allows it.
 *
 * A record matches when the field the relation is matched by holds exactly the
 * value, case and accents included, on every database. Hidden records match
 * too, so they are not created a second time, and so do records on any page.
 * Deleted records, workspace drafts and translations never match. Of several
 * matching records the one with the lowest uid is taken.
 *
 * A created record is persisted at once, so the next lookup finds it, even one
 * for another profile of the same frontend user that the synchronisation
 * persists together with this one. Persisting writes everything pending in
 * the persistence manager at that moment, not only the new record. Then it
 * gets the slug a backend save would give it, which the filter routes of the
 * persons list read and Extbase does not write.
 *
 * @internal not part of public API.
 */
final readonly class ContractRelationResolver
{
    private const ORGANISATIONAL_UNIT_TABLE = 'tx_academicpersons_domain_model_organisational_unit';
    private const FUNCTION_TYPE_TABLE = 'tx_academicpersons_domain_model_function_type';

    public function __construct(
        private ConnectionPool $connectionPool,
        private PersistenceManagerInterface $persistenceManager,
    ) {}

    /**
     * @param non-empty-string $value
     */
    public function resolveOrganisationalUnit(FrontendUserSyncRelation $relation, string $value): ?OrganisationalUnit
    {
        $column = match ($relation->matchBy) {
            'uniqueName' => 'unique_name',
            'unitName' => 'unit_name',
            default => throw $this->unsupportedField('An organisational unit', $relation->matchBy),
        };
        $uid = $this->findUid(self::ORGANISATIONAL_UNIT_TABLE, $column, $value);
        if ($uid > 0) {
            return $this->findIncludingHidden(OrganisationalUnit::class, $uid);
        }
        if (!$relation->create) {
            return null;
        }
        $organisationalUnit = new OrganisationalUnit();
        $organisationalUnit->setPid($relation->storagePid);
        // The unit name is the label of the record and required in the backend form.
        $organisationalUnit->setUnitName($value);
        if ($relation->matchBy === 'uniqueName') {
            $organisationalUnit->setUniqueName($value);
        }
        $this->persist($organisationalUnit);
        $this->writeSlug(self::ORGANISATIONAL_UNIT_TABLE, $organisationalUnit);
        return $organisationalUnit;
    }

    /**
     * @param non-empty-string $value
     */
    public function resolveFunctionType(FrontendUserSyncRelation $relation, string $value): ?FunctionType
    {
        $column = match ($relation->matchBy) {
            'functionName' => 'function_name',
            default => throw $this->unsupportedField('A function type', $relation->matchBy),
        };
        $uid = $this->findUid(self::FUNCTION_TYPE_TABLE, $column, $value);
        if ($uid > 0) {
            return $this->findIncludingHidden(FunctionType::class, $uid);
        }
        if (!$relation->create) {
            return null;
        }
        $functionType = new FunctionType();
        $functionType->setPid($relation->storagePid);
        $functionType->setFunctionName($value);
        $this->persist($functionType);
        $this->writeSlug(self::FUNCTION_TYPE_TABLE, $functionType);
        return $functionType;
    }

    /**
     * The lowest uid of a live, default-language record whose column holds
     * exactly the value, or 0. The database narrows the candidates, and PHP
     * compares them: MySQL and MariaDB also return a value that differs in
     * case, accents or trailing blanks, PostgreSQL and SQLite do not.
     *
     * @param non-empty-string $table
     * @param non-empty-string $column
     */
    private function findUid(string $table, string $column, string $value): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $result = $queryBuilder
            ->select('uid', $column)
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq($column, $queryBuilder->createNamedParameter($value)),
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList([0, -1]),
                ),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('uid')
            ->executeQuery();
        while ($row = $result->fetchAssociative()) {
            if ((string)$row[$column] === $value) {
                return (int)$row['uid'];
            }
        }
        return 0;
    }

    /**
     * @template T of AbstractEntity
     * @param class-string<T> $className
     * @return T|null
     */
    private function findIncludingHidden(string $className, int $uid): ?AbstractEntity
    {
        $query = $this->persistenceManager->createQueryForType($className);
        // The default language without overlays, whatever the language of the request a
        // custom factory runs in: a unit without a translation must not load as null.
        $query->getQuerySettings()
            ->setRespectStoragePage(false)
            ->setRespectSysLanguage(false)
            ->setLanguageAspect(new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF))
            ->setIgnoreEnableFields(true);
        $query->matching($query->equals('uid', $uid));
        /** @var T|null $record */
        $record = $query->execute()->getFirst();
        return $record;
    }

    /**
     * The normaliser accepts no other field, so only a relation built by hand
     * reaches this.
     */
    private function unsupportedField(string $record, string $field): \LogicException
    {
        return new \LogicException(
            sprintf('%s cannot be matched by "%s".', $record, $field),
            1790720488,
        );
    }

    private function persist(AbstractEntity $record): void
    {
        $this->persistenceManager->add($record);
        $this->persistenceManager->persistAll();
    }

    /**
     * Generates the slug from the persisted row with the TCA of the field, unique in
     * the table for its language, as the DataHandler does on a save.
     *
     * @param non-empty-string $table
     */
    private function writeSlug(string $table, AbstractEntity $record): void
    {
        $uid = (int)$record->getUid();
        $connection = $this->connectionPool->getConnectionForTable($table);
        $row = $connection->select(['*'], $table, ['uid' => $uid])->fetchAssociative();
        $configuration = $GLOBALS['TCA'][$table]['columns']['slug']['config'] ?? null;
        if (!is_array($row) || !is_array($configuration)) {
            return;
        }
        $pid = (int)$row['pid'];
        $slugHelper = GeneralUtility::makeInstance(SlugHelper::class, $table, 'slug', $configuration);
        $slug = $slugHelper->buildSlugForUniqueInTable(
            $slugHelper->generate($row, $pid),
            RecordStateFactory::forName($table)->fromArray($row, $pid, $uid),
        );
        $connection->update($table, ['slug' => $slug], ['uid' => $uid]);
    }
}
