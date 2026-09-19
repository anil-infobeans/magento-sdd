<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Api\Data;

/**
 * Credit balance data contract.
 *
 * Represents the current, materialized credit state for exactly one customer.
 * See specs/quick-consult-credit/plan/data-model.md "Entity: Customer Credit Account".
 */
interface CreditBalanceInterface
{
    public const CUSTOMER_ID = 'customer_id';
    public const BALANCE = 'balance';
    public const TOTAL_CREDITED = 'total_credited';
    public const TOTAL_DEBITED = 'total_debited';

    /**
     * Get the customer identity this balance belongs to.
     *
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * Set the customer identity this balance belongs to.
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self;

    /**
     * Get the current available balance, in whole-number credit points.
     *
     * @return int
     */
    public function getBalance(): int;

    /**
     * Set the current available balance, in whole-number credit points.
     *
     * @param int $balance
     * @return $this
     */
    public function setBalance(int $balance): self;

    /**
     * Get the lifetime cumulative CREDIT-direction total (QCC-ACCOUNT-006, QCC-ADMIN-002).
     *
     * @return int
     */
    public function getTotalCredited(): int;

    /**
     * Set the lifetime cumulative CREDIT-direction total (QCC-ACCOUNT-006, QCC-ADMIN-002).
     *
     * @param int $totalCredited
     * @return $this
     */
    public function setTotalCredited(int $totalCredited): self;

    /**
     * Get the lifetime cumulative DEBIT-direction total (QCC-ACCOUNT-007, QCC-ADMIN-002).
     *
     * @return int
     */
    public function getTotalDebited(): int;

    /**
     * Set the lifetime cumulative DEBIT-direction total (QCC-ACCOUNT-007, QCC-ADMIN-002).
     *
     * @param int $totalDebited
     * @return $this
     */
    public function setTotalDebited(int $totalDebited): self;
}
