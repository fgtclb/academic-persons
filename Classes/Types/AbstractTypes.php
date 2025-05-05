<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Types;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractTypes implements TypesInterface
{
    /**
     * @var array<string, string>
     */
    protected array $types;

    public function __construct(private readonly ExtensionConfiguration $extensionConfiguration)
    {
        $this->types = [];
        $this->initialize();
    }

    /**
     * @return array<string, string>
     */
    public function getAll(): array
    {
        return $this->types;
    }

    protected function loadTypesByExtensionConfigurationProperty(string $property): void
    {
        if ($this->types !== []) {
            return;
        }

        $typesString = $this->extensionConfiguration->get('academic_persons', $property);
        $typesArray = GeneralUtility::trimExplode(',', $typesString);

        foreach ($typesArray as $type) {
            $typeValue = $typeLabel = $type;
            if (str_contains($type, '=')) {
                [$typeValue, $typeLabel] = GeneralUtility::trimExplode('=', $type, true, 2);
            }
            $this->types[$typeValue] = $typeLabel;
        }
    }

    abstract protected function initialize(): void;
}
