<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Block\Adminhtml\Customer\Edit\Tab;

use ICC\QuickConsultCredit\Api\CreditBalanceManagementInterface;
use ICC\QuickConsultCredit\Api\CreditLedgerInterface;
use ICC\QuickConsultCredit\Api\Data\CreditBalanceInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionSearchResultsInterface;
use ICC\QuickConsultCredit\Block\Adminhtml\Customer\Edit\Tab\Credit;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers balance/lifetime-totals/history rendering with mocked service contracts, and
 * the tab visibility rules for the customer currently being edited (QCC-ADMIN-001/002,
 * QCC-NFR-006).
 */
class CreditTest extends TestCase
{
    /**
     * @var Registry&MockObject
     */
    private $coreRegistry;

    /**
     * @var CreditBalanceManagementInterface&MockObject
     */
    private $balanceManagement;

    /**
     * @var CreditLedgerInterface&MockObject
     */
    private $ledger;

    /**
     * @var Credit
     */
    private $block;

    protected function setUp(): void
    {
        // Magento\Backend\Block\Template's constructor falls back to
        // ObjectManager::getInstance() for its own optional jsonHelper/directoryHelper
        // parameters (not exposed on this module's Credit subclass constructor), so the
        // global instance must be primed for construction to succeed in isolation.
        $globalObjectManager = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $globalObjectManager->method('get')->willReturnCallback(
            fn (string $class) => $this->createMock($class)
        );
        ObjectManager::setInstance($globalObjectManager);

        $this->coreRegistry = $this->createMock(Registry::class);
        $this->balanceManagement = $this->createMock(CreditBalanceManagementInterface::class);
        $this->ledger = $this->createMock(CreditLedgerInterface::class);

        $searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->method('addSortOrder')->willReturnSelf();
        $searchCriteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteriaInterface::class));

        $sortOrderBuilder = $this->createMock(SortOrderBuilder::class);
        $sortOrderBuilder->method('setField')->willReturnSelf();
        $sortOrderBuilder->method('setDirection')->willReturnSelf();
        $sortOrderBuilder->method('create')->willReturn($this->createMock(\Magento\Framework\Api\SortOrder::class));

        $urlBuilder = $this->createMock(\Magento\Framework\UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturn('backend/quickconsultcredit/customer_credit/save/customer_id/42');

        $context = $this->createMock(Context::class);
        $context->method('getUrlBuilder')->willReturn($urlBuilder);

        $this->block = new Credit(
            $context,
            $this->coreRegistry,
            $this->balanceManagement,
            $this->ledger,
            $searchCriteriaBuilder,
            $sortOrderBuilder
        );
    }

    private function withCustomerId(int $customerId): void
    {
        $this->coreRegistry->method('registry')->with(RegistryConstants::CURRENT_CUSTOMER_ID)->willReturn($customerId);
    }

    public function testGetCustomerIdReadsFromAdminRegistry(): void
    {
        $this->withCustomerId(42);
        self::assertSame(42, $this->block->getCustomerId());
    }

    public function testGetBalanceTotalsDelegateToBalanceManagementForRegistryCustomer(): void
    {
        $this->withCustomerId(42);

        $balance = $this->createMock(CreditBalanceInterface::class);
        $balance->method('getBalance')->willReturn(95);
        $balance->method('getTotalCredited')->willReturn(120);
        $balance->method('getTotalDebited')->willReturn(25);

        $this->balanceManagement->method('getBalance')->with(42)->willReturn($balance);

        self::assertSame(95, $this->block->getBalance());
        self::assertSame(120, $this->block->getTotalCredited());
        self::assertSame(25, $this->block->getTotalDebited());
    }

    public function testGetTransactionsReturnsFullUnpaginatedHistoryForRegistryCustomer(): void
    {
        $this->withCustomerId(42);

        $searchResults = $this->createMock(CreditTransactionSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([]);

        $this->ledger->expects(self::once())->method('getList')->with(42, self::anything())->willReturn($searchResults);

        self::assertSame([], $this->block->getTransactions());
    }

    public function testCanShowTabIsFalseWithoutACustomerInRegistry(): void
    {
        $this->withCustomerId(0);
        self::assertFalse($this->block->canShowTab());
    }

    public function testCanShowTabIsTrueForARegisteredCustomer(): void
    {
        $this->withCustomerId(42);
        self::assertTrue($this->block->canShowTab());
    }

    public function testIsHiddenIsInverseOfCanShowTab(): void
    {
        $this->withCustomerId(0);
        self::assertTrue($this->block->isHidden());
    }

    public function testTabLabelAndTitleAreQuickConsultCredit(): void
    {
        self::assertSame('Quick Consult Credit', (string) $this->block->getTabLabel());
        self::assertSame('Quick Consult Credit', (string) $this->block->getTabTitle());
    }
}
