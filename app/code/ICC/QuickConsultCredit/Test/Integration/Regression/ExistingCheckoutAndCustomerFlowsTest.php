<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Integration\Regression;

use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction\CollectionFactory;
use ICC\QuickConsultCredit\Plugin\Checkout\PreventGuestCheckoutForCreditProductPlugin;
use Magento\Checkout\Model\GuestPaymentInformationManagement;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * QCC-NFR-005: confirms existing checkout and order-processing behavior is unaffected
 * for products not involved with Quick Consult Credit, even while the module is
 * enabled and configured with an (unrelated) qualifying SKU.
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class ExistingCheckoutAndCustomerFlowsTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     * @magentoConfigFixture current_store quick_consult_credit/general/enabled 1
     * @magentoConfigFixture current_store quick_consult_credit/general/product_sku not-the-credit-product
     */
    public function testInvoicingNonQualifyingProductDoesNotPostCredit(): void
    {
        $order = $this->objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');
        $invoice = $order->getInvoiceCollection()->getFirstItem();
        self::assertNotEmpty($invoice->getId(), 'Precondition: the fixture invoice must exist.');

        // Re-dispatch the same event Magento fires after invoice save, with the module
        // enabled but configured for a different SKU than this order's product ('simple').
        $eventManager = $this->objectManager->get(ManagerInterface::class);
        $eventManager->dispatch('sales_order_invoice_save_after', ['invoice' => $invoice]);

        $orderItem = current($order->getAllItems());
        $ledgerCollection = $this->objectManager->create(CollectionFactory::class)->create();
        $ledgerCollection->addFieldToFilter('source_reference', (string) $orderItem->getItemId());
        self::assertCount(0, $ledgerCollection);
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_items_saved.php
     * @magentoConfigFixture current_store quick_consult_credit/general/enabled 1
     * @magentoConfigFixture current_store quick_consult_credit/general/product_sku not-the-credit-product
     */
    public function testGuestCheckoutIsNotBlockedForCartWithoutCreditProduct(): void
    {
        $quote = $this->objectManager->create(Quote::class);
        $this->objectManager->get(QuoteResource::class)->load($quote, 'test_order_item_with_items', 'reserved_order_id');
        self::assertNotEmpty($quote->getId(), 'Precondition: the fixture quote must exist.');

        $quoteIdMask = $this->objectManager->create(QuoteIdMask::class);
        $this->objectManager->get(QuoteIdMaskResource::class)->load($quoteIdMask, $quote->getId(), 'quote_id');
        self::assertNotEmpty($quoteIdMask->getMaskedId(), 'Precondition: the fixture quote must have a masked id.');

        $payment = $this->objectManager->create(QuotePayment::class);
        $payment->setMethod('checkmo');

        $plugin = $this->objectManager->create(PreventGuestCheckoutForCreditProductPlugin::class);
        $subject = $this->objectManager->create(GuestPaymentInformationManagement::class);

        // Must not throw: this quote's only item ('simple_one') is not the configured
        // Quick Consult Credit product, so guest checkout must proceed unimpeded.
        $plugin->beforeSavePaymentInformation($subject, $quoteIdMask->getMaskedId(), 'guest@example.com', $payment);
        $this->addToAssertionCount(1);
    }
}
