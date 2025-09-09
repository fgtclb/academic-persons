<?php

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * @internal not part of public API.
 * @todo Move this to `EXT:academic_base`.
 */
#[Exclude]
final class Validation
{
    /**
     * @param string $identifier
     * @param class-string<ValidatorInterface>[] $validatorClassNames
     * @param array<string, mixed> $tcaConfig
     */
    public function __construct(
        public readonly string $identifier,
        public readonly string $fieldName,
        public readonly bool $required,
        public readonly bool $disabled,
        public readonly bool $readOnly,
        public readonly array $validatorClassNames,
        public readonly array $tcaConfig,
        public readonly string $inputType = '',
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     fieldName: string,
     *     required: bool,
     *     disabled: bool,
     *     readOnly: bool,
     *     validatorClassNames: class-string<ValidatorInterface>[],
     *     tcaConfig: array<string, mixed>,
     *     inputType: string,
     * } $array
     * @return self
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            fieldName: $array['fieldName'],
            required: $array['required'],
            disabled: $array['disabled'],
            readOnly: $array['readOnly'],
            validatorClassNames: $array['validatorClassNames'],
            tcaConfig: $array['tcaConfig'],
            inputType: $array['inputType'],
        );
    }
}
