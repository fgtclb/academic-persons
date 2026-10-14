<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Upgrade\ConfigurationFinding;
use FGTCLB\AcademicBase\Upgrade\ConfigurationFindingKind;
use FGTCLB\AcademicBase\Upgrade\ConfigurationFindingProviderInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Reports to the upgrade check of `academic_base` every column that two fields
 * of one validation set of the persons settings name, as {@see TcaValidationSets}
 * finds them in the compiled TCA. The column takes the backend flags of the field
 * applied last, and the flags of the others are lost, so it is a warning.
 *
 * @internal not part of public API.
 */
final readonly class SharedColumnFindingProvider implements ConfigurationFindingProviderInterface
{
    public function __construct(
        private TcaValidationSets $tcaValidationSets,
    ) {}

    public function findings(): array
    {
        $tca = is_array($GLOBALS['TCA'] ?? null) ? $GLOBALS['TCA'] : [];
        $findings = [];
        foreach ($this->tcaValidationSets->findSharedColumns($tca) as $sharedColumn) {
            $findings[] = new ConfigurationFinding(
                ConfigurationFindingKind::SharedColumn,
                ContextualFeedbackSeverity::WARNING,
                $sharedColumn->getSubject(),
                sprintf(
                    'The fields %s of the persons settings all name the column "%s" of %s. The column takes the '
                    . 'backend flags of "%s", the field applied last, and the flags of %s are lost. Give each '
                    . 'field a column of its own, or remove the field whose flags the column should not get.',
                    $this->quoted($sharedColumn->fields),
                    $sharedColumn->column,
                    $sharedColumn->recordType === ''
                        ? sprintf('the table "%s"', $sharedColumn->table)
                        : sprintf('the record type "%s" of the table "%s"', $sharedColumn->recordType, $sharedColumn->table),
                    $sharedColumn->getDecidingField(),
                    $this->quoted($sharedColumn->getLostFields()),
                ),
            );
        }
        return $findings;
    }

    /**
     * `"a"`, `"a", "b"`, the form the status report of `academic_persons` uses.
     *
     * @param list<string> $values
     */
    private function quoted(array $values): string
    {
        return '"' . implode('", "', $values) . '"';
    }
}
