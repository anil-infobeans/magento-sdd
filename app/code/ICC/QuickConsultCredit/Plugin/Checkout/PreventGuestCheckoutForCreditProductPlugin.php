<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Plugin\Checkout;

use ICC\QuickConsultCredit\Model\Config\ModuleConfig;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

/**
 * Rejects guest checkout completion when the cart contains the Quick Consult Credit
 * product (QCC-PROD-004). This is the single extension point the storefront checkout
 * (and any direct guest-cart API caller) ultimately calls to place a guest order,
 * making it the least-intrusive place to enforce the restriction (Constitution
 * Principle I) without altering cart/add-to-cart behavior itself.
 */
class PreventGuestCheckoutForCreditProductPlugin
{
    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param ModuleConfig $config
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        ModuleConfig $config,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository
    ) {
        $this->config = $config;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param GuestPaymentInformationManagementInterface $subject
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return void
     * @throws CouldNotSaveException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): void {
        $this->guardAgainstGuestCheckout((string) $cartId);
    }

    /**
     * @param GuestPaymentInformationManagementInterface $subject
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return void
     * @throws CouldNotSaveException
     */
    public function beforeSavePaymentInformation(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): void {
        $this->guardAgainstGuestCheckout((string) $cartId);
    }

    /**
     * @param string $maskedCartId
     * @return void
     * @throws CouldNotSaveException
     */
    private function guardAgainstGuestCheckout(string $maskedCartId): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $sku = $this->config->getCreditProductSku();
        if (!$sku) {
            return;
        }

        try {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
            $quote = $this->cartRepository->get($quoteId);
        } catch (\Exception $e) {
            // Not our concern: let the core flow surface its own cart-not-found error.
            return;
        }

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getSku() === $sku) {
                throw new CouldNotSaveException(
                    __('Please sign in or create an account to purchase %1.', $sku)
                );
            }
        }
    }
}
