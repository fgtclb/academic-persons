<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Settings\Validation;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * One direct property of the profile record, grouped into a {@see ProfileSection}
 * by its `section`. `fieldType` and `renderType` describe the frontend control;
 * the TCA column keeps its own type. `helptext` is the label reference or text
 * rendered next to the control.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class ProfileField
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $section,
        public readonly string $propertyName,
        public readonly string $fieldName,
        public readonly string $fieldType,
        public readonly string $renderType,
        public readonly Validation $validation,
        public readonly int $position,
        public readonly string $helptext = '',
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     section: string,
     *     propertyName: string,
     *     fieldName: string,
     *     fieldType: string,
     *     renderType: string,
     *     validation: Validation,
     *     position: int,
     *     helptext?: string,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            section: $array['section'],
            propertyName: $array['propertyName'],
            fieldName: $array['fieldName'],
            fieldType: $array['fieldType'],
            renderType: $array['renderType'],
            validation: $array['validation'],
            position: $array['position'],
            helptext: $array['helptext'] ?? '',
        );
    }

    public function isValid(): bool
    {
        return $this->identifier !== ''
            && $this->section !== ''
            && $this->propertyName !== ''
            && $this->fieldName !== ''
            && $this->fieldType !== ''
            && $this->renderType !== '';
    }
}
