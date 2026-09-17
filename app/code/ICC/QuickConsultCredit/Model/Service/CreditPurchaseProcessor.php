<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\Service;

use ICC\QuickConsultCredit\Api\CreditTransactionManagementInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction as CreditTransactionResource;
use Magento\Sales\Model\Order\Item as OrderItem;
use Psr\Log\LoggerInterface;

/**
 * Converts one qualifying order item into exactly one PURCHASE credit transaction,
 * exactly once (QCC-PURCHASE-001/009/010/011).
 */
class CreditPurchaseProcessor
{
    /**
     * @var CreditTransactionManagementInterface
     */
    private $transactionManagement;

    /**
     * @var CreditTransactionResource
     */
    private $transactionResource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CreditTransactionManagementInterface $transactionManagement
     * @param CreditTransactionResource $transactionResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        CreditTransactionManagementInterface $transactionManagement,
        CreditTransactionResource $transactionResource,
        LoggerInterface $logger
    ) {
        $this->transactionManagement = $transactionManagement;
        $this->transactionResource = $transactionResource;
        $this->logger = $logger;
    }

    /**
     * Post credit for one qualifying order item, deriving the amount from the given
     * quantity (QCC-PURCHASE-002) and using the order item ID as the deterministic,
     * unique `source_reference` (QCC-PURCHASE-011). No-ops if already posted
     * (QCC-PURCHASE-001/009/010).
     *
     * @param OrderItem $orderItem
     * @param int $qty
     * @return void
     */
    public function postPurchase(OrderItem $orderItem, int $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $customerId = $orderItem->getOrder() !== null ? $orderItem->getOrder()->getCustomerId() : null;
        if (!$customerId) {
            // Guest checkout is prevented for this product (QCC-PROD-004); defensive no-op.
            return;
        }

        $sourceReference = (string) $orderItem->getItemId();

        if ($this->transactionResource->existsBySourceReference($sourceReference)) {
            // QCC-PURCHASE-001/009/010: already posted for this order item.
            return;
        }

        try {
            $this->transactionManagement->credit(
                (int) $customerId,
                $qty,
                CreditTransactionInterface::TRANSACTION_TYPE_PURCHASE,
                CreditTransactionInterface::SOURCE_SYSTEM,
                null,
                null,
                $sourceReference
            );
        } catch (\Throwable $e) {
            // QCC-AUDIT-005/QCC-NFR-003: structured failure logging, never including credentials.
            $this->logger->error('Quick Consult Credit purchase posting failed', [
                'customer_id' => $customerId,
                'order_item_id' => $orderItem->getItemId(),
                'qty' => $qty,
                'source_reference' => $sourceReference,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
