<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Paginated search-results contract for credit transaction (ledger) queries.
 *
 * See QCC-LEDGER-009.
 */
interface CreditTransactionSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get transaction list.
     *
     * @return \ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface[]
     */
    public function getItems(): array;

    /**
     * Set transaction list.
     *
     * @param \ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
