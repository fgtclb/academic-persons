<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Backend\FormEngine;

use FGTCLB\AcademicPersons\Profile\ManagedFieldResolver;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Renders the managed fields of a synchronised person record read-only, with
 * a note naming the import identifier, see {@see ManagedFieldResolver}. Every
 * other field, and every record without an import identifier, keeps its TCA.
 *
 * A form lock only: DataHandler still writes these fields, so the
 * synchronisation, an import or a script are not affected. Registered in
 * `ext_localconf.php` right after `TcaColumnsProcessFieldDescriptions`, so the
 * note is appended to a description that is already translated.
 *
 * @internal Registered as form data provider, not part of the public API.
 */
#[Autoconfigure(public: true)]
final readonly class ManagedFieldsReadOnly implements FormDataProviderInterface
{
    private const NOTE_LABEL = 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:managedField.description';

    public function __construct(
        private ManagedFieldResolver $managedFieldResolver,
    ) {}

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function addData(array $result): array
    {
        $tableName = (string)($result['tableName'] ?? '');
        $row = $result['databaseRow'] ?? [];
        if (!is_array($row)) {
            return $result;
        }
        $columns = $this->managedFieldResolver->getManagedColumns($tableName, $row);
        if ($columns === []) {
            return $result;
        }
        $note = sprintf(
            $this->getLanguageService()->sL(self::NOTE_LABEL),
            $this->managedFieldResolver->getImportIdentifier($row),
        );
        foreach ($columns as $column) {
            if (!isset($result['processedTca']['columns'][$column])) {
                // Not part of the record type, or removed by the showitem list.
                continue;
            }
            $result['processedTca']['columns'][$column]['config']['readOnly'] = true;
            $description = trim((string)($result['processedTca']['columns'][$column]['description'] ?? ''));
            $result['processedTca']['columns'][$column]['description'] = $description === ''
                ? $note
                : $description . "\n" . $note;
        }
        return $result;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
