<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Integration\Model\Service;

use ICC\QuickConsultCredit\Api\CreditBalanceManagementInterface;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction\CollectionFactory;
use ICC\QuickConsultCredit\Model\Service\CreditPurchaseProcessor;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Covers QCC-PURCHASE-001/009/010/011 and QCC-DATA-005: re-processing the same
 * qualifying order item must not create a second PURCHASE ledger row or double-credit
 * the balance.
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class CreditPurchaseProcessorTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CreditPurchaseProcessor
     */
    private $purchaseProcessor;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->purchaseProcessor = $this->objectManager->create(CreditPurchaseProcessor::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testReprocessingSameOrderItemDoesNotDoubleCredit(): void
    {
        $order = $this->objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');
        $orderItem = current($order->getAllItems());
        $customerId = (int) $order->getCustomerId();
        $qty = (int) $orderItem->getQtyOrdered();

        $this->purchaseProcessor->postPurchase($orderItem, $qty);
        // Re-processing the same order item (e.g. duplicate event delivery) must no-op.
        $this->purchaseProcessor->postPurchase($orderItem, $qty);

        /** @var CreditBalanceManagementInterface $balanceManagement */
        $balanceManagement = $this->objectManager->create(CreditBalanceManagementInterface::class);
        $balance = $balanceManagement->getBalance($customerId);
        self::assertSame($qty, $balance->getBalance());

        $ledgerCollection = $this->objectManager->create(CollectionFactory::class)->create();
        $ledgerCollection->addFieldToFilter('source_reference', (string) $orderItem->getItemId());
        self::assertCount(1, $ledgerCollection);
    }
}
