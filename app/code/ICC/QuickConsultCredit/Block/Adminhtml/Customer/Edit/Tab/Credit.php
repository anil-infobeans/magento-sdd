<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Block\Adminhtml\Customer\Edit\Tab;

use ICC\QuickConsultCredit\Api\CreditBalanceManagementInterface;
use ICC\QuickConsultCredit\Api\CreditLedgerInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Registry;
use Magento\Ui\Component\Layout\Tabs\TabInterface;

/**
 * Quick Consult Credit tab on the Admin customer edit page (QCC-ADMIN-001/002).
 *
 * Displays the current balance, lifetime totals, and full transaction history — with
 * administrator identity and reason for every ADMIN_ADD/ADMIN_REMOVE row
 * (QCC-AUDIT-002/003) — for the customer currently being edited (resolved from the
 * Admin registry, never a client-supplied id independent of the page itself).
 */
class Credit extends Template implements TabInterface
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var CreditBalanceManagementInterface
     */
    private $balanceManagement;

    /**
     * @var CreditLedgerInterface
     */
    private $ledger;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param CreditBalanceManagementInterface $balanceManagement
     * @param CreditLedgerInterface $ledger
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        CreditBalanceManagementInterface $balanceManagement,
        CreditLedgerInterface $ledger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry = $coreRegistry;
        $this->balanceManagement = $balanceManagement;
        $this->ledger = $ledger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->setTemplate('ICC_QuickConsultCredit::customer/edit/tab/credit.phtml');
    }

    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        return (int) $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * QCC-ADMIN-002: current balance.
     *
     * @return int
     */
    public function getBalance(): int
    {
        return $this->balanceManagement->getBalance($this->getCustomerId())->getBalance();
    }

    /**
     * QCC-ADMIN-002: lifetime credited (purchased) total.
     *
     * @return int
     */
    public function getTotalCredited(): int
    {
        return $this->balanceManagement->getBalance($this->getCustomerId())->getTotalCredited();
    }

    /**
     * QCC-ADMIN-002: lifetime debited (redeemed) total.
     *
     * @return int
     */
    public function getTotalDebited(): int
    {
        return $this->balanceManagement->getBalance($this->getCustomerId())->getTotalDebited();
    }

    /**
     * QCC-ADMIN-002: full transaction history (not paginated — this is the Admin view,
     * distinct from the customer-facing paginated dashboard in QCC-CUSTOMER-004).
     *
     * @return CreditTransactionInterface[]
     */
    public function getTransactions(): array
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField(CreditTransactionInterface::CREATED_AT)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder->addSortOrder($sortOrder)->create();

        return $this->ledger->getList($this->getCustomerId(), $searchCriteria)->getItems();
    }

    /**
     * @return string
     */
    public function getSaveUrl(): string
    {
        return $this->getUrl('quickconsultcredit/customer_credit/save', ['customer_id' => $this->getCustomerId()]);
    }

    /**
     * @inheritDoc
     */
    public function getTabLabel()
    {
        return __('Quick Consult Credit');
    }

    /**
     * @inheritDoc
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * @inheritDoc
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canShowTab()
    {
        return (bool) $this->getCustomerId();
    }

    /**
     * @inheritDoc
     */
    public function isHidden()
    {
        return !$this->getCustomerId();
    }
}
