<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Observer;

use ICC\QuickConsultCredit\Model\Config\ModuleConfig;
use ICC\QuickConsultCredit\Model\Service\CreditPurchaseProcessor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Invoice;

/**
 * Observes `sales_order_invoice_save_after` — the Magento extension point matching the
 * configured default qualifying condition ("invoice generated / payment captured",
 * QCC-CONFIG-003, CLA-003). Resolves qualifying Quick Consult Credit order items from
 * the invoice and posts credit for each.
 *
 * Does not fire on cart addition, quote creation, a pending (uninvoiced) order, a
 * failed payment, or a cancelled/non-qualifying order (QCC-PROD-005/006/007/008,
 * QCC-PURCHASE-003/004/005/006/007/008) — invoice creation only happens after payment
 * capture for a qualifying, non-cancelled order.
 */
class OrderCreditPost implements ObserverInterface
{
    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var CreditPurchaseProcessor
     */
    private $purchaseProcessor;

    /**
     * @param ModuleConfig $config
     * @param CreditPurchaseProcessor $purchaseProcessor
     */
    public function __construct(ModuleConfig $config, CreditPurchaseProcessor $purchaseProcessor)
    {
        $this->config = $config;
        $this->purchaseProcessor = $purchaseProcessor;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $qualifyingSku = $this->config->getCreditProductSku();
        if (!$qualifyingSku) {
            return;
        }

        /** @var Invoice|null $invoice */
        $invoice = $observer->getEvent()->getData('invoice');
        if (!$invoice instanceof Invoice) {
            return;
        }

        foreach ($invoice->getAllItems() as $invoiceItem) {
            $orderItem = $invoiceItem->getOrderItem();
            if (!$orderItem || $orderItem->getSku() !== $qualifyingSku) {
                // Only the configured Quick Consult Credit product qualifies (QCC-PROD-005).
                continue;
            }

            $qty = (int) $invoiceItem->getQty();
            if ($qty <= 0) {
                continue;
            }

            $this->purchaseProcessor->postPurchase($orderItem, $qty);
        }
    }
}
