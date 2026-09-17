<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Integration\Model\Service;

use ICC\QuickConsultCredit\Api\CreditTransactionManagementInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditBalance as CreditBalanceResource;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction\CollectionFactory;
use ICC\QuickConsultCredit\Model\Service\Exception\InsufficientBalanceException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Covers QCC-DATA-001/002/003/006 and QCC-NFR-002: balance never goes negative, exactly
 * one ledger row is created per successful operation, and any failure fully rolls back
 * both the balance and ledger (no partial state).
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class CreditTransactionManagementTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CreditTransactionManagementInterface
     */
    private $transactionManagement;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->transactionManagement = $this->objectManager->create(CreditTransactionManagementInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testCreditThenDebitProducesExactlyOneLedgerRowEach(): void
    {
        $customerId = (int) $this->getFixtureCustomerId();

        $creditTransaction = $this->transactionManagement->credit(
            $customerId,
            100,
            CreditTransactionInterface::TRANSACTION_TYPE_PURCHASE,
            CreditTransactionInterface::SOURCE_SYSTEM,
            null,
            null,
            'test-order-item-' . $customerId
        );

        self::assertSame(0, $creditTransaction->getBalanceBefore());
        self::assertSame(100, $creditTransaction->getBalanceAfter());

        $debitTransaction = $this->transactionManagement->debit(
            $customerId,
            40,
            CreditTransactionInterface::TRANSACTION_TYPE_REDEEM,
            CreditTransactionInterface::SOURCE_API
        );

        self::assertSame(100, $debitTransaction->getBalanceBefore());
        self::assertSame(60, $debitTransaction->getBalanceAfter());

        $ledgerCollection = $this->objectManager->create(CollectionFactory::class)->create();
        $ledgerCollection->addFieldToFilter('customer_id', $customerId);
        self::assertCount(2, $ledgerCollection);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testDebitExceedingBalanceRollsBackWithNoPartialState(): void
    {
        $customerId = (int) $this->getFixtureCustomerId();

        $this->transactionManagement->credit(
            $customerId,
            10,
            CreditTransactionInterface::TRANSACTION_TYPE_PURCHASE,
            CreditTransactionInterface::SOURCE_SYSTEM,
            null,
            null,
            'test-order-item-insufficient-' . $customerId
        );

        try {
            $this->transactionManagement->debit(
                $customerId,
                25,
                CreditTransactionInterface::TRANSACTION_TYPE_REDEEM,
                CreditTransactionInterface::SOURCE_API
            );
            self::fail('Expected InsufficientBalanceException was not thrown.');
        } catch (InsufficientBalanceException $e) {
            // expected
        }

        /** @var CreditBalanceResource $balanceResource */
        $balanceResource = $this->objectManager->create(CreditBalanceResource::class);
        $connection = $balanceResource->getConnection();
        $balance = (int) $connection->fetchOne(
            $connection->select()
                ->from($balanceResource->getMainTable(), 'balance')
                ->where('customer_id = ?', $customerId)
        );

        // Balance must remain exactly as it was before the rejected debit (QCC-DATA-001/003).
        self::assertSame(10, $balance);

        $ledgerCollection = $this->objectManager->create(CollectionFactory::class)->create();
        $ledgerCollection->addFieldToFilter('customer_id', $customerId);
        // Only the original successful credit — the failed debit created no ledger row.
        self::assertCount(1, $ledgerCollection);
    }

    /**
     * @return int
     */
    private function getFixtureCustomerId(): int
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);
        return (int) $customerRepository->get('customer@example.com')->getId();
    }
}
