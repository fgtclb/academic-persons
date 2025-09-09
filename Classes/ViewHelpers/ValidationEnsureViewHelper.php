<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\ViewHelpers;

use FGTCLB\AcademicPersons\Settings\Validation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Get ensured validation setting for `$identifier` from `$validations`.
 *
 * This ViewHelper gets the validation configuration specified by `$identifier`
 * from provided `$validations` array. In case validation for `$identifier` is
 * not found, default (empty) validation set is returned with default values.
 *
 * Usages:
 *
 * ::
 *
 *      <f:variable name="validation"><p:validationEnsure validations="{element.validations}" identifier="{element.identifier}" /></f:variable>
 *      <f:form.textfield
 *          disabled="{validation.disabled ? 'disabled' : ''}"
 *          required="{validation.required ? 'required' : ''}"
 *          readonly="{validation.readOnly ? 'readonly' : ''}"
 *      />
 *
 * @internal not part of public API.
 */
final class ValidationEnsureViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('validations', 'array', 'Available validations', true);
        $this->registerArgument('identifier', 'string', 'Field identifier', true);
    }

    public function render(): Validation
    {
        $identifier = trim((string)($this->arguments['identifier'] ?? ''));
        if ($identifier === '') {
            throw new \InvalidArgumentException(
                'Identifier not provided.',
                1757473536,
            );
        }
        $validations = $this->arguments['validations'] ?? [];
        $validation = $validations[$identifier] ?? null;
        if (!($validation instanceof Validation)) {
            $validation = new Validation(
                identifier: $identifier,
                fieldName: GeneralUtility::camelCaseToLowerCaseUnderscored($identifier),
                required: false,
                disabled: false,
                readOnly: false,
                validatorClassNames: [],
                tcaConfig: [],
                inputType: 'text',
            );
        }
        return $validation;
    }
}
