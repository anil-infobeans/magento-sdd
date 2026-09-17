<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Api;

use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionResultInterface;

/**
 * The single, atomic business entry point for every balance-changing operation
 * (QCC-DATA-007/008). No other class may write to the balance or ledger tables.
 *
 * Consumers: the create-transaction REST endpoint (redeem only), the Admin Add/Remove
 * Credit controller, and the purchase-posting processor (credit).
 */
interface CreditTransactionManagementInterface
{
    /**
     * Credit (increase) a customer's balance, e.g. for a qualifying purchase.
     *
     * @param int $customerId
     * @param int $amount Positive whole-number credit points.
     * @param string $transactionType One of the TRANSACTION_TYPE_* constants on CreditTransactionInterface.
     * @param string $source One of the SOURCE_* constants on CreditTransactionInterface.
     * @param string|null $message
     * @param string|null $createdBy
     * @param string|null $sourceReference Required and enforced unique for PURCHASE (QCC-PURCHASE-011).
     * @return \ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\InvalidAmountException
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\CustomerNotFoundException
     */
    public function credit(
        int $customerId,
        int $amount,
        string $transactionType,
        string $source,
        ?string $message = null,
        ?string $createdBy = null,
        ?string $sourceReference = null
    ): CreditTransactionInterface;

    /**
     * Debit (decrease) a customer's balance, e.g. for a REST redemption or an Admin
     * "Remove Credit" adjustment.
     *
     * @param int $customerId
     * @param int $amount Positive whole-number credit points.
     * @param string $transactionType One of the TRANSACTION_TYPE_* constants on CreditTransactionInterface.
     * @param string $source One of the SOURCE_* constants on CreditTransactionInterface.
     * @param string|null $message Required and non-empty when $transactionType is ADMIN_REMOVE (QCC-LEDGER-004).
     * @param string|null $createdBy
     * @return \ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\InvalidAmountException
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\InsufficientBalanceException
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\CustomerNotFoundException
     */
    public function debit(
        int $customerId,
        int $amount,
        string $transactionType,
        string $source,
        ?string $message = null,
        ?string $createdBy = null
    ): CreditTransactionInterface;

    /**
     * The `POST /V1/quick-consult-credit/transactions` REST entry point (QCC-API-005,
     * QCC-API-017, QCC-API-019). Deliberately untyped/nullable parameters: unlike
     * {@see credit()} and {@see debit()}, this method must distinguish a genuinely
     * missing/non-integer request field (`INVALID_REQUEST`) from a structurally valid
     * but out-of-range one (`INVALID_AMOUNT`/`INVALID_TRANSACTION_TYPE`) — a
     * distinction that is lost if Magento's webapi framework is allowed to coerce or
     * reject the raw JSON value before this method runs.
     *
     * Restricted to integration/admin ACL callers only; accepts
     * `transaction_type = REDEEM` only (QCC-API-017, CLA-016).
     *
     * @param mixed $customerId
     * @param mixed $transactionType
     * @param mixed $amount
     * @param string|null $message
     * @return \ICC\QuickConsultCredit\Api\Data\CreditTransactionResultInterface
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\InvalidRequestException
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\InvalidAmountException
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\InvalidTransactionTypeException
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\InsufficientBalanceException
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\CustomerNotFoundException
     */
    public function createTransaction($customerId = null, $transactionType = null, $amount = null, ?string $message = null): CreditTransactionResultInterface;
}
