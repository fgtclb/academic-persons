<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Profile;

use FGTCLB\AcademicPersons\Profile\ManagedFieldResolver;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ManagedFieldsSettings;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * What the resolver decides before it looks up a profile. Which stored
 * records are synchronised is covered by the functional test of the same name.
 */
final class ManagedFieldResolverTest extends UnitTestCase
{
    /**
     * A misspelled field would otherwise leave the field editable without a word.
     * Every record is refused, including one that has nothing managed.
     */
    #[Test]
    public function aMistakeInTheMapIsThrownForEveryRecord(): void
    {
        $resolver = $this->resolver(new ManagedFieldsSettings(
            fields: ['contracts' => ['position' => 'position']],
            problems: ['`managedFields.contracts` names `office`, which is not a field of `contracts.fields`.'],
        ));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1790536034);
        $this->expectExceptionMessage('names `office`');
        $resolver->getManagedColumns('tx_academicpersons_domain_model_email', ['import_identifier' => '']);
    }

    /**
     * FormEngine asks for every record it compiles. A page, a content element
     * or a user record is answered without looking at the map, so a mistake
     * in it leaves those forms alone.
     */
    #[Test]
    public function aTableOutsideThePersonTablesIgnoresAMistakeInTheMap(): void
    {
        $resolver = $this->resolver(
            new ManagedFieldsSettings(problems: ['`managedFields.contracts` names `office`, which is not a field of `contracts.fields`.']),
            $this->connectionPoolThatIsNeverAsked(),
        );

        $this->assertSame([], $resolver->getManagedColumns('pages', ['uid' => 1, 'import_identifier' => 'fe_users:1']));
    }

    /**
     * A record that is not a candidate costs no query: nothing is managed on its
     * table, it has no import identifier, or it is a translation.
     */
    #[Test]
    public function aRecordThatCannotBeManagedIsAnsweredWithoutAQuery(): void
    {
        $resolver = $this->resolver(
            new ManagedFieldsSettings(fields: ['contracts' => ['position' => 'position']]),
            $this->connectionPoolThatIsNeverAsked(),
        );

        $this->assertSame([], $resolver->getManagedColumns(
            'tx_academicpersons_domain_model_email',
            ['import_identifier' => 'fe_users:1', 'sys_language_uid' => 0, 'contract' => 1],
        ));
        $this->assertSame([], $resolver->getManagedColumns(
            'tx_academicpersons_domain_model_contract',
            ['import_identifier' => ' ', 'sys_language_uid' => 0, 'profile' => 1],
        ));
        $this->assertSame([], $resolver->getManagedColumns(
            'tx_academicpersons_domain_model_contract',
            ['import_identifier' => 'fe_users:1', 'sys_language_uid' => 1, 'profile' => 1],
        ));
    }

    /**
     * A profile row carries its own `skip_sync`, so it needs no query either.
     * FormEngine hands a checkbox value in as it is stored.
     */
    #[Test]
    public function aProfileIsDecidedByItsOwnRow(): void
    {
        $resolver = $this->resolver(
            new ManagedFieldsSettings(fields: ['profile' => ['title' => 'title', 'lastName' => 'last_name']]),
            $this->connectionPoolThatIsNeverAsked(),
        );
        $row = ['import_identifier' => 'fe_users:1', 'sys_language_uid' => 0, 'skip_sync' => 0];

        $this->assertSame(['title', 'last_name'], $resolver->getManagedColumns('tx_academicpersons_domain_model_profile', $row));
        $this->assertSame([], $resolver->getManagedColumns('tx_academicpersons_domain_model_profile', ['skip_sync' => '1'] + $row));
    }

    private function resolver(ManagedFieldsSettings $managedFields, ?ConnectionPool $connectionPool = null): ManagedFieldResolver
    {
        return new ManagedFieldResolver(
            new AcademicPersonsSettings(managedFields: $managedFields),
            $connectionPool ?? $this->createMock(ConnectionPool::class),
        );
    }

    private function connectionPoolThatIsNeverAsked(): ConnectionPool
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects($this->never())->method('getQueryBuilderForTable');
        return $connectionPool;
    }
}
