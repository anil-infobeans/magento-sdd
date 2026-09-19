<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Model\ResourceModel;

use ICC\QuickConsultCredit\Model\CreditBalance;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditBalance as CreditBalanceResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers `loadByCustomerId()`/`loadByCustomerIdForUpdate()` on the Customer Credit
 * Account resource model, with the DB adapter mocked (QCC-ACCOUNT-001, QCC-DATA-001/003/006,
 * QCC-NFR-006).
 */
class CreditBalanceTest extends TestCase
{
    /**
     * @var AdapterInterface&MockObject
     */
    private $connection;

    /**
     * @var Select&MockObject
     */
    private $select;

    /**
     * @var CreditBalanceResource
     */
    private $resource;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->select = $this->createMock(Select::class);

        $this->connection->method('select')->willReturn($this->select);
        $this->select->method('from')->willReturnSelf();
        $this->select->method('where')->willReturnSelf();
        $this->select->method('forUpdate')->willReturnSelf();

        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($this->connection);

        $context = $this->createMock(Context::class);
        $context->method('getResources')->willReturn($resourceConnection);
        $context->method('getTransactionManager')
            ->willReturn($this->createMock(\Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface::class));
        $context->method('getObjectRelationProcessor')
            ->willReturn($this->createMock(\Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor::class));

        $objectManager = new ObjectManager($this);
        $this->resource = $objectManager->getObject(CreditBalanceResource::class, ['context' => $context]);
    }

    public function testLoadByCustomerIdPopulatesObjectFromRow(): void
    {
        $row = ['entity_id' => 5, 'customer_id' => 42, 'balance' => 100];
        $this->connection->method('fetchRow')->willReturn($row);

        $model = (new ObjectManager($this))->getObject(CreditBalance::class);
        $this->resource->loadByCustomerId($model, 42);

        self::assertSame(100, (int) $model->getData('balance'));
        self::assertSame(42, (int) $model->getData('customer_id'));
    }

    public function testLoadByCustomerIdLeavesObjectEmptyWhenNoRowFound(): void
    {
        $this->connection->method('fetchRow')->willReturn(false);

        $model = (new ObjectManager($this))->getObject(CreditBalance::class);
        $this->resource->loadByCustomerId($model, 999);

        self::assertNull($model->getData('balance'));
    }

    public function testLoadByCustomerIdForUpdateAppliesPessimisticLock(): void
    {
        $this->select->expects(self::once())->method('forUpdate')->with(true)->willReturnSelf();
        $this->connection->method('fetchRow')->willReturn(['entity_id' => 1, 'customer_id' => 7, 'balance' => 10]);

        $model = (new ObjectManager($this))->getObject(CreditBalance::class);
        $this->resource->loadByCustomerIdForUpdate($model, 7);

        self::assertSame(10, (int) $model->getData('balance'));
    }
}
