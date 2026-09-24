<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Unit\DataHandling;

use FGTCLB\AcademicPersons\DataHandling\ProfileWriteCorrelation;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProfileWriteCorrelationTest extends UnitTestCase
{
    #[Test]
    public function aCreatedCorrelationIdIsReadBackAsItsCase(): void
    {
        $this->assertSame(
            ProfileWriteCorrelation::Internal,
            ProfileWriteCorrelation::fromCorrelationId(ProfileWriteCorrelation::Internal->create()),
        );
        $this->assertSame(
            ProfileWriteCorrelation::Import,
            ProfileWriteCorrelation::fromCorrelationId(ProfileWriteCorrelation::Import->create()),
        );
    }

    #[Test]
    public function aRunOfAnyOtherCodeCarriesNoCase(): void
    {
        $this->assertNull(ProfileWriteCorrelation::fromCorrelationId(null));
        $this->assertNull(ProfileWriteCorrelation::fromCorrelationId(CorrelationId::forScope('abc123')));
        $this->assertNull(
            ProfileWriteCorrelation::fromCorrelationId(CorrelationId::forScope('abc123')->withAspects('5d8e6e70', 'slug')),
        );
    }

    /**
     * The mark is an aspect and the scope stays random per run, like the one the
     * DataHandler creates itself: the history store derives the correlation id of
     * every row it writes from the scope, so a fixed scope would give every
     * synchronisation of a record one and the same history correlation id.
     */
    #[Test]
    public function everyCreatedCorrelationIdHasAScopeOfItsOwn(): void
    {
        $first = ProfileWriteCorrelation::Internal->create();
        $second = ProfileWriteCorrelation::Internal->create();

        $this->assertSame(['academic-persons-internal'], $first->getAspects());
        $this->assertNotNull($first->getScope());
        $this->assertNotSame($first->getScope(), $second->getScope());
        $this->assertNull($first->getSubject(), 'The history store adds the subject per record.');
    }

    #[Test]
    public function theMarkSurvivesTheSubjectTheHistoryStoreAdds(): void
    {
        $withSubject = CorrelationId::fromString((string)ProfileWriteCorrelation::Import->create()->withSubject('d41d8cd98f00b204'));

        $this->assertSame(ProfileWriteCorrelation::Import, ProfileWriteCorrelation::fromCorrelationId($withSubject));
    }
}
