<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

final class ExtensionLoadedTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function testCaseLoadsExtension(): void
    {
        $this->assertContains('fgtclb/academic-persons', $this->testExtensionsToLoad);
    }

    #[Test]
    public function extensionIsLoaded(): void
    {
        $this->assertTrue(ExtensionManagementUtility::isLoaded('academic_persons'));
    }
}
