<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Plugin\Checkout;

use ICC\QuickConsultCredit\Model\Config\ModuleConfig;
use ICC\QuickConsultCredit\Plugin\Checkout\PreventGuestCheckoutForCreditProductPlugin;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers rejection/forced-authentication when the cart contains the Quick Consult
 * Credit product, and no-op for carts without it (QCC-PROD-004, QCC-NFR-006).
 */
class PreventGuestCheckoutForCreditProductPluginTest extends TestCase
{
    /**
     * @var ModuleConfig&MockObject
     */
    private $config;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface&MockObject
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var CartRepositoryInterface&MockObject
     */
    private $cartRepository;

    /**
     * @var PreventGuestCheckoutForCreditProductPlugin
     */
    private $plugin;

    /**
     * @var GuestPaymentInformationManagementInterface&MockObject
     */
    private $subject;

    /**
     * @var PaymentInterface&MockObject
     */
    private $paymentMethod;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ModuleConfig::class);
        $this->maskedQuoteIdToQuoteId = $this->createMock(MaskedQuoteIdToQuoteIdInterface::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->subject = $this->createMock(GuestPaymentInformationManagementInterface::class);
        $this->paymentMethod = $this->createMock(PaymentInterface::class);

        $this->plugin = new PreventGuestCheckoutForCreditProductPlugin(
            $this->config,
            $this->maskedQuoteIdToQuoteId,
            $this->cartRepository
        );
    }

    private function mockQuoteWithSkus(array $skus): Quote
    {
        $items = array_map(function (string $sku): QuoteItem {
            $item = $this->createMock(QuoteItem::class);
            $item->method('getSku')->willReturn($sku);
            return $item;
        }, $skus);

        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn($items);

        return $quote;
    }

    public function testDisabledModuleAllowsGuestCheckout(): void
    {
        $this->config->method('isEnabled')->willReturn(false);
        $this->cartRepository->expects(self::never())->method('get');

        $this->plugin->beforeSavePaymentInformationAndPlaceOrder(
            $this->subject,
            'masked-id',
            'guest@example.com',
            $this->paymentMethod
        );
        $this->addToAssertionCount(1);
    }

    public function testNoConfiguredSkuAllowsGuestCheckout(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getCreditProductSku')->willReturn(null);
        $this->cartRepository->expects(self::never())->method('get');

        $this->plugin->beforeSavePaymentInformation(
            $this->subject,
            'masked-id',
            'guest@example.com',
            $this->paymentMethod
        );
        $this->addToAssertionCount(1);
    }

    public function testCartWithoutCreditProductAllowsGuestCheckout(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getCreditProductSku')->willReturn('QCC-CREDIT');
        $this->maskedQuoteIdToQuoteId->method('execute')->willReturn(10);
        $this->cartRepository->method('get')->willReturn($this->mockQuoteWithSkus(['OTHER-SKU']));

        $this->plugin->beforeSavePaymentInformationAndPlaceOrder(
            $this->subject,
            'masked-id',
            'guest@example.com',
            $this->paymentMethod
        );
        $this->addToAssertionCount(1);
    }

    public function testCartContainingCreditProductRejectsGuestCheckout(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getCreditProductSku')->willReturn('QCC-CREDIT');
        $this->maskedQuoteIdToQuoteId->method('execute')->willReturn(10);
        $this->cartRepository->method('get')->willReturn($this->mockQuoteWithSkus(['QCC-CREDIT']));

        $this->expectException(CouldNotSaveException::class);
        $this->plugin->beforeSavePaymentInformationAndPlaceOrder(
            $this->subject,
            'masked-id',
            'guest@example.com',
            $this->paymentMethod
        );
    }

    public function testCartContainingCreditProductRejectsOnBothGuardedMethods(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getCreditProductSku')->willReturn('QCC-CREDIT');
        $this->maskedQuoteIdToQuoteId->method('execute')->willReturn(10);
        $this->cartRepository->method('get')->willReturn($this->mockQuoteWithSkus(['QCC-CREDIT']));

        $this->expectException(CouldNotSaveException::class);
        $this->plugin->beforeSavePaymentInformation(
            $this->subject,
            'masked-id',
            'guest@example.com',
            $this->paymentMethod
        );
    }

    public function testCartNotFoundIsNotThisPluginsConcernAndDoesNotThrow(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getCreditProductSku')->willReturn('QCC-CREDIT');
        $this->maskedQuoteIdToQuoteId->method('execute')->willThrowException(new \Exception('no such cart'));

        $this->plugin->beforeSavePaymentInformationAndPlaceOrder(
            $this->subject,
            'masked-id',
            'guest@example.com',
            $this->paymentMethod
        );
        $this->addToAssertionCount(1);
    }
}
