<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons;

use TYPO3\CMS\Core\Package\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    protected static function getPackageName(): string
    {
        return 'fgtclb/academic-persons';
    }

    public function getFactories(): array
    {
        return [];
    }

    public function getExtensions(): array
    {
        return [] + parent::getExtensions();
    }
}
