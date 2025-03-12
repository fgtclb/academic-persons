<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Fgtclb\AcademicPersons\Tests\Functional\Fixtures\Trait;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Site\SiteSettingsFactory;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\Frontend\PhpError;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\AbstractInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\ArrayValueInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\TypoScriptInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Trait used for test classes that want to set up (= write) site configuration files.
 *
 * Mainly used when testing Site-related tests in Frontend requests.
 *
 * Be sure to set the LANGUAGE_PRESETS const in your class.
 */
trait SiteBasedTestTrait
{
    /**
     * @param string[] $items
     */
    protected static function failIfArrayIsNotEmpty(array $items): void
    {
        if (empty($items)) {
            return;
        }

        static::fail(
            'Array was not empty as expected, but contained these items:' . LF
            . '* ' . implode(LF . '* ', $items)
        );
    }

    /**
     * @param string $identifier
     * @param array<string, mixed> $site
     * @param list<array<string, mixed>> $languages
     * @param array<string, mixed> $errorHandling
     */
    protected function writeSiteConfiguration(
        string $identifier,
        array $site = [],
        array $languages = [],
        array $errorHandling = []
    ): void {
        $configuration = $site;
        if (!empty($languages)) {
            $configuration['languages'] = $languages;
        }
        if (!empty($errorHandling)) {
            $configuration['errorHandling'] = $errorHandling;
        }

        try {
            // ensure no previous site configuration influences the test
            GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites/' . $identifier, true);
            if (!class_exists(SiteWriter::class)) {
                // @phpstan-ignore-next-line PHPStan has issues detecting this correctly
                $this->createSiteConfiguration($this->instancePath . '/typo3conf/sites/')->write($identifier, $configuration);
            } else {
                // @phpstan-ignore-next-line PHPStan has issues detecting this correctly
                $this->get(SiteWriter::class)->write($identifier, $configuration);
            }
        } catch (\Exception $exception) {
            self::fail($exception->getMessage());
        }
    }

    /**
     * @param string $identifier
     * @param array<string, mixed> $overrides
     */
    protected function mergeSiteConfiguration(
        string $identifier,
        array $overrides
    ): void {
        $siteConfiguration = $this->createSiteConfiguration($this->instancePath . '/typo3conf/sites/');
        $configuration = $siteConfiguration->load($identifier);
        $configuration = array_merge($configuration, $overrides);
        try {
            if (!class_exists(SiteWriter::class)) {
                // @phpstan-ignore-next-line PHPStan has issues detecting this correctly
                $this->createSiteConfiguration($this->instancePath . '/typo3conf/sites/')->write($identifier, $configuration);
            } else {
                // @phpstan-ignore-next-line PHPStan has issues detecting this correctly
                $this->get(SiteWriter::class)->write($identifier, $configuration);
            }
        } catch (\Exception $exception) {
            self::fail($exception->getMessage());
        }
    }

    /**
     * @param array<non-empty-string, mixed> $additionalRootConfiguration
     * @return array<string, mixed>
     */
    protected function buildSiteConfiguration(
        int $rootPageId,
        string $base = '',
        string $websiteTitle='',
        array $additionalRootConfiguration = []
    ): array {
        return array_merge([
            'rootPageId' => $rootPageId,
            'base' => $base,
            'websiteTitle' => $websiteTitle,
        ], $additionalRootConfiguration);
    }

    protected function createSiteConfiguration(string $path): SiteConfiguration
    {
        if (!class_exists(SiteSettingsFactory::class)) {
            // @phpstan-ignore arguments.count
            return new SiteConfiguration(
                $path,
                // @phpstan-ignore argument.type
                $this->get(EventDispatcherInterface::class),
                // @phpstan-ignore argument.type
                $this->get('cache.core')
            );
        }
        // @phpstan-ignore arguments.count,argument.type
        return new SiteConfiguration(
            $path,
            // @phpstan-ignore argument.type
            $this->get(SiteSettingsFactory::class),
            // @phpstan-ignore argument.type
            $this->get(SetRegistry::class),
            // @phpstan-ignore argument.type
            $this->get(EventDispatcherInterface::class),
            // @phpstan-ignore argument.type
            $this->get('cache.core'),
            // @phpstan-ignore argument.type
            $this->get(YamlFileLoader::class),
            // @phpstan-ignore argument.type
            $this->get('cache.runtime')
        );
    }

    /**
     * @param string $identifier
     * @param string $base
     * @return array<string, mixed>
     */
    protected function buildDefaultLanguageConfiguration(
        string $identifier,
        string $base
    ): array {
        $configuration = $this->buildLanguageConfiguration($identifier, $base);
        $configuration['flag'] = 'global';
        unset($configuration['fallbackType'], $configuration['fallbacks']);
        return $configuration;
    }

    /**
     * @param string $identifier
     * @param string $base
     * @param array<int, string> $fallbackIdentifiers
     * @param string $fallbackType
     * @return array<string, mixed>
     */
    protected function buildLanguageConfiguration(
        string $identifier,
        string $base,
        array $fallbackIdentifiers = [],
        ?string $fallbackType = null
    ): array {
        $preset = $this->resolveLanguagePreset($identifier);

        $configuration = [
            'languageId' => $preset['id'],
            'title' => $preset['title'],
            'navigationTitle' => $preset['title'],
            'base' => $base,
            'locale' => $preset['locale'],
            'flag' => $preset['flag'] ?? $preset['iso'] ?? '',
            'fallbackType' => $fallbackType ?? (empty($fallbackIdentifiers) ? 'strict' : 'fallback'),
        ];
        if (isset($preset['custom'])) {
            $configuration = array_replace(
                $configuration,
                $preset['custom']
            );
        }

        if (!empty($fallbackIdentifiers)) {
            $fallbackIds = array_map(
                function (string $fallbackIdentifier) {
                    $preset = $this->resolveLanguagePreset($fallbackIdentifier);
                    return $preset['id'];
                },
                $fallbackIdentifiers
            );
            $configuration['fallbackType'] = $fallbackType ?? 'fallback';
            $configuration['fallbacks'] = implode(',', $fallbackIds);
        }

        return $configuration;
    }

    /**
     * @param string $handler
     * @param int[] $codes
     * @return array<string, mixed>
     */
    protected function buildErrorHandlingConfiguration(
        string $handler,
        array $codes
    ): array {
        if ($handler === 'Page') {
            // This implies you cannot test both 404 and 403 in the same test.
            // Fixing that requires much deeper changes to the testing harness,
            // as the structure here is only a portion of the config array structure.
            if (in_array(404, $codes, true)) {
                $baseConfiguration = [
                    'errorContentSource' => 't3://page?uid=404',
                ];
            } elseif (in_array(403, $codes, true)) {
                $baseConfiguration = [
                    'errorContentSource' => 't3://page?uid=403',
                ];
            }
        } elseif ($handler === 'Fluid') {
            $baseConfiguration = [
                'errorFluidTemplate' => 'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/FluidError.html',
                'errorFluidTemplatesRootPath' => '',
                'errorFluidLayoutsRootPath' => '',
                'errorFluidPartialsRootPath' => '',
            ];
        } elseif ($handler === 'PHP') {
            $baseConfiguration = [
                'errorPhpClassFQCN' => PhpError::class,
            ];
        } else {
            throw new \LogicException(
                sprintf('Invalid handler "%s"', $handler),
                1533894782
            );
        }

        $baseConfiguration['errorHandler'] = $handler;

        return array_map(
            static function (int $code) use ($baseConfiguration) {
                $baseConfiguration['errorCode'] = $code;
                return $baseConfiguration;
            },
            $codes
        );
    }

    /**
     * @param string $identifier
     * @return mixed
     */
    protected function resolveLanguagePreset(string $identifier)
    {
        if (!isset(static::LANGUAGE_PRESETS[$identifier])) {
            throw new \LogicException(
                sprintf('Undefined preset identifier "%s"', $identifier),
                1533893665
            );
        }
        return static::LANGUAGE_PRESETS[$identifier];
    }

    /**
     * @param InternalRequest $request
     * @param AbstractInstruction ...$instructions
     * @return InternalRequest
     *
     * @todo Instruction handling should be part of Testing Framework (multiple instructions per identifier, merge in interface)
     */
    protected function applyInstructions(InternalRequest $request, AbstractInstruction ...$instructions): InternalRequest
    {
        $modifiedInstructions = [];

        foreach ($instructions as $instruction) {
            $identifier = $instruction->getIdentifier();
            $useModifier = $modifiedInstructions[$identifier] ?? $request->getInstruction($identifier);
            if ($useModifier !== null) {
                // @phpstan-ignore-next-line
                $modifiedInstructions[$identifier] = $this->mergeInstruction($useModifier, $instruction);
            } else {
                $modifiedInstructions[$identifier] = $instruction;
            }
        }
        // @phpstan-ignore-next-line
        return $request->withInstructions($modifiedInstructions);
    }

    /**
     * @param AbstractInstruction $current
     * @param AbstractInstruction $other
     * @return AbstractInstruction
     */
    protected function mergeInstruction(AbstractInstruction $current, AbstractInstruction $other): AbstractInstruction
    {
        if (get_class($current) !== get_class($other)) {
            throw new \LogicException('Cannot merge different instruction types', 1565863174);
        }

        if ($current instanceof TypoScriptInstruction) {
            /** @var TypoScriptInstruction $other */
            $typoScript = array_replace_recursive(
                $current->getTypoScript() ?? [],
                $other->getTypoScript() ?? []
            );
            $constants = array_replace_recursive(
                $current->getConstants() ?? [],
                $other->getConstants() ?? []
            );
            if ($typoScript !== []) {
                $current = $current->withTypoScript($typoScript);
            }
            if ($constants !== []) {
                $current = $current->withConstants($constants);
            }
            return $current;
        }

        if ($current instanceof ArrayValueInstruction) {
            /** @var ArrayValueInstruction $other */
            $array = array_merge_recursive($current->getArray(), $other->getArray());
            return $current->withArray($array);
        }

        return $current;
    }
}
