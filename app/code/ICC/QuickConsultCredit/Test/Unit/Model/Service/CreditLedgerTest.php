<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Model\Service;

use ICC\QuickConsultCredit\Api\Data\CreditTransactionSearchResultsInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionSearchResultsInterfaceFactory;
use ICC\QuickConsultCredit\Model\CreditTransaction;
use ICC\QuickConsultCredit\Model\CreditTransactionFactory;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction as CreditTransactionResource;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction\Collection;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction\CollectionFactory;
use ICC\QuickConsultCredit\Model\Service\CreditLedger;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers forced per-customer scoping (never disclosing another customer's ledger
 * entries) and pagination wiring, with the collection mocked (distinct from the
 * DB-backed integration test) (QCC-LEDGER-009, QCC-SEC-004, QCC-NFR-006).
 */
class CreditLedgerTest extends TestCase
{
    /**
     * @var CollectionFactory&MockObject
     */
    private $collectionFactory;

    /**
     * @var CreditTransactionSearchResultsInterfaceFactory&MockObject
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface&MockObject
     */
    private $collectionProcessor;

    /**
     * @var CreditTransactionFactory&MockObject
     */
    private $creditTransactionFactory;

    /**
     * @var CreditTransactionResource&MockObject
     */
    private $transactionResource;

    /**
     * @var CreditLedger
     */
    private $ledger;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->searchResultsFactory = $this->createMock(CreditTransactionSearchResultsInterfaceFactory::class);
        $this->collectionProcessor = $this->createMock(CollectionProcessorInterface::class);
        $this->creditTransactionFactory = $this->createMock(CreditTransactionFactory::class);
        $this->transactionResource = $this->createMock(CreditTransactionResource::class);

        $this->ledger = new CreditLedger(
            $this->collectionFactory,
            $this->searchResultsFactory,
            $this->collectionProcessor,
            $this->creditTransactionFactory,
            $this->transactionResource
        );
    }

    public function testGetListForcesCustomerScopeBeforeApplyingSearchCriteria(): void
    {
        $collection = $this->createMock(Collection::class);
        $this->collectionFactory->method('create')->willReturn($collection);

        $collection->expects(self::once())->method('addFieldToFilter')->with('customer_id', 42);
        $this->collectionProcessor->expects(self::once())->method('process');

        $collection->method('getItems')->willReturn([]);
        $collection->method('getSize')->willReturn(0);

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $searchResults = $this->createMock(CreditTransactionSearchResultsInterface::class);
        $this->searchResultsFactory->method('create')->willReturn($searchResults);

        $searchResults->expects(self::once())->method('setSearchCriteria')->with($searchCriteria);
        $searchResults->expects(self::once())->method('setItems')->with([]);
        $searchResults->expects(self::once())->method('setTotalCount')->with(0);

        $result = $this->ledger->getList(42, $searchCriteria);
        self::assertSame($searchResults, $result);
    }

    public function testGetByIdReturnsOwnTransaction(): void
    {
        $model = $this->createMock(CreditTransaction::class);
        $model->method('getId')->willReturn(5);
        $model->method('getCustomerId')->willReturn(42);
        $this->creditTransactionFactory->method('create')->willReturn($model);

        $result = $this->ledger->getById(42, 5);
        self::assertSame($model, $result);
    }

    public function testGetByIdThrowsNoSuchEntityForAnotherCustomersTransaction(): void
    {
        $model = $this->createMock(CreditTransaction::class);
        $model->method('getId')->willReturn(5);
        $model->method('getCustomerId')->willReturn(999);
        $this->creditTransactionFactory->method('create')->willReturn($model);

        $this->expectException(NoSuchEntityException::class);
        $this->ledger->getById(42, 5);
    }

    public function testGetByIdThrowsNoSuchEntityWhenTransactionDoesNotExist(): void
    {
        $model = $this->createMock(CreditTransaction::class);
        $model->method('getId')->willReturn(null);
        $this->creditTransactionFactory->method('create')->willReturn($model);

        $this->expectException(NoSuchEntityException::class);
        $this->ledger->getById(42, 999);
    }
}
