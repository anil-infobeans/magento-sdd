<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Api\Data;

/**
 * The fixed REST response shape for `POST /V1/quick-consult-credit/transactions`
 * (QCC-API-010, CLA resolved: exactly these field names, no alternate naming
 * permitted). Deliberately distinct from {@see CreditTransactionInterface} (the
 * internal ledger-entry data contract, whose field names — `entity_id`,
 * `transaction_type`, `balance_before`, `balance_after` — differ from this REST
 * contract's `transaction_id`, `type`, `previous_balance`, `current_balance`).
 */
interface CreditTransactionResultInterface
{
    /**
     * Get the unique identifier of the ledger transaction created by this request.
     *
     * @return int
     */
    public function getTransactionId(): int;

    /**
     * Get the transaction type (e.g. PURCHASE, REDEEM).
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get the amount, in whole-number credit points, that was applied.
     *
     * @return int
     */
    public function getAmount(): int;

    /**
     * Get the customer's balance immediately before this transaction was applied.
     *
     * @return int
     */
    public function getPreviousBalance(): int;

    /**
     * Get the customer's balance immediately after this transaction was applied.
     *
     * @return int
     */
    public function getCurrentBalance(): int;
}
