<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractAcademicPersonsTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
    ];

    protected function addCoreExtension(string ...$extensions): void
    {
        foreach ($extensions as $extension) {
            if ($extension !== '' && !in_array($extension, $this->coreExtensionsToLoad)) {
                $this->coreExtensionsToLoad[] = $extension;
            }
        }
    }

    protected function addTestExtension(string ...$extensions): void
    {
        foreach ($extensions as $extension) {
            if ($extension !== '' && !in_array($extension, $this->testExtensionsToLoad)) {
                $this->testExtensionsToLoad[] = $extension;
            }
        }
    }
}
