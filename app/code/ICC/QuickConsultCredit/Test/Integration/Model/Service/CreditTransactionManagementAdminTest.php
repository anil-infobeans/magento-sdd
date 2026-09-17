<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Integration\Model\Service;

use ICC\QuickConsultCredit\Api\CreditTransactionManagementInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Model\Service\Exception\InsufficientBalanceException;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Covers QCC-ADMIN-003/004/005/006/007 and QCC-AUDIT-002/003: Admin Add/Remove Credit
 * atomicity, mandatory-reason rejection, and administrator identity recording.
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class CreditTransactionManagementAdminTest extends TestCase
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
    public function testAdminAddRecordsAdministratorIdentityAndReason(): void
    {
        $transaction = $this->transactionManagement->credit(
            1,
            20,
            CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_ADD,
            CreditTransactionInterface::SOURCE_ADMIN,
            'Goodwill credit',
            'admin_user'
        );

        self::assertSame(20, $transaction->getBalanceAfter());
        self::assertSame('admin_user', $transaction->getCreatedBy());
        self::assertSame('Goodwill credit', $transaction->getMessage());
        self::assertSame(CreditTransactionInterface::SOURCE_ADMIN, $transaction->getSource());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testAdminRemoveRecordsAdministratorIdentityAndReason(): void
    {
        $this->transactionManagement->credit(
            1,
            50,
            CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_ADD,
            CreditTransactionInterface::SOURCE_ADMIN,
            'Initial credit',
            'admin_user'
        );

        $transaction = $this->transactionManagement->debit(
            1,
            30,
            CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_REMOVE,
            CreditTransactionInterface::SOURCE_ADMIN,
            'Correction of duplicate credit',
            'admin_user_2'
        );

        self::assertSame(20, $transaction->getBalanceAfter());
        self::assertSame('admin_user_2', $transaction->getCreatedBy());
        self::assertSame('Correction of duplicate credit', $transaction->getMessage());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testMissingReasonIsRejectedBeforeAnyChange(): void
    {
        try {
            $this->transactionManagement->credit(
                1,
                20,
                CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_ADD,
                CreditTransactionInterface::SOURCE_ADMIN,
                '',
                'admin_user'
            );
            self::fail('Expected a validation exception for a missing reason.');
        } catch (LocalizedException $e) {
            // expected
        }

        $balanceManagement = $this->objectManager->create(
            \ICC\QuickConsultCredit\Api\CreditBalanceManagementInterface::class
        );
        self::assertSame(0, $balanceManagement->getBalance(1)->getBalance());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testInsufficientBalanceRemovalIsRejectedWithNoPartialState(): void
    {
        $this->transactionManagement->credit(
            1,
            10,
            CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_ADD,
            CreditTransactionInterface::SOURCE_ADMIN,
            'Initial credit',
            'admin_user'
        );

        try {
            $this->transactionManagement->debit(
                1,
                200,
                CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_REMOVE,
                CreditTransactionInterface::SOURCE_ADMIN,
                'Attempted over-removal',
                'admin_user'
            );
            self::fail('Expected InsufficientBalanceException.');
        } catch (InsufficientBalanceException $e) {
            // expected
        }

        $balanceManagement = $this->objectManager->create(
            \ICC\QuickConsultCredit\Api\CreditBalanceManagementInterface::class
        );
        self::assertSame(10, $balanceManagement->getBalance(1)->getBalance());
    }
}
