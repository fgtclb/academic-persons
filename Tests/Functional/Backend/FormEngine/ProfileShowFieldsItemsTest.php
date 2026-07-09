<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use FGTCLB\AcademicPersons\Backend\FormEngine\ProfileShowFieldsItems;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ProfileShowFieldsItemsTest extends AbstractAcademicPersonsTestCase
{
    /** @var array<string, mixed>|null */
    protected ?array $backupTCA = null;

    protected function setUp(): void
    {
        parent::setUp();
        $backupTCA = $GLOBALS['TCA'];
    }

    protected function tearDown(): void
    {
        if ($this->backupTCA !== null) {
            $GLOBALS['TCA'] = $this->backupTCA;
            if (class_exists(TcaSchemaFactory::class)) {
                /** @var TcaSchemaFactory $tcaSchemaFactory */
                $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
                $tcaSchemaFactory->load($GLOBALS['TCA'], true);
            }
        }
        parent::tearDown();
    }

    #[Test]
    public function itemsProcFuncReturnsExpectedDefaultItems(): void
    {
        $this->applyFakeTableTca();
        $site = new Site('acme', 1, []);
        $subject = new ProfileShowFieldsItems();
        $invokeGetDefaultShowFieldItems = new \ReflectionMethod($subject, 'getDefaultShowFieldItems');
        $expectedDefaultItems = $invokeGetDefaultShowFieldItems->invoke($subject);
        $this->assertSame($expectedDefaultItems, $this->callItemsProcFunc(1, $site, 'fake_table', 'fake_field'));
    }

    #[Test]
    public function itemsProcFuncDispatchesEventWithDefaultItems(): void
    {
        $this->applyFakeTableTca();
        $site = new Site('acme', 1, []);
        $subject = new ProfileShowFieldsItems();
        $invokeGetDefaultShowFieldItems = new \ReflectionMethod($subject, 'getDefaultShowFieldItems');

        $countDispatched = 0;
        $countDispatchedForExpectedField = 0;
        $dispatchedItems = [];

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'event-dispatch-checker',
            static function (ModifyTcaSelectFieldItemsEvent $event) use (
                &$countDispatched,
                &$countDispatchedForExpectedField,
                &$dispatchedItems,
            ): void {
                $countDispatched++;
                $tableName = $event->getParameters()['table'];
                $fieldName = $event->getParameters()['field'];
                if ($tableName === 'fake_table' && $fieldName === 'fake_field') {
                    $countDispatchedForExpectedField++;
                    $dispatchedItems = $event->getParameters()['items'];
                }
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(ModifyTcaSelectFieldItemsEvent::class, 'event-dispatch-checker');

        $expectedDefaultItems = $invokeGetDefaultShowFieldItems->invoke($subject);
        $this->callItemsProcFunc(1, $site, 'fake_table', 'fake_field');
        $this->assertSame(1, $countDispatched);
        $this->assertSame(1, $countDispatchedForExpectedField);
        $this->assertSame($expectedDefaultItems, $dispatchedItems);
    }

    #[Test]
    public function addedItemWithEventListenerGetsReturned(): void
    {
        $this->applyFakeTableTca();
        $site = new Site('acme', 1, []);
        $itemsToSet = [
            0 => [
                'label' => 'some label',
                'value' => 123,
            ],
        ];

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'event-dispatch-modification-checker',
            static function (ModifyTcaSelectFieldItemsEvent $event) use ($itemsToSet): void {

                $tableName = $event->getParameters()['table'];
                $fieldName = $event->getParameters()['field'];
                if ($tableName === 'fake_table' && $fieldName === 'fake_field') {
                    $parameters = $event->getParameters();
                    $parameters['items'] = $itemsToSet;
                    $event->setParameters($parameters);
                }
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(ModifyTcaSelectFieldItemsEvent::class, 'event-dispatch-modification-checker');

        $this->assertSame($itemsToSet, $this->callItemsProcFunc(1, $site, 'fake_table', 'fake_field'));
    }

    /**
     * @return array<int, array{
     *     label?: string|null,
     *     value?: mixed,
     *     icon?: string|null,
     *     group?: string|null,
     * }>
     */
    private function callItemsProcFunc(
        int $pageId,
        Site $site,
        string $tableName,
        string $fieldName,
    ): array {
        $itemProcFunc = (string)($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['itemsProcFunc'] ?? '');
        if ($itemProcFunc === '') {
            throw new \RuntimeException(
                sprintf(
                    'No itemProcFunc configured for "%s.%s".',
                    $tableName,
                    $fieldName,
                ),
                1759136962,
            );
        }
        $items = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['items'] ?? [];
        $processorParameters = [
            'items' => &$items,
            'config' => $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'],
            'TSconfig' => BackendUtility::getPagesTSconfig($pageId),
            'table' => $tableName,
            'field' => $fieldName,
            'effectivePid' => $pageId,
            'site' => $site,
        ];
        GeneralUtility::callUserFunction($itemProcFunc, $processorParameters, $this);
        $items = $processorParameters['items'];
        return $items;
    }

    private function applyFakeTableTca(): void
    {
        $GLOBALS['TCA']['fake_table'] = [
            'ctrl' => [
                'title' => 'fake-table',
            ],
            'columns' => [
                'fake_field' => [
                    'exclude' => true,
                    'label' => 'fake-field',
                    'config' => [
                        'type' => 'select',
                        'renderType' => 'selectSingle',
                        'itemsProcFunc' => ProfileShowFieldsItems::class . '->itemsProcFunc',
                        'size' => 1,
                        'maxitems' => 1,
                        'required' => true,
                    ],
                ],
            ],
        ];
        if (class_exists(TcaSchemaFactory::class)) {
            /** @var TcaSchemaFactory $tcaSchemaFactory */
            $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
            $tcaSchemaFactory->load($GLOBALS['TCA'], true);
        }
    }
}
