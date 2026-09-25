<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The demand arguments of a navigation link of the profile list: the visitor's choices the
 * list is shown with, changed only by what the link is responsible for.
 *
 * ```html
 * <html xmlns:persons="http://typo3.org/ns/FGTCLB/AcademicPersons/ViewHelpers" data-namespace-typo3-fluid="true">
 *
 * <f:link.action arguments="{demand: '{persons:listArguments(arguments: activeListArguments, overrides: {currentPage: page})}'}">...</f:link.action>
 * <f:link.action arguments="{demand: '{persons:listArguments(arguments: activeListArguments, overrides: {alphabetFilter: letter}, remove: \'currentPage\')}'}">...</f:link.action>
 * ```
 *
 * `arguments` is the `activeListArguments` the list action assigns. `overrides` replaces
 * or adds values, `remove` drops the comma separated keys it names - a letter link drops
 * the page, so a new letter starts on the first page. A key that is overridden and
 * removed is removed. A template handing in no `arguments` gets the overrides alone,
 * which is what the links carried before.
 */
final class ListArgumentsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        // Neither is required, and both default to empty: Fluid 5 rejects an argument that
        // is required and has a default, and a list template of a project that does not
        // pass `activeListArguments` on has to keep rendering its links.
        $this->registerArgument('arguments', 'array', 'The active list arguments, "activeListArguments".', false, []);
        $this->registerArgument('overrides', 'array', 'The values the link sets.', false, []);
        $this->registerArgument('remove', 'string', 'Comma separated keys the link drops.', false, '');
    }

    /**
     * @return array<array-key, mixed>
     */
    public function render(): array
    {
        $arguments = is_array($this->arguments['arguments'] ?? null) ? $this->arguments['arguments'] : [];
        $overrides = is_array($this->arguments['overrides'] ?? null) ? $this->arguments['overrides'] : [];
        $remove = GeneralUtility::trimExplode(',', (string)($this->arguments['remove'] ?? ''), true);

        return array_diff_key(array_replace($arguments, $overrides), array_flip($remove));
    }
}
