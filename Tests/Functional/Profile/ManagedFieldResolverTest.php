<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Profile;

use FGTCLB\AcademicPersons\Profile\ManagedFieldResolver;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

/**
 * Which fields of a stored person record a synchronisation owns. The fixture
 * extension `test_managed_fields` manages the profile title and website, the
 * contract position, the e-mail address and the phone number type, and
 * nothing of the physical addresses.
 *
 * The records are read from the database as they are stored, the shape the
 * import writer will hand in. The backend form hands in its own row, which
 * `ManagedFieldsReadOnlyTest` covers.
 */
final class ManagedFieldResolverTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-managed-fields';
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ManagedFields/records.csv');
    }

    /**
     * @return \Generator<string, array{string, int, list<string>}>
     */
    public static function records(): \Generator
    {
        yield 'a synchronised profile' => ['tx_academicpersons_domain_model_profile', 1, ['title', 'website']];
        yield 'a profile excluded from the synchronisation' => ['tx_academicpersons_domain_model_profile', 2, []];
        yield 'a hidden synchronised profile' => ['tx_academicpersons_domain_model_profile', 3, ['title', 'website']];
        yield 'a profile an editor created' => ['tx_academicpersons_domain_model_profile', 4, []];
        yield 'the translation of a synchronised profile' => ['tx_academicpersons_domain_model_profile', 5, []];
        yield 'a synchronised contract' => ['tx_academicpersons_domain_model_contract', 1, ['position']];
        yield 'a contract an editor added to a synchronised profile' => ['tx_academicpersons_domain_model_contract', 2, []];
        yield 'a synchronised contract of an excluded profile' => ['tx_academicpersons_domain_model_contract', 3, []];
        yield 'a synchronised contract of a hidden profile' => ['tx_academicpersons_domain_model_contract', 4, ['position']];
        yield 'the translation of a synchronised contract' => ['tx_academicpersons_domain_model_contract', 5, []];
        yield 'a synchronised e-mail address, two levels below its profile' => ['tx_academicpersons_domain_model_email', 1, ['email']];
        yield 'an e-mail address an editor added to a synchronised contract' => ['tx_academicpersons_domain_model_email', 2, []];
        yield 'a synchronised e-mail address of an excluded profile' => ['tx_academicpersons_domain_model_email', 3, []];
        yield 'a synchronised phone number, its field named by its property' => ['tx_academicpersons_domain_model_phone_number', 1, ['type']];
        yield 'a synchronised address of a record type that manages nothing' => ['tx_academicpersons_domain_model_address', 1, []];
    }

    /**
     * @param list<string> $expectedColumns
     */
    #[DataProvider('records')]
    #[Test]
    public function theManagedColumnsOfARecord(string $tableName, int $uid, array $expectedColumns): void
    {
        $this->assertSame(
            $expectedColumns,
            $this->get(ManagedFieldResolver::class)->getManagedColumns($tableName, $this->fetchRow($tableName, $uid)),
        );
    }

    #[Test]
    public function aTableOutsideTheMapHasNoManagedColumn(): void
    {
        $this->assertSame(
            [],
            $this->get(ManagedFieldResolver::class)->getManagedColumns(
                'pages',
                ['uid' => 20, 'import_identifier' => 'fe_users:1', 'sys_language_uid' => 0],
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRow(string $tableName, int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $row = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)))
            ->executeQuery()
            ->fetchAssociative();
        $this->assertIsArray($row);
        return $row;
    }
}
