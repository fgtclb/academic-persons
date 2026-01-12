<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractAcademicPersonsTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $extensionConfiguration->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions();
    }

    protected function addCoreExtension(string ...$extensions): void
    {
        foreach ($extensions as $extension) {
            if ($extension !== '' && !in_array($extension, $this->coreExtensionsToLoad)) {
                $this->coreExtensionsToLoad[] = $extension;
            }
        }
    }

    protected function addTestExtension(string ...$extensions): void
    {
        foreach ($extensions as $extension) {
            if ($extension !== '' && !in_array($extension, $this->testExtensionsToLoad)) {
                $this->testExtensionsToLoad[] = $extension;
            }
        }
    }

    /**
     * @param string $tableName
     * @param array<int, array<string, mixed>> $assertions
     * @param array<int, array<string, mixed>> $records
     * @param string[] $assertFields
     */
    protected function assertMatchingArray(string $tableName, array $assertions, array $records, array $assertFields): void
    {
        if ($assertFields === []) {
            throw new \InvalidArgumentException(
                '$assertFields must be a non-empty list of field names (string).',
                1768391053,
            );
        }
        $failMessages = [];
        $originalRecords = $records;
        foreach ($assertions as $index => $assertion) {
            $result = $this->assertInRecords($assertion, array_values($originalRecords));
            if ($result === false) {
                if (empty($originalRecords[$assertion['uid']])) {
                    $failMessages[] = 'Record "' . $tableName . ':' . $assertion['uid'] . '" not found in database';
                    continue;
                }
                $recordIdentifier = $tableName . ':' . $assertion['uid'];
                $additionalInformation = $this->renderRecords($assertion, $originalRecords[$assertion['uid']]);
                $failMessages[] = 'Assertion in data-set failed for "' . $recordIdentifier . '":' . LF . $additionalInformation;
                unset($records[$assertion['uid']]);
            } else {
                if ($result !== $index) {
                    $failMessages[] = sprintf(
                        'Asserted record "%s:%s" should be on index %s, but is on %s. Wrong sorting.',
                        $tableName,
                        $assertion['uid'],
                        $index,
                        $result,
                    );
                }
                // Unset asserted record
                unset($records[$assertion['uid']]);
                // Increase assertion counter
                $this->assertTrue($result !== false);
            }
        }
        if (!empty($records)) {
            foreach ($records as $record) {
                $emptyAssertion = array_fill_keys($assertFields, '[none]');
                $reducedRecord = array_intersect_key($record, $emptyAssertion);
                $recordIdentifier = $tableName . ':' . $record['uid'];
                $additionalInformation = $this->renderRecords($emptyAssertion, $reducedRecord);
                $failMessages[] = 'Not asserted record found for "' . $recordIdentifier . '":' . LF . $additionalInformation;
            }
        }

        if (!empty($failMessages)) {
            $this->fail(implode(LF, $failMessages));
        }
    }
}
