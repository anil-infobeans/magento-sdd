<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Model\Service;

use ICC\QuickConsultCredit\Api\CreditTransactionManagementInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction as CreditTransactionResource;
use ICC\QuickConsultCredit\Model\Service\CreditPurchaseProcessor;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Covers quantity-derived credit amount, deterministic `source_reference` from the
 * order-item ID, and idempotent skip-if-already-posted, with
 * `CreditTransactionManagementInterface` mocked (QCC-PURCHASE-002/009/010/011,
 * QCC-NFR-006).
 */
class CreditPurchaseProcessorTest extends TestCase
{
    /**
     * @var CreditTransactionManagementInterface&MockObject
     */
    private $transactionManagement;

    /**
     * @var CreditTransactionResource&MockObject
     */
    private $transactionResource;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * @var CreditPurchaseProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->transactionManagement = $this->createMock(CreditTransactionManagementInterface::class);
        $this->transactionResource = $this->createMock(CreditTransactionResource::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new CreditPurchaseProcessor(
            $this->transactionManagement,
            $this->transactionResource,
            $this->logger
        );
    }

    private function createOrderItem(?int $customerId, int $itemId): OrderItem
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn($customerId);

        $orderItem = $this->createMock(OrderItem::class);
        $orderItem->method('getOrder')->willReturn($order);
        $orderItem->method('getItemId')->willReturn($itemId);

        return $orderItem;
    }

    public function testZeroOrNegativeQuantityIsNoOp(): void
    {
        $orderItem = $this->createOrderItem(42, 100);
        $this->transactionManagement->expects(self::never())->method('credit');

        $this->processor->postPurchase($orderItem, 0);
        $this->processor->postPurchase($orderItem, -1);
    }

    public function testGuestOrderWithNoCustomerIdIsNoOp(): void
    {
        $orderItem = $this->createOrderItem(null, 100);
        $this->transactionManagement->expects(self::never())->method('credit');

        $this->processor->postPurchase($orderItem, 5);
    }

    public function testAlreadyPostedOrderItemIsSkipped(): void
    {
        $orderItem = $this->createOrderItem(42, 100);
        $this->transactionResource->method('existsBySourceReference')->with('100')->willReturn(true);
        $this->transactionManagement->expects(self::never())->method('credit');

        $this->processor->postPurchase($orderItem, 5);
    }

    public function testValidPurchasePostsQuantityDerivedCreditWithDeterministicReference(): void
    {
        $orderItem = $this->createOrderItem(42, 100);
        $this->transactionResource->method('existsBySourceReference')->willReturn(false);

        $this->transactionManagement->expects(self::once())->method('credit')->with(
            42,
            5,
            CreditTransactionInterface::TRANSACTION_TYPE_PURCHASE,
            CreditTransactionInterface::SOURCE_SYSTEM,
            null,
            null,
            '100'
        );

        $this->processor->postPurchase($orderItem, 5);
    }

    public function testFailurePostingCreditIsLoggedWithoutCredentialsAndRethrown(): void
    {
        $orderItem = $this->createOrderItem(42, 100);
        $this->transactionResource->method('existsBySourceReference')->willReturn(false);
        $this->transactionManagement->method('credit')->willThrowException(new \RuntimeException('DB error'));

        $this->logger->expects(self::once())->method('error')->with(
            self::stringContains('Quick Consult Credit purchase posting failed'),
            self::callback(function (array $context): bool {
                self::assertSame(42, $context['customer_id']);
                self::assertArrayNotHasKey('password', $context);
                return true;
            })
        );

        $this->expectException(\RuntimeException::class);
        $this->processor->postPurchase($orderItem, 5);
    }
}
