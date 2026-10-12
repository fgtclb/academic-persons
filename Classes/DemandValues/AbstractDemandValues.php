<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\DemandValues;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
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

        try {
            $typesString = $this->extensionConfiguration->get('academic_persons', $property);
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException) {
            // `get()` restores a path that `ext_conf_template.txt` declares, so these are
            // thrown only for an extension or a path the template does not declare. An
            // empty list leaves the select with its "none" item and the list in uid order.
            // Throwing here would abort every list rendering and the backend form.
            return;
        }
        $typesArray = GeneralUtility::trimExplode(',', is_string($typesString) ? $typesString : '', true);

        foreach ($typesArray as $type) {
            // The empty side of `value=` or `=label` must keep its position, so it is not
            // removed here. An empty value is skipped: as an item '' it would be the value
            // of "none" and let "none" pass the allow list in `ProfileRepository`.
            [$typeValue, $typeLabel] = GeneralUtility::trimExplode('=', $type, false, 2) + [1 => ''];
            if ($typeValue === '') {
                continue;
            }
            $this->values[$typeValue] = $typeLabel !== '' ? $typeLabel : $typeValue;
        }
    }

    abstract protected function initialize(): void;
}
