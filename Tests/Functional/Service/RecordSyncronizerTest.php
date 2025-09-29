<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service;

use FGTCLB\AcademicPersons\Service\RecordSyncronizer;
use FGTCLB\AcademicPersons\Service\RecordSyncronizerInterface;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class RecordSyncronizerTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function serviceCanBeFetchedFromContainerByInterface(): void
    {
        $service = $this->get(RecordSyncronizerInterface::class);
        $this->assertInstanceOf(RecordSyncronizerInterface::class, $service);
        $this->assertInstanceOf(RecordSyncronizer::class, $service);
    }

    #[Test]
    public function serviceCanBeFetchedFromContainerByClassName(): void
    {
        $service = $this->get(RecordSyncronizer::class);
        $this->assertInstanceOf(RecordSyncronizerInterface::class, $service);
        $this->assertInstanceOf(RecordSyncronizer::class, $service);
    }

    #[Test]
    public function serviceCanBeFetchedFromGeneralUtilityByInterface(): void
    {
        $service = GeneralUtility::makeInstance(RecordSyncronizerInterface::class);
        $this->assertInstanceOf(RecordSyncronizerInterface::class, $service);
        $this->assertInstanceOf(RecordSyncronizer::class, $service);
    }

    #[Test]
    public function serviceCanBeFetchedFromGeneralUtilityByClassName(): void
    {
        $service = GeneralUtility::makeInstance(RecordSyncronizer::class);
        $this->assertInstanceOf(RecordSyncronizerInterface::class, $service);
        $this->assertInstanceOf(RecordSyncronizer::class, $service);
    }
}
