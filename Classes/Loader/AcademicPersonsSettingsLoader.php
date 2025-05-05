<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Loader;

use FGTCLB\AcademicPersons\Registry\AcademicPersonsSettingsRegistry as SettingsRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;

class AcademicPersonsSettingsLoader
{
    protected ?SettingsRegistry $settingsRegistry = null;

    public function __construct(
        #[Autowire(service: 'cache.core')]
        protected readonly PhpFrontend $cache,
        protected readonly PackageManager $packageManager
    ) {}

    public function load(): SettingsRegistry
    {
        if ($this->settingsRegistry !== null) {
            return $this->settingsRegistry;
        }
        $this->settingsRegistry = new SettingsRegistry();

        // Load cached settings
        $settings = $this->getFromCache();
        if (is_array($settings)) {
            $this->settingsRegistry->attach($settings);
        } else {
            // Load from extension yaml files and populate cache
            $settings = $this->loadUncached();
            $this->settingsRegistry->attach($settings);
            $this->setCache($this->settingsRegistry->getSettings());
        }
        // Fallback only added to satisfy phpstan. Technically not possible.
        return $this->settingsRegistry ?? new SettingsRegistry();
    }

    /**
     * @return array<string, mixed>
     */
    public function loadUncached(): array
    {
        $loadedSettings = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $settingsFile = $package->getPackagePath() . 'Configuration/AcademicPersons/Settings.yaml';
            if (file_exists($settingsFile)) {
                $settingsArray = Yaml::parseFile($settingsFile);
                if ($settingsArray === null) {
                    continue;
                }
                $loadedSettings = array_merge($loadedSettings, $settingsArray);
            }
        }
        return $loadedSettings;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFromCache(): ?array
    {
        $settings = $this->cache->require($this->academicPersonsSettingsIdentifier());
        if (!is_array($settings)) {
            return null;
        }
        return $settings;
    }

    /**
     * @param array<string, mixed> $settings
     */
    protected function setCache(array $settings): void
    {
        $this->cache->set($this->academicPersonsSettingsIdentifier(), 'return ' . var_export($settings, true) . ';');
    }

    /**
     * @return non-empty-string string
     */
    protected function academicPersonsSettingsIdentifier(): string
    {
        return 'AcademicPersons_Settings';
    }
}
