<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Api;

use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Read-only, paginated access to a customer's append-only transaction ledger.
 *
 * Never returns another customer's entries (QCC-SEC-004, QCC-LEDGER-009).
 */
interface CreditLedgerInterface
{
    /**
     * Get a paginated, ordered list of ledger entries strictly scoped to $customerId.
     *
     * @param int $customerId
     * @param SearchCriteriaInterface $searchCriteria
     * @return CreditTransactionSearchResultsInterface
     */
    public function getList(
        int $customerId,
        SearchCriteriaInterface $searchCriteria
    ): CreditTransactionSearchResultsInterface;

    /**
     * Get a single ledger entry by its identifier, scoped strictly to $customerId.
     *
     * @param int $customerId
     * @param int $transactionId
     * @return CreditTransactionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $customerId, int $transactionId): CreditTransactionInterface;
}
