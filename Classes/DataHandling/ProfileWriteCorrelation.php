<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\DataHandling;

use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;

/**
 * Marks a DataHandler run that writes profiles, for the backend announcement of
 * {@see \FGTCLB\AcademicPersons\Hook\DataHandlerHooks}.
 *
 * The mark is set after `DataHandler::start()`, which replaces the correlation id:
 *
 * ```php
 * $dataHandler->start($datamap, []);
 * $dataHandler->setCorrelationId(ProfileWriteCorrelation::Import->create());
 * $dataHandler->process_datamap();
 * ```
 *
 * It is an aspect of the correlation id, the way the redirects extension of the core
 * recognises its own nested runs. The scope stays random per run, as the DataHandler
 * creates it, because the history store derives the correlation id of every row it
 * writes from it.
 */
enum ProfileWriteCorrelation: string
{
    /**
     * A run this extension starts itself - the translation synchronisation and the
     * profile image writes. It is never announced: the code that started it has
     * announced the update already, or is reacting to one.
     */
    case Internal = 'academic-persons-internal';

    /**
     * A run of import code. It is announced like a backend save, once per profile,
     * with the origin {@see \FGTCLB\AcademicPersons\Event\ProfileUpdateOrigin::Import}.
     */
    case Import = 'academic-persons-import';

    public function create(): CorrelationId
    {
        return CorrelationId::forScope(bin2hex(random_bytes(16)))->withAspects($this->value);
    }

    public static function fromCorrelationId(?CorrelationId $correlationId): ?self
    {
        $aspects = $correlationId?->getAspects() ?? [];
        foreach (self::cases() as $case) {
            if (in_array($case->value, $aspects, true)) {
                return $case;
            }
        }
        return null;
    }
}
