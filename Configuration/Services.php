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
use FGTCLB\AcademicPersons\Report\LegacySettingsStatus;
use FGTCLB\AcademicPersons\Types\TypesInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Reports\Registry\StatusRegistry;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder): void {
    $containerBuilder->registerForAutoconfiguration(TypesInterface::class)->setPublic(true);
    $containerBuilder->registerForAutoconfiguration(DemandValuesInterface::class)->setPublic(true);
    $containerBuilder->registerForAutoconfiguration(ProfileFactoryInterface::class)
        ->setPublic(true)
        ->setShared(true);

    // The status provider implements TYPO3\CMS\Reports\StatusProviderInterface, which
    // exists only with EXT:reports, so the class is kept out of the resource load of
    // Services.yaml and registered when that extension is active. Whether it is cannot
    // be asked here: this file runs while the container is built, before the package
    // manager reaches ExtensionManagementUtility or the container. A compiler pass runs
    // once every package's Services file is loaded, so the StatusRegistry EXT:reports
    // defines in its Services.yaml is a reliable signal on TYPO3 v13 and v14 alike, and
    // EXT:reports tags every autoconfigured implementation as "reports.status" itself -
    // which is why the pass has to run before Symfony's ResolveInstanceofConditionalsPass
    // at priority 100 of the same stage, or the definition arrives untagged.
    $containerBuilder->addCompilerPass(new class () implements CompilerPassInterface {
        public function process(ContainerBuilder $container): void
        {
            if (!$container->hasDefinition(StatusRegistry::class)) {
                return;
            }
            $container->register(LegacySettingsStatus::class, LegacySettingsStatus::class)
                ->setAutowired(true)
                ->setAutoconfigured(true);
        }
    }, PassConfig::TYPE_BEFORE_OPTIMIZATION, 500);
};
