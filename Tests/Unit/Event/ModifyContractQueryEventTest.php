<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Event;

use FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyContractQueryEventTest extends UnitTestCase
{
    #[Test]
    public function getQueryReturnsInstanceSetInConstructor(): void
    {
        $queryStub = $this->createStub(QueryInterface::class);
        $event = new ModifyContractQueryEvent($queryStub);
        $this->assertSame($queryStub, $event->getQuery());
    }

    #[Test]
    public function getConstraintsIsEmptyWhenNoListenerAddedOne(): void
    {
        $event = new ModifyContractQueryEvent($this->createStub(QueryInterface::class));
        $this->assertSame([], $event->getConstraints());
    }

    #[Test]
    public function getConstraintsReturnsEveryAddedConstraintInTheOrderItWasAdded(): void
    {
        $first = $this->createStub(ConstraintInterface::class);
        $second = $this->createStub(ConstraintInterface::class);
        $event = new ModifyContractQueryEvent($this->createStub(QueryInterface::class));
        $event->addConstraint($first);
        $event->addConstraint($second);
        $this->assertSame([$first, $second], $event->getConstraints());
    }
}
