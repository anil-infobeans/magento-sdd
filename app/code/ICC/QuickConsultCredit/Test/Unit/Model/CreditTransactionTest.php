<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Model;

use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Model\CreditTransaction;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Covers getter/setter round-tripping for every Credit Transaction (ledger entry)
 * field on the model (QCC-LEDGER-002/003/004/005/006, QCC-NFR-006).
 */
class CreditTransactionTest extends TestCase
{
    /**
     * @var CreditTransaction
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(CreditTransaction::class);
    }

    public function testCustomerIdGetterAndSetter(): void
    {
        $this->model->setCustomerId(42);
        self::assertSame(42, $this->model->getCustomerId());
    }

    public function testTransactionTypeGetterAndSetter(): void
    {
        $this->model->setTransactionType(CreditTransactionInterface::TRANSACTION_TYPE_REDEEM);
        self::assertSame(CreditTransactionInterface::TRANSACTION_TYPE_REDEEM, $this->model->getTransactionType());
    }

    public function testDirectionGetterAndSetter(): void
    {
        $this->model->setDirection(CreditTransactionInterface::DIRECTION_DEBIT);
        self::assertSame(CreditTransactionInterface::DIRECTION_DEBIT, $this->model->getDirection());
    }

    public function testAmountGetterAndSetter(): void
    {
        $this->model->setAmount(25);
        self::assertSame(25, $this->model->getAmount());
    }

    public function testBalanceBeforeAndAfterGetterAndSetter(): void
    {
        $this->model->setBalanceBefore(100);
        $this->model->setBalanceAfter(75);
        self::assertSame(100, $this->model->getBalanceBefore());
        self::assertSame(75, $this->model->getBalanceAfter());
    }

    public function testMessageGetterAndSetterAllowsNull(): void
    {
        self::assertNull($this->model->getMessage());
        $this->model->setMessage('Goodwill credit');
        self::assertSame('Goodwill credit', $this->model->getMessage());
    }

    public function testSourceGetterAndSetter(): void
    {
        $this->model->setSource(CreditTransactionInterface::SOURCE_ADMIN);
        self::assertSame(CreditTransactionInterface::SOURCE_ADMIN, $this->model->getSource());
    }

    public function testCreatedByGetterAndSetterAllowsNull(): void
    {
        self::assertNull($this->model->getCreatedBy());
        $this->model->setCreatedBy('admin:1');
        self::assertSame('admin:1', $this->model->getCreatedBy());
    }

    public function testSourceReferenceGetterAndSetterAllowsNull(): void
    {
        self::assertNull($this->model->getSourceReference());
        $this->model->setSourceReference('order_item:99');
        self::assertSame('order_item:99', $this->model->getSourceReference());
    }

    public function testEntityIdIsNullUntilPersisted(): void
    {
        self::assertNull($this->model->getEntityId());
        $this->model->setData(CreditTransactionInterface::ENTITY_ID, 7);
        self::assertSame(7, $this->model->getEntityId());
    }
}
