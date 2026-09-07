<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Settings\ValidationSet;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * One list of records attached to a profile: the seven profile information
 * types, and the contracts. `type` is the record type the rows are selected
 * by and `fieldName` the profile relation they hang off. `rowFields` and
 * `actions` are the ordered, validated lists of what a compact row shows and
 * offers; `readOnly` reduces the actions to viewing regardless of the list.
 * `helptexts` maps the field keys of the section's editor - the keys of its
 * `validators` map, aliases included - to a label reference or text.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class DocumentSection
{
    public const SUPPORTED_ROW_FIELDS = ['from', 'to', 'year', 'title', 'description', 'position'];
    public const SUPPORTED_CONTRACT_ROW_FIELDS = ['from', 'to', 'position'];
    public const SUPPORTED_PROFILE_INFORMATION_ROW_FIELDS = ['from', 'to', 'year', 'title', 'description'];
    public const SUPPORTED_ACTIONS = ['hide', 'view', 'down', 'up', 'delete', 'edit'];

    /**
     * @param list<string> $rowFields
     * @param list<string> $actions
     * @param array<string, string> $helptexts Per field key of the section's editor, the label reference or text
     */
    public function __construct(
        public readonly string $identifier,
        public readonly string $label,
        public readonly string $type,
        public readonly string $fieldName,
        public readonly bool $readOnly,
        public readonly ValidationSet $validationSet,
        public readonly int $position,
        public readonly array $rowFields = [],
        public readonly array $actions = [],
        public readonly array $helptexts = [],
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     label: string,
     *     type: string,
     *     fieldName: string,
     *     readOnly: bool,
     *     validationSet: ValidationSet,
     *     position: int,
     *     rowFields?: list<string>,
     *     actions?: list<string>,
     *     helptexts?: array<string, string>,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            label: $array['label'],
            type: $array['type'],
            fieldName: $array['fieldName'],
            readOnly: $array['readOnly'],
            validationSet: $array['validationSet'],
            position: $array['position'],
            rowFields: $array['rowFields'] ?? [],
            actions: $array['actions'] ?? [],
            helptexts: $array['helptexts'] ?? [],
        );
    }

    public function isValid(): bool
    {
        return $this->identifier !== ''
            && $this->label !== ''
            && $this->type !== ''
            && $this->fieldName !== '';
    }

    public function isContractSection(): bool
    {
        return $this->identifier === 'contracts'
            || in_array($this->type, ['contract', 'contracts'], true);
    }

    public function allowsAction(string $action): bool
    {
        $normalizedAction = strtolower(trim($action));
        return in_array($normalizedAction, $this->actions, true)
            && (!$this->readOnly || $normalizedAction === 'view');
    }

    /**
     * @return list<string>
     */
    public function getAllowedActions(): array
    {
        return array_values(array_filter(
            $this->actions,
            fn(string $action): bool => $this->allowsAction($action),
        ));
    }

    public function allowsCreate(): bool
    {
        return !$this->readOnly;
    }

    public function allowsDragSorting(): bool
    {
        return $this->allowsAction('up') && $this->allowsAction('down');
    }
}
