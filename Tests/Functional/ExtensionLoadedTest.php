<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractAcademicPersonsTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        // extension keys
        'academic_base',
        'academic_persons',
    ];
}
