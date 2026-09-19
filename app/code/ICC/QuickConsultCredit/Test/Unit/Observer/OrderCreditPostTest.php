<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Observer;

use ICC\QuickConsultCredit\Model\Config\ModuleConfig;
use ICC\QuickConsultCredit\Model\Service\CreditPurchaseProcessor;
use ICC\QuickConsultCredit\Observer\OrderCreditPost;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers qualifying-item resolution and that no post occurs for a non-qualifying
 * invoice/order state, with `CreditPurchaseProcessor` mocked (QCC-PROD-005/006/007/008,
 * QCC-NFR-006).
 */
class OrderCreditPostTest extends TestCase
{
    /**
     * @var ModuleConfig&MockObject
     */
    private $config;

    /**
     * @var CreditPurchaseProcessor&MockObject
     */
    private $purchaseProcessor;

    /**
     * @var OrderCreditPost
     */
    private $observerHandler;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ModuleConfig::class);
        $this->purchaseProcessor = $this->createMock(CreditPurchaseProcessor::class);
        $this->observerHandler = new OrderCreditPost($this->config, $this->purchaseProcessor);
    }

    /**
     * @param Invoice|null $invoiceData
     * @return Observer&MockObject
     */
    private function createObserver($invoiceData): Observer
    {
        $event = $this->createMock(Event::class);
        $event->method('getData')->with('invoice')->willReturn($invoiceData);

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        return $observer;
    }

    public function testDisabledModuleDoesNotPost(): void
    {
        $this->config->method('isEnabled')->willReturn(false);
        $this->purchaseProcessor->expects(self::never())->method('postPurchase');

        $this->observerHandler->execute($this->createObserver(null));
    }

    public function testMissingQualifyingSkuDoesNotPost(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getCreditProductSku')->willReturn(null);
        $this->purchaseProcessor->expects(self::never())->method('postPurchase');

        $this->observerHandler->execute($this->createObserver(null));
    }

    public function testNonInvoiceEventDataDoesNotPost(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getCreditProductSku')->willReturn('QCC-CREDIT');
        $this->purchaseProcessor->expects(self::never())->method('postPurchase');

        $this->observerHandler->execute($this->createObserver('not-an-invoice'));
    }

    public function testNonQualifyingSkuItemIsSkipped(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getCreditProductSku')->willReturn('QCC-CREDIT');

        $orderItem = $this->createMock(OrderItem::class);
        $orderItem->method('getSku')->willReturn('OTHER-SKU');

        $invoiceItem = $this->createMock(InvoiceItem::class);
        $invoiceItem->method('getOrderItem')->willReturn($orderItem);
        $invoiceItem->method('getQty')->willReturn(2);

        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getAllItems')->willReturn([$invoiceItem]);

        $this->purchaseProcessor->expects(self::never())->method('postPurchase');

        $this->observerHandler->execute($this->createObserver($invoice));
    }

    public function testItemWithNoOrderItemIsSkipped(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getCreditProductSku')->willReturn('QCC-CREDIT');

        $invoiceItem = $this->createMock(InvoiceItem::class);
        $invoiceItem->method('getOrderItem')->willReturn(null);

        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getAllItems')->willReturn([$invoiceItem]);

        $this->purchaseProcessor->expects(self::never())->method('postPurchase');

        $this->observerHandler->execute($this->createObserver($invoice));
    }

    public function testZeroQuantityQualifyingItemIsSkipped(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getCreditProductSku')->willReturn('QCC-CREDIT');

        $orderItem = $this->createMock(OrderItem::class);
        $orderItem->method('getSku')->willReturn('QCC-CREDIT');

        $invoiceItem = $this->createMock(InvoiceItem::class);
        $invoiceItem->method('getOrderItem')->willReturn($orderItem);
        $invoiceItem->method('getQty')->willReturn(0);

        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getAllItems')->willReturn([$invoiceItem]);

        $this->purchaseProcessor->expects(self::never())->method('postPurchase');

        $this->observerHandler->execute($this->createObserver($invoice));
    }

    public function testQualifyingItemPostsPurchaseWithInvoicedQty(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getCreditProductSku')->willReturn('QCC-CREDIT');

        $orderItem = $this->createMock(OrderItem::class);
        $orderItem->method('getSku')->willReturn('QCC-CREDIT');

        $invoiceItem = $this->createMock(InvoiceItem::class);
        $invoiceItem->method('getOrderItem')->willReturn($orderItem);
        $invoiceItem->method('getQty')->willReturn(3);

        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getAllItems')->willReturn([$invoiceItem]);

        $this->purchaseProcessor->expects(self::once())->method('postPurchase')->with($orderItem, 3);

        $this->observerHandler->execute($this->createObserver($invoice));
    }
}
