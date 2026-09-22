<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Event;

use FGTCLB\AcademicPersons\Domain\Model\Dto\DemandInterface;
use FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyProfileQueryEventTest extends UnitTestCase
{
    #[Test]
    public function getQueryReturnsInstanceSetInConstructor(): void
    {
        $queryStub = $this->createStub(QueryInterface::class);
        $event = new ModifyProfileQueryEvent($queryStub, null);
        $this->assertSame($queryStub, $event->getQuery());
    }

    #[Test]
    public function getDemandReturnsInstanceSetInConstructor(): void
    {
        $demandStub = $this->createStub(DemandInterface::class);
        $event = new ModifyProfileQueryEvent($this->createStub(QueryInterface::class), $demandStub);
        $this->assertSame($demandStub, $event->getDemand());
    }

    /**
     * A lookup of the uids an editor selected knows no list demand, and a listener has to be
     * able to tell that case apart from a list rendering.
     */
    #[Test]
    public function getDemandReturnsNullForAUidLookup(): void
    {
        $event = new ModifyProfileQueryEvent($this->createStub(QueryInterface::class), null);
        $this->assertNull($event->getDemand());
    }

    #[Test]
    public function getConstraintsIsEmptyWhenNoListenerAddedOne(): void
    {
        $event = new ModifyProfileQueryEvent($this->createStub(QueryInterface::class), null);
        $this->assertSame([], $event->getConstraints());
    }

    #[Test]
    public function getConstraintsReturnsEveryAddedConstraintInTheOrderItWasAdded(): void
    {
        $first = $this->createStub(ConstraintInterface::class);
        $second = $this->createStub(ConstraintInterface::class);
        $event = new ModifyProfileQueryEvent($this->createStub(QueryInterface::class), null);
        $event->addConstraint($first);
        $event->addConstraint($second);
        $this->assertSame([$first, $second], $event->getConstraints());
    }
}
