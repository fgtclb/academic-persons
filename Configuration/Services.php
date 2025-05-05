<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons;

use FGTCLB\AcademicPersons\DemandValues\DemandValuesInterface;
use FGTCLB\AcademicPersons\Profile\ProfileFactoryInterface;
use FGTCLB\AcademicPersons\Types\TypesInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder): void {
    $containerBuilder->registerForAutoconfiguration(TypesInterface::class)->setPublic(true);
    $containerBuilder->registerForAutoconfiguration(DemandValuesInterface::class)->setPublic(true);
    $containerBuilder->registerForAutoconfiguration(ProfileFactoryInterface::class)->setPublic(true);
};
