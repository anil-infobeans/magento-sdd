<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Integration\Model\Service;

use ICC\QuickConsultCredit\Api\CreditLedgerInterface;
use ICC\QuickConsultCredit\Api\CreditTransactionManagementInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Covers QCC-SEC-004: `CreditLedger::getList` never returns another customer's ledger
 * entries, even when the caller's own SearchCriteria applies no customer filter.
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class CreditLedgerTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CreditLedgerInterface
     */
    private $ledger;

    /**
     * @var CreditTransactionManagementInterface
     */
    private $transactionManagement;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->ledger = $this->objectManager->create(CreditLedgerInterface::class);
        $this->transactionManagement = $this->objectManager->create(CreditTransactionManagementInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/two_customers.php
     */
    public function testGetListNeverReturnsAnotherCustomersEntries(): void
    {
        $this->transactionManagement->credit(
            1,
            50,
            CreditTransactionInterface::TRANSACTION_TYPE_PURCHASE,
            CreditTransactionInterface::SOURCE_SYSTEM,
            null,
            null,
            'test-ledger-customer-1'
        );
        $this->transactionManagement->credit(
            2,
            75,
            CreditTransactionInterface::TRANSACTION_TYPE_PURCHASE,
            CreditTransactionInterface::SOURCE_SYSTEM,
            null,
            null,
            'test-ledger-customer-2'
        );

        $searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $searchResults = $this->ledger->getList(1, $searchCriteriaBuilder->create());

        self::assertCount(1, $searchResults->getItems());
        foreach ($searchResults->getItems() as $item) {
            self::assertSame(1, $item->getCustomerId());
        }
    }
}
