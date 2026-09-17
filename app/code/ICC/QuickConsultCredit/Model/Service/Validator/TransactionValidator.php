<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\Service\Validator;

use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Model\Service\Exception\InsufficientBalanceException;
use ICC\QuickConsultCredit\Model\Service\Exception\InvalidAmountException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Shared validation rules enforced by CreditTransactionManagement for every transaction
 * type, regardless of which layer (REST, Admin, purchase processor) invoked it.
 */
class TransactionValidator
{
    /**
     * Amount must be a positive integer: rejects zero (QCC-REDEEM-004) and negative
     * (QCC-REDEEM-005, QCC-API-006) values.
     *
     * @param int $amount
     * @return void
     * @throws InvalidAmountException
     */
    public function validateAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidAmountException(__('Amount must be a positive whole number of credit points.'));
        }
    }

    /**
     * `message` must be non-empty when $transactionType is ADMIN_ADD/ADMIN_REMOVE
     * (QCC-LEDGER-004, QCC-ADMIN-005).
     *
     * @param string $transactionType
     * @param string|null $message
     * @return void
     * @throws LocalizedException
     */
    public function validateMessage(string $transactionType, ?string $message): void
    {
        $requiresReason = in_array(
            $transactionType,
            [CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_ADD, CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_REMOVE],
            true
        );

        if ($requiresReason && trim((string) $message) === '') {
            throw new LocalizedException(__('A reason is required for this credit adjustment.'));
        }
    }

    /**
     * A debit amount must not exceed the current balance (QCC-DATA-001, QCC-REDEEM-006).
     *
     * @param int $currentBalance
     * @param int $amount
     * @return void
     * @throws InsufficientBalanceException
     */
    public function validateSufficientBalance(int $currentBalance, int $amount): void
    {
        if ($amount > $currentBalance) {
            throw new InsufficientBalanceException(__('Requested amount exceeds available balance.'));
        }
    }
}
