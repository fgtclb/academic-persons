<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Tca;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use FGTCLB\AcademicPersons\Tca\ContractLabels;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The `label_userFunc` of the contract table. The backend calls it for every record
 * title it renders, also for a record that is deleted or missing, for example from the
 * open documents or the history, and hands it no row or a row without a uid then.
 */
final class ContractLabelsTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @param array<string, mixed> $parameters
     */
    #[Test]
    #[DataProvider('parametersWithoutARecordUidProvider')]
    public function parametersWithoutARecordUidLeaveTheTitleUntouched(array $parameters): void
    {
        $repository = $this->createMock(ContractRepository::class);
        $repository->expects($this->never())->method('findByUid');
        GeneralUtility::setSingletonInstance(ContractRepository::class, $repository);

        $expected = $parameters;
        (new ContractLabels())->getTitle($parameters);

        $this->assertSame($expected, $parameters);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function parametersWithoutARecordUidProvider(): array
    {
        return [
            'no row key' => [['table' => 'tx_academicpersons_domain_model_contract', 'title' => '']],
            'row is null' => [['table' => 'tx_academicpersons_domain_model_contract', 'row' => null, 'title' => '']],
            'row is empty' => [['table' => 'tx_academicpersons_domain_model_contract', 'row' => [], 'title' => '']],
            'row without uid' => [['table' => 'tx_academicpersons_domain_model_contract', 'row' => ['pid' => 1], 'title' => '']],
        ];
    }

    #[Test]
    public function aRecordUidIsLabelledByItsModel(): void
    {
        $record = $this->createMock(Contract::class);
        $record->method('getLabel')->willReturn('Label of record 5');
        $repository = $this->createMock(ContractRepository::class);
        $repository->expects($this->once())->method('findByUid')->with(5)->willReturn($record);
        GeneralUtility::setSingletonInstance(ContractRepository::class, $repository);

        $parameters = ['table' => 'tx_academicpersons_domain_model_contract', 'row' => ['uid' => 5, 'pid' => 1], 'title' => ''];
        (new ContractLabels())->getTitle($parameters);

        $this->assertSame('Label of record 5', $parameters['title']);
    }
}
