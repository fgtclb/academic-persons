<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer;

use FGTCLB\AcademicPersons\Service\RecordSynchronizerInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

final class SynchronizerContext
{
    /**
     * @param array<int, SiteLanguage> $allowedSiteLanguages
     */
    public function __construct(
        public readonly RecordSynchronizerInterface $recordSyncronizer,
        public readonly Site $site,
        public readonly SiteLanguage $defaultLanguage,
        public readonly array $allowedSiteLanguages,
        public readonly string $tableName,
        public readonly int $uid,
    ) {}

    /**
     * @param RecordSynchronizerInterface $recordSyncronizer
     * @param Site $site
     * @param array<int, string|int> $allowedLanguageIds
     * @param string $tableName
     * @param int $uid
     * @return self
     */
    public static function create(
        RecordSynchronizerInterface $recordSyncronizer,
        Site $site,
        array $allowedLanguageIds,
        string $tableName,
        int $uid,
    ): self {
        $allowedSiteLanguages = [];
        foreach ($allowedLanguageIds as $allowedLanguageId) {
            $allowedLanguageId = (int)$allowedLanguageId;
            if ($allowedLanguageId <= 0) {
                continue;
            }
            try {
                $siteLanguage = $site->getLanguageById($allowedLanguageId);
                $allowedSiteLanguages[$siteLanguage->getLanguageId()] = $siteLanguage;
            } catch (\InvalidArgumentException $e) {
                if ($e->getCode() !== 1522960188) {
                    // Some unexpected exception occorued, rethrow. We only want to handle the
                    // specific TYPO3 exception code indicating that passed language id does
                    // not exist for the passed site configuration.
                    throw $e;
                }
            }
        }
        return new self(
            recordSyncronizer: $recordSyncronizer,
            site: $site,
            defaultLanguage: $site->getDefaultLanguage(),
            allowedSiteLanguages: $allowedSiteLanguages,
            tableName: $tableName,
            uid: $uid,
        );
    }

    public function withRecord(string $tableName, int $uid): self
    {
        return new self(
            recordSyncronizer: $this->recordSyncronizer,
            site: $this->site,
            defaultLanguage: $this->defaultLanguage,
            allowedSiteLanguages: $this->allowedSiteLanguages,
            tableName: $tableName,
            uid: $uid,
        );
    }

    /**
     * @return int[]
     */
    public function getAllowedLanguageIds(): array
    {
        return array_keys($this->allowedSiteLanguages);
    }
}
