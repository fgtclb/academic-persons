<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Service;

use FGTCLB\AcademicPersons\Service\ProfileImageMetadataService;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * {@see ProfileImageMetadataService} with `EXT:filemetadata` installed, which is the
 * installation ACE-89 is about: that system extension adds `copyright` to
 * `sys_file_metadata`, and an installation requiring file attributes reports an
 * uploaded file without one.
 *
 * {@see ProfileImageMetadataServiceTest} covers the same service without the
 * extension, where the column does not exist at all. A class of its own, because
 * loading a system extension is a decision of `setUp()` and not of one case.
 */
final class ProfileImageMetadataServiceWithFileMetadataTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->coreExtensionsToLoad[] = 'typo3/cms-filemetadata';
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/RecordSynchronizer/Fixtures/ProfileWithRelations.csv');
    }

    #[Test]
    public function theUploadFillsTheCopyrightOfTheFile(): void
    {
        $file = $this->get(ResourceFactory::class)->getFileObject(1);

        $metadata = $this->get(ProfileImageMetadataService::class)->initializeFileMetadata($file, 1);

        $this->assertSame(
            [
                'title' => 'Erika Musterfrau',
                'alternative' => 'Erika Musterfrau',
                'copyright' => 'Erika Musterfrau',
            ],
            $metadata,
        );
        $this->assertSame(
            [
                'title' => 'Erika Musterfrau',
                'alternative' => 'Erika Musterfrau',
                'copyright' => 'Erika Musterfrau',
            ],
            $this->fetchFileMetadata(),
        );
    }

    #[Test]
    public function aCopyrightTheRecordAlreadyCarriesIsKept(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_file_metadata');
        $connection->insert('sys_file_metadata', [
            'uid' => 1,
            'pid' => 0,
            'file' => 1,
            'copyright' => 'Acme University',
        ]);
        $file = $this->get(ResourceFactory::class)->getFileObject(1);

        $metadata = $this->get(ProfileImageMetadataService::class)->initializeFileMetadata($file, 1);

        $this->assertSame(['title' => 'Erika Musterfrau', 'alternative' => 'Erika Musterfrau'], $metadata);
        $this->assertSame(
            [
                'title' => 'Erika Musterfrau',
                'alternative' => 'Erika Musterfrau',
                'copyright' => 'Acme University',
            ],
            $this->fetchFileMetadata(),
        );
    }

    /**
     * The relation row has no `copyright` column - neither in the core nor through
     * this extension - so the name synchronisation writes the two columns it has.
     */
    #[Test]
    public function theReferenceRowCarriesNoCopyright(): void
    {
        $this->assertFalse(
            $this->fileReferenceTableHasColumn('copyright'),
            'EXT:filemetadata adds copyright to sys_file_reference after all - the write has to follow.',
        );

        $metadata = $this->get(ProfileImageMetadataService::class)->updateForProfileUid(1);

        $this->assertSame(['title' => 'Erika Musterfrau', 'alternative' => 'Erika Musterfrau'], $metadata);
    }

    /**
     * @return array<string, string>
     */
    private function fetchFileMetadata(): array
    {
        $row = $this->getConnectionPool()
            ->getConnectionForTable('sys_file_metadata')
            ->select(['title', 'alternative', 'copyright'], 'sys_file_metadata', ['file' => 1])
            ->fetchAssociative();
        return $row === false ? [] : array_map(static fn(mixed $value): string => (string)$value, $row);
    }

    private function fileReferenceTableHasColumn(string $columnName): bool
    {
        $columnNames = array_map(
            static fn(string $name): string => strtolower($name),
            array_keys(
                $this->getConnectionPool()
                    ->getConnectionForTable('sys_file_reference')
                    ->createSchemaManager()
                    ->listTableColumns('sys_file_reference'),
            ),
        );

        return in_array(strtolower($columnName), $columnNames, true);
    }
}
