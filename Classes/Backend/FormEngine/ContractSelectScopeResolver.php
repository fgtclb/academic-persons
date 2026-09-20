<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Backend\FormEngine;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Turns the `itemsProcFunc` parameters of a contract select into the two inputs the
 * repository needs: the pages to restrict to, and the contracts that stay offered
 * whatever the restriction says.
 *
 * The setting is read from `$parameters['TSconfig']`, which FormEngine fills with the
 * content of `TCEFORM.<table>.<field>.itemsProcFunc.` - the key itself is already
 * stripped, on TYPO3 v13 by `AbstractItemProvider::resolveItemProcessorFunction()` and
 * on v14 by `ItemProcessingService::processItems()`. It is `null` when the field has no
 * such page TSconfig at all.
 */
final readonly class ContractSelectScopeResolver
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function resolve(array $parameters): ContractSelectScope
    {
        $tsConfig = $parameters['TSconfig'] ?? null;
        if (!is_array($tsConfig)) {
            $tsConfig = [];
        }

        return new ContractSelectScope(
            $this->resolvePageIds(
                GeneralUtility::intExplode(',', (string)($tsConfig['storagePids'] ?? ''), true),
                MathUtility::forceIntegerInRange((int)($tsConfig['recursive'] ?? 0), 0, 99),
            ),
            $this->referencedUids($parameters),
        );
    }

    /**
     * @param int[] $pageIds
     * @return int[]
     */
    private function resolvePageIds(array $pageIds, int $depth): array
    {
        $pageIds = array_values(array_filter($pageIds, static fn(int $pageId): bool => $pageId > 0));
        if ($pageIds === [] || $depth === 0) {
            return array_values(array_unique($pageIds));
        }

        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $resolved = $pageIds;
        foreach ($pageIds as $pageId) {
            // The enable field check is bypassed on purpose: a storage folder is
            // regularly hidden, and a select that silently skips one would be the
            // defect this setting exists to avoid, one level down.
            foreach ($pageRepository->getDescendantPageIdsRecursive($pageId, $depth, 0, [], true) as $descendant) {
                $resolved[] = (int)$descendant;
            }
        }

        return array_values(array_unique($resolved));
    }

    /**
     * The value of the edited field, read before `TcaSelectItems` processes it - it is
     * the raw database value for a TCA column and the `vDEF` value for a FlexForm
     * element, and a comma separated list in both cases.
     *
     * @param array<string, mixed> $parameters
     * @return int[]
     */
    private function referencedUids(array $parameters): array
    {
        $field = (string)($parameters['field'] ?? '');
        $row = $parameters['row'] ?? [];
        if ($field === '' || !is_array($row) || !array_key_exists($field, $row)) {
            return [];
        }

        $value = $row[$field];
        if (!is_scalar($value)) {
            // `TcaSelectItems` turns the value into an array, but only after it resolved
            // the items - so anything but a scalar here is a caller that did not come
            // through FormEngine, and it is not this class's job to guess its shape.
            return [];
        }

        $uids = GeneralUtility::intExplode(',', (string)$value, true);

        return array_values(array_unique(array_filter($uids, static fn(int $uid): bool => $uid > 0)));
    }
}
