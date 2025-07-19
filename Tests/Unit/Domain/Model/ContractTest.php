<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Domain\Model;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ContractTest extends UnitTestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        new Contract();
    }

    #[Test]
    public function getProfileReturnsNullForNewModel(): void
    {
        $this->assertNull((new Contract())->getProfile());
    }

    #[Test]
    public function getOrganizationaUnitReturnsNullForNewModel(): void
    {
        $this->assertNull((new Contract())->getOrganisationalUnit());
    }

    #[Test]
    public function getFunctionTypeReturnsNulForNewModel(): void
    {
        $this->assertNull((new Contract())->getFunctionType());
    }

    #[Test]
    public function getEmployeeTypeReturnsNullForNewModel(): void
    {
        $this->assertNull((new Contract())->getEmployeeType());
    }

    #[Test]
    public function getLocationReturnsNullForNewModel(): void
    {
        $this->assertNull((new Contract())->getLocation());
    }

    #[Test]
    public function getValidFromReturnsNullForNewModel(): void
    {
        $this->assertNull((new Contract())->getValidFrom());
    }

    #[Test]
    public function getValidToReturnsNullForNewModel(): void
    {
        $this->assertNull((new Contract())->getValidTo());
    }

    #[Test]
    public function getPositionReturnsEmptyStringForNewModel(): void
    {
        $this->assertSame('', (new Contract())->getPosition());
    }

    #[Test]
    public function getRoomReturnsEmptyStringForNewModel(): void
    {
        $this->assertSame('', (new Contract())->getRoom());
    }

    #[Test]
    public function getOfficeHoursReturnsEmptyStringForNewModel(): void
    {
        $this->assertSame('', (new Contract())->getOfficeHours());
    }

    #[Test]
    public function getPublishReturnsFalseForNewModel(): void
    {
        $this->assertFalse((new Contract())->getPublish());
    }

    #[Test]
    public function isPublishReturnsFalseForNewModel(): void
    {
        $this->assertFalse((new Contract())->isPublish());
    }
}
