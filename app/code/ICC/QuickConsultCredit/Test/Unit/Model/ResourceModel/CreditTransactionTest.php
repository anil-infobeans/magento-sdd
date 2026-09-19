<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Model\ResourceModel;

use ICC\QuickConsultCredit\Model\CreditTransaction;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction as CreditTransactionResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers immutability (no update/delete save path) and `existsBySourceReference()` on
 * the Credit Transaction (ledger entry) resource model, with the DB adapter mocked
 * (QCC-LEDGER-001/008, QCC-PURCHASE-001/009/010/011, QCC-NFR-006).
 */
class CreditTransactionTest extends TestCase
{
    /**
     * @var AdapterInterface&MockObject
     */
    private $connection;

    /**
     * @var CreditTransactionResource
     */
    private $resource;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AdapterInterface::class);
        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $this->connection->method('select')->willReturn($select);

        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($this->connection);

        $context = $this->createMock(Context::class);
        $context->method('getResources')->willReturn($resourceConnection);
        $context->method('getTransactionManager')
            ->willReturn($this->createMock(\Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface::class));
        $context->method('getObjectRelationProcessor')
            ->willReturn($this->createMock(\Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor::class));

        $objectManager = new ObjectManager($this);
        $this->resource = $objectManager->getObject(CreditTransactionResource::class, ['context' => $context]);
    }

    public function testSaveOfAnAlreadyPersistedTransactionIsRejected(): void
    {
        /** @var CreditTransaction&MockObject $model */
        $model = $this->getMockBuilder(CreditTransaction::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $model->method('getId')->willReturn(5);

        $this->expectException(LocalizedException::class);
        $this->resource->save($model);
    }

    public function testDeleteIsAlwaysRejected(): void
    {
        $model = (new ObjectManager($this))->getObject(CreditTransaction::class);

        $this->expectException(LocalizedException::class);
        $this->resource->delete($model);
    }

    public function testExistsBySourceReferenceReturnsTrueWhenRowFound(): void
    {
        $this->connection->method('fetchOne')->willReturn('7');

        self::assertTrue($this->resource->existsBySourceReference('order_item:7'));
    }

    public function testExistsBySourceReferenceReturnsFalseWhenNoRowFound(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        self::assertFalse($this->resource->existsBySourceReference('order_item:unknown'));
    }
}
