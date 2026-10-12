<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Types;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
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

        try {
            $typesString = $this->extensionConfiguration->get('academic_persons', $property);
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException) {
            // `get()` restores a path that `ext_conf_template.txt` declares, so these are
            // thrown only for an extension or a path the template does not declare. An
            // empty list degrades the select to its shipped `undefined` item. Throwing
            // here would abort the backend form while it renders.
            return;
        }
        $typesArray = GeneralUtility::trimExplode(',', is_string($typesString) ? $typesString : '', true);

        foreach ($typesArray as $type) {
            // The empty side of `value=` or `=label` must keep its position, so it is not
            // removed here. An empty value would be a second item '' beside the shipped
            // `undefined` one, and is skipped.
            [$typeValue, $typeLabel] = GeneralUtility::trimExplode('=', $type, false, 2) + [1 => ''];
            if ($typeValue === '') {
                continue;
            }
            $this->types[$typeValue] = $typeLabel !== '' ? $typeLabel : $typeValue;
        }
    }

    abstract protected function initialize(): void;
}
