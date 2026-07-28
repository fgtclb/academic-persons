<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\DeprecatedCoreLabelsTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @see DeprecatedCoreLabelsTrait
 */
final class DeprecatedCoreLabelsTest extends AbstractAcademicPersonsTestCase
{
    use DeprecatedCoreLabelsTrait;

    #[Group('not-core-13')]
    #[Test]
    public function tcaDoesNotReferenceCoreLabelsRetiredInV14(): void
    {
        $this->assertTcaHasNoDeprecatedCoreLabelReferences(['tx_academicpersons_']);
    }
}
