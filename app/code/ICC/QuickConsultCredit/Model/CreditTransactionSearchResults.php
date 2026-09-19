<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model;

use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Concrete search-results implementation for paginated ledger queries (QCC-LEDGER-009).
 */
class CreditTransactionSearchResults extends SearchResults implements CreditTransactionSearchResultsInterface
{
    /**
     * @inheritDoc
     *
     * Narrows the parent's untyped return to satisfy this interface's `: array`
     * return-type declaration (the parent {@see SearchResults::getItems()} declares no
     * return type, which PHP would otherwise reject as an incompatible override).
     *
     * @return CreditTransactionInterface[]
     */
    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod
    public function getItems(): array
    {
        return parent::getItems();
    }

    /**
     * @inheritDoc
     *
     * @param CreditTransactionInterface[] $items
     * @return $this
     */
    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod
    public function setItems(array $items)
    {
        return parent::setItems($items);
    }
}
