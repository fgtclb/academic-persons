<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Domain\Model;

use FGTCLB\AcademicPersons\Domain\Model\Email;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class EmailTest extends UnitTestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        new Email();
    }

    #[Test]
    public function getContractReturnsNullForNewModel(): void
    {
        $this->assertNull((new Email())->getContract());
    }

    #[Test]
    public function getEmailReturnsEmptyStringForNewModel(): void
    {
        $this->assertSame('', (new Email())->getEmail());
    }

    #[Test]
    public function getTypeReturnsEmptyStringForNewModel(): void
    {
        $this->assertSame('', (new Email())->getType());
    }

    #[Test]
    public function getSortingReturnsIntegerZeroForNewModel(): void
    {
        $this->assertSame(0, (new Email())->getSorting());
    }
}
