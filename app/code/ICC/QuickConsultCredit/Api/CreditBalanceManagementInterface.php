<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Api;

use ICC\QuickConsultCredit\Api\Data\CreditBalanceInterface;

/**
 * The sole entry point for reading (and lazily creating) a customer's credit balance.
 *
 * Every consumer — the REST balance endpoint, the customer My Account dashboard, and
 * the Admin customer-edit tab — reads exclusively through this interface (QCC-DATA-007/008).
 */
interface CreditBalanceManagementInterface
{
    /**
     * Get the current balance for a customer, lazily creating the account with balance 0
     * if absent (QCC-ACCOUNT-004). Read-only; no side effects other than the lazy create
     * (QCC-API-004).
     *
     * When invoked through the REST balance endpoint, this method also enforces:
     * - QCC-API-009: a customer-authenticated caller may only resolve their own identity,
     *   regardless of the supplied $customerId.
     * - QCC-API-018: a malformed/zero/negative $customerId is rejected before any
     *   customer-existence lookup occurs.
     *
     * @param int $customerId
     * @return \ICC\QuickConsultCredit\Api\Data\CreditBalanceInterface
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\MalformedCustomerIdException
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\UnauthorizedException
     * @throws \ICC\QuickConsultCredit\Model\Service\Exception\CustomerNotFoundException
     */
    public function getBalance(int $customerId): CreditBalanceInterface;
}
