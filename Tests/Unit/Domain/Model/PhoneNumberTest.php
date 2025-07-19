<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Domain\Model;

use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PhoneNumberTest extends UnitTestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        new PhoneNumber();
    }

    #[Test]
    public function getContractReturnsNullForNewModel(): void
    {
        $this->assertNull((new PhoneNumber())->getContract());
    }

    #[Test]
    public function getPhoneNumberReturnsEmptyStringForNewModel(): void
    {
        $this->assertSame('', (new PhoneNumber())->getPhoneNumber());
    }

    #[Test]
    public function getTypeReturnsEmptyStringForNewModel(): void
    {
        $this->assertSame('', (new PhoneNumber())->getType());
    }

    #[Test]
    public function getSortingReturnsIntegerZeroForNewModel(): void
    {
        $this->assertSame(0, (new PhoneNumber())->getSorting());
    }
}
