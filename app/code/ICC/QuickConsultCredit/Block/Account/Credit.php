<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Block\Account;

use ICC\QuickConsultCredit\Api\CreditBalanceManagementInterface;
use ICC\QuickConsultCredit\Api\CreditLedgerInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionSearchResultsInterface;
use ICC\QuickConsultCredit\Model\Config\ModuleConfig;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Template;

/**
 * Renders the session-scoped customer's own Quick Consult Credit balance and paginated
 * transaction history (QCC-CUSTOMER-002/003/004/005/006). Customer identity is read
 * exclusively from {@see CustomerSession} — never from a request parameter — so this
 * block cannot be made to disclose another customer's data.
 */
class Credit extends Template
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CreditBalanceManagementInterface
     */
    private $balanceManagement;

    /**
     * @var CreditLedgerInterface
     */
    private $ledger;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var CreditTransactionSearchResultsInterface|null
     */
    private $searchResults;

    /**
     * @param Template\Context $context
     * @param CustomerSession $customerSession
     * @param CreditBalanceManagementInterface $balanceManagement
     * @param CreditLedgerInterface $ledger
     * @param ModuleConfig $config
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CustomerSession $customerSession,
        CreditBalanceManagementInterface $balanceManagement,
        CreditLedgerInterface $ledger,
        ModuleConfig $config,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->balanceManagement = $balanceManagement;
        $this->ledger = $ledger;
        $this->config = $config;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * QCC-CUSTOMER-002.
     *
     * @return int
     */
    public function getBalance(): int
    {
        return $this->balanceManagement->getBalance($this->getCustomerId())->getBalance();
    }

    /**
     * QCC-CUSTOMER-003: date, type, amount, resulting balance, and message per entry.
     *
     * @return CreditTransactionInterface[]
     */
    public function getTransactions(): array
    {
        return $this->getSearchResults()->getItems();
    }

    /**
     * QCC-CUSTOMER-004.
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return max(1, (int) $this->getRequest()->getParam('p', 1));
    }

    /**
     * QCC-CONFIG-004/QCC-CUSTOMER-004.
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->config->getHistoryPageSize();
    }

    /**
     * @return int
     */
    public function getLastPageNumber(): int
    {
        $pageSize = $this->getPageSize();
        if ($pageSize <= 0) {
            return 1;
        }

        return max(1, (int) ceil($this->getSearchResults()->getTotalCount() / $pageSize));
    }

    /**
     * @param int $page
     * @return string
     */
    public function getTransactionsPageUrl(int $page): string
    {
        return $this->getUrl('quickconsultcredit/account/transactions', ['p' => $page]);
    }

    /**
     * @return int
     */
    private function getCustomerId(): int
    {
        return (int) $this->customerSession->getCustomerId();
    }

    /**
     * @return CreditTransactionSearchResultsInterface
     */
    private function getSearchResults(): CreditTransactionSearchResultsInterface
    {
        if ($this->searchResults === null) {
            // QCC-LEDGER-009: chronologically ordered, most recent first.
            $sortOrder = $this->sortOrderBuilder
                ->setField(CreditTransactionInterface::CREATED_AT)
                ->setDirection(SortOrder::SORT_DESC)
                ->create();

            $searchCriteria = $this->searchCriteriaBuilder
                ->setCurrentPage($this->getCurrentPage())
                ->setPageSize($this->getPageSize())
                ->addSortOrder($sortOrder)
                ->create();

            $this->searchResults = $this->ledger->getList($this->getCustomerId(), $searchCriteria);
        }

        return $this->searchResults;
    }
}
