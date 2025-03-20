<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\DemandValues;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractDemandValues implements DemandValuesInterface
{
    /**
     * @var array<string, string>
     */
    protected array $values;

    public function __construct(private readonly ExtensionConfiguration $extensionConfiguration)
    {
        $this->values = [];
        $this->initialize();
    }

    /**
     * @return array<string, string>
     */
    public function getAll(): array
    {
        return $this->values;
    }

    protected function loadValuesByExtensionConfigurationProperty(string $property): void
    {
        if ($this->values !== []) {
            return;
        }

        $typesString = $this->extensionConfiguration->get('academic_persons', $property);
        $typesArray = GeneralUtility::trimExplode(',', $typesString);

        foreach ($typesArray as $type) {
            $typeValue = $typeLabel = $type;
            if (str_contains($type, '=')) {
                [$typeValue, $typeLabel] = GeneralUtility::trimExplode('=', $type, true, 2);
            }
            $this->values[$typeValue] = $typeLabel;
        }
    }

    abstract protected function initialize(): void;
}
