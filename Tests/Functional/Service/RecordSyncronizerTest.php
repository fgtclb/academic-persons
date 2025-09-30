<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service;

use FGTCLB\AcademicPersons\Service\RecordSynchronizer;
use FGTCLB\AcademicPersons\Service\RecordSynchronizerInterface;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class RecordSyncronizerTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function serviceCanBeFetchedFromContainerByInterface(): void
    {
        $service = $this->get(RecordSynchronizerInterface::class);
        $this->assertInstanceOf(RecordSynchronizerInterface::class, $service);
        $this->assertInstanceOf(RecordSynchronizer::class, $service);
    }

    #[Test]
    public function serviceCanBeFetchedFromContainerByClassName(): void
    {
        $service = $this->get(RecordSynchronizer::class);
        $this->assertInstanceOf(RecordSynchronizerInterface::class, $service);
        $this->assertInstanceOf(RecordSynchronizer::class, $service);
    }

    #[Test]
    public function serviceCanBeFetchedFromGeneralUtilityByInterface(): void
    {
        $service = GeneralUtility::makeInstance(RecordSynchronizerInterface::class);
        $this->assertInstanceOf(RecordSynchronizerInterface::class, $service);
        $this->assertInstanceOf(RecordSynchronizer::class, $service);
    }

    #[Test]
    public function serviceCanBeFetchedFromGeneralUtilityByClassName(): void
    {
        $service = GeneralUtility::makeInstance(RecordSynchronizer::class);
        $this->assertInstanceOf(RecordSynchronizerInterface::class, $service);
        $this->assertInstanceOf(RecordSynchronizer::class, $service);
    }
}
