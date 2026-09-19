<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Block\Account;

use ICC\QuickConsultCredit\Api\CreditBalanceManagementInterface;
use ICC\QuickConsultCredit\Api\CreditLedgerInterface;
use ICC\QuickConsultCredit\Api\Data\CreditBalanceInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionSearchResultsInterface;
use ICC\QuickConsultCredit\Block\Account\Credit;
use ICC\QuickConsultCredit\Model\Config\ModuleConfig;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers session-scoped balance/history retrieval via mocked
 * `CreditBalanceManagementInterface`/`CreditLedgerInterface`, denial of cross-customer
 * reference (identity is read exclusively from the session), and the configured page
 * size being applied (QCC-CUSTOMER-005/006, QCC-NFR-006).
 */
class CreditTest extends TestCase
{
    /**
     * @var CustomerSession&MockObject
     */
    private $customerSession;

    /**
     * @var CreditBalanceManagementInterface&MockObject
     */
    private $balanceManagement;

    /**
     * @var CreditLedgerInterface&MockObject
     */
    private $ledger;

    /**
     * @var ModuleConfig&MockObject
     */
    private $config;

    /**
     * @var SearchCriteriaBuilder&MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var RequestInterface&MockObject
     */
    private $request;

    /**
     * @var Credit
     */
    private $block;

    protected function setUp(): void
    {
        $this->customerSession = $this->createMock(CustomerSession::class);
        $this->balanceManagement = $this->createMock(CreditBalanceManagementInterface::class);
        $this->ledger = $this->createMock(CreditLedgerInterface::class);
        $this->config = $this->createMock(ModuleConfig::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaBuilder->method('setCurrentPage')->willReturnSelf();
        $this->searchCriteriaBuilder->method('setPageSize')->willReturnSelf();
        $this->searchCriteriaBuilder->method('addSortOrder')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteriaInterface::class));

        $sortOrderBuilder = $this->createMock(SortOrderBuilder::class);
        $sortOrderBuilder->method('setField')->willReturnSelf();
        $sortOrderBuilder->method('setDirection')->willReturnSelf();
        $sortOrderBuilder->method('create')->willReturn($this->createMock(\Magento\Framework\Api\SortOrder::class));

        $this->request = $this->createMock(RequestInterface::class);
        $urlBuilder = $this->createMock(UrlInterface::class);

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getUrlBuilder')->willReturn($urlBuilder);

        $this->block = new Credit(
            $context,
            $this->customerSession,
            $this->balanceManagement,
            $this->ledger,
            $this->config,
            $this->searchCriteriaBuilder,
            $sortOrderBuilder
        );
    }

    public function testGetBalanceReadsSessionScopedCustomerBalance(): void
    {
        $this->customerSession->method('getCustomerId')->willReturn(42);

        $balance = $this->createMock(CreditBalanceInterface::class);
        $balance->method('getBalance')->willReturn(100);

        $this->balanceManagement->expects(self::once())->method('getBalance')->with(42)->willReturn($balance);

        self::assertSame(100, $this->block->getBalance());
    }

    public function testGetTransactionsScopesLedgerQueryToSessionCustomerOnly(): void
    {
        $this->customerSession->method('getCustomerId')->willReturn(42);
        $this->request->method('getParam')->with('p', 1)->willReturn(1);
        $this->config->method('getHistoryPageSize')->willReturn(20);

        $searchResults = $this->createMock(CreditTransactionSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([]);
        $searchResults->method('getTotalCount')->willReturn(0);

        $this->ledger->expects(self::once())->method('getList')->with(42, self::anything())->willReturn($searchResults);

        self::assertSame([], $this->block->getTransactions());
    }

    public function testGetPageSizeAppliesConfiguredHistoryPageSize(): void
    {
        $this->config->method('getHistoryPageSize')->willReturn(50);

        self::assertSame(50, $this->block->getPageSize());
    }

    public function testGetCurrentPageDefaultsToOne(): void
    {
        $this->request->method('getParam')->with('p', 1)->willReturn(1);

        self::assertSame(1, $this->block->getCurrentPage());
    }

    public function testGetLastPageNumberComputesFromTotalCountAndPageSize(): void
    {
        $this->customerSession->method('getCustomerId')->willReturn(42);
        $this->request->method('getParam')->with('p', 1)->willReturn(1);
        $this->config->method('getHistoryPageSize')->willReturn(20);

        $searchResults = $this->createMock(CreditTransactionSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([]);
        $searchResults->method('getTotalCount')->willReturn(45);

        $this->ledger->method('getList')->willReturn($searchResults);

        self::assertSame(3, $this->block->getLastPageNumber());
    }
}
