<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Domain\Model;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\OrganisationalUnit;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Everything on this model is a plain accessor except the `contracts` storage: it is a
 * typed property without a default that `initializeObject()` has to fill, and it is the
 * only place in the package with `add`/`remove` methods delegating to `ObjectStorage`.
 * Those inherit `SplObjectStorage` semantics - identity based, silently idempotent -
 * which is what the tests below pin.
 */
final class OrganisationalUnitTest extends UnitTestCase
{
    /**
     * `contracts` has no default value, so a missing initialisation is a fatal error on
     * first read rather than an empty result.
     */
    #[Test]
    public function contractsIsAnEmptyStorageAfterConstruction(): void
    {
        $this->assertSame(0, (new OrganisationalUnit())->getContracts()->count());
    }

    /**
     * `initializeObject()` is public and Extbase calls it on objects it is about to map
     * into. Replacing the storage instead of keeping it is the defined behaviour.
     */
    #[Test]
    public function initializeObjectDiscardsWhatTheStorageAlreadyHeld(): void
    {
        $unit = new OrganisationalUnit();
        $unit->addContract(new Contract());
        $this->assertSame(1, $unit->getContracts()->count());

        $unit->initializeObject();

        $this->assertSame(0, $unit->getContracts()->count());
    }

    /**
     * The fluent return value is what makes a chained build-up possible, so it is
     * exercised rather than asserted separately.
     */
    #[Test]
    public function addContractAttachesToTheExistingStorage(): void
    {
        $first = new Contract();
        $second = new Contract();

        $unit = (new OrganisationalUnit())->addContract($first)->addContract($second);

        $this->assertSame([$first, $second], iterator_to_array($unit->getContracts(), false));
    }

    /**
     * `ObjectStorage::attach()` is identity based: adding the same instance twice is a
     * no-op, not a duplicate. Relevant because the editor plugin re-submits the whole
     * relation on every save.
     */
    #[Test]
    public function addingTheSameContractTwiceKeepsOneEntry(): void
    {
        $contract = new Contract();

        $unit = (new OrganisationalUnit())->addContract($contract)->addContract($contract);

        $this->assertSame(1, $unit->getContracts()->count());
    }

    /**
     * Two distinct instances are two entries even when they carry equal data - the
     * storage compares identity, never value.
     */
    #[Test]
    public function twoDistinctContractsWithEqualDataAreBothKept(): void
    {
        $unit = new OrganisationalUnit();
        $unit->addContract((new Contract())->setPosition('Professor'));
        $unit->addContract((new Contract())->setPosition('Professor'));

        $this->assertSame(2, $unit->getContracts()->count());
    }

    #[Test]
    public function removeContractDetachesOnlyTheGivenContract(): void
    {
        $kept = new Contract();
        $removed = new Contract();
        $unit = (new OrganisationalUnit())->addContract($kept)->addContract($removed);

        $unit->removeContract($removed);

        $this->assertSame([$kept], iterator_to_array($unit->getContracts(), false));
    }

    /**
     * `detach()` of an absent object does not raise, so a remove that arrives twice - a
     * double submit, or a relation already cleared elsewhere - must not take the request
     * down.
     */
    #[Test]
    public function removingAContractThatWasNeverAddedIsIgnored(): void
    {
        $unit = new OrganisationalUnit();
        $unit->addContract(new Contract());

        $unit->removeContract(new Contract());

        $this->assertSame(1, $unit->getContracts()->count());
    }

    /**
     * `setContracts()` replaces the storage rather than merging into it, and it keeps the
     * caller's instance - the caller therefore still holds a live handle on it.
     */
    #[Test]
    public function setContractsReplacesTheStorageInstance(): void
    {
        $unit = new OrganisationalUnit();
        $unit->addContract(new Contract());
        /** @var ObjectStorage<Contract> $replacement */
        $replacement = new ObjectStorage();
        $replacement->attach(new Contract());
        $replacement->attach(new Contract());

        $unit->setContracts($replacement);

        $this->assertSame($replacement, $unit->getContracts());
        $this->assertSame(2, $unit->getContracts()->count());
    }
}
