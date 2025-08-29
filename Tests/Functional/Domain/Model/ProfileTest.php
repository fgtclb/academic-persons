<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Model;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

final class ProfileTest extends AbstractAcademicPersonsTestCase
{
    /**
     * This test demonstrates that using extbase does not handle record slug creation for new records,
     * like it is done in the TYPO3 backend surface or using the {@see DataHandler}. Note that for the
     * alpha name field variants `first_name_alpha` and `last_name_alpha` the same missing handling
     * occurs.
     *
     * @todo    Investigate automatic slug value generation when persisting extbase models and evaluate if adding
     *          `slug` property and getter/setter to the {@see Profile} model makes sense or if handling needs to
     *          be done transparently using some suiting PSR-14 event (extbase) or hooks available for extbase
     *          persisting.
     */
    #[Test]
    public function persistingNewProfileDoesNotCreateUniqueSlugOrNameAlphaVariants(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/PageTree.csv');
        $profile = new Profile();
        $profile->setPid(2);
        $profile
            ->setGender('mr')
            ->setTitle('Cpt.')
            ->setFirstName('James')
            ->setMiddleName('Tiberius')
            ->setLastName('Kirk');
        /** @var PersistenceManager $persistenceManager */
        $persistenceManager = $this->get(PersistenceManagerInterface::class);
        $persistenceManager->add($profile);
        $persistenceManager->persistAll();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Profile/persistingNewProfileCreatesValidSlug.csv');
    }
}
