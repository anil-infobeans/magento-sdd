<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Model\Service;

use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Model\CreditBalance;
use ICC\QuickConsultCredit\Model\CreditBalanceFactory;
use ICC\QuickConsultCredit\Model\CreditTransaction;
use ICC\QuickConsultCredit\Model\CreditTransactionFactory;
use ICC\QuickConsultCredit\Model\CreditTransactionResult;
use ICC\QuickConsultCredit\Model\CreditTransactionResultFactory;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditBalance as CreditBalanceResource;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction as CreditTransactionResource;
use ICC\QuickConsultCredit\Model\Service\AuthorizationAuditLogger;
use ICC\QuickConsultCredit\Model\Service\CreditTransactionManagement;
use ICC\QuickConsultCredit\Model\Service\Exception\CustomerNotFoundException;
use ICC\QuickConsultCredit\Model\Service\Exception\InsufficientBalanceException;
use ICC\QuickConsultCredit\Model\Service\Exception\InvalidRequestException;
use ICC\QuickConsultCredit\Model\Service\Exception\InvalidTransactionTypeException;
use ICC\QuickConsultCredit\Model\Service\Validator\TransactionValidator;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Covers the atomic locking-read/validate/ledger-write/balance-update entry point:
 * exactly-one-ledger-row-per-call, rollback-on-failure, and `createTransaction()`
 * validation/precedence rules, with the resource/adapter/validator mocked (distinct
 * from the DB-backed integration test in Test/Integration) (QCC-DATA-001/002/003/006,
 * QCC-API-012/017/019, QCC-NFR-006).
 */
class CreditTransactionManagementTest extends TestCase
{
    /**
     * @var CreditBalanceFactory&MockObject
     */
    private $creditBalanceFactory;

    /**
     * @var CreditBalanceResource&MockObject
     */
    private $balanceResource;

    /**
     * @var CreditTransactionFactory&MockObject
     */
    private $creditTransactionFactory;

    /**
     * @var CreditTransactionResource&MockObject
     */
    private $transactionResource;

    /**
     * @var TransactionValidator&MockObject
     */
    private $validator;

    /**
     * @var CustomerRepositoryInterface&MockObject
     */
    private $customerRepository;

    /**
     * @var UserContextInterface&MockObject
     */
    private $userContext;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * @var AuthorizationAuditLogger&MockObject
     */
    private $auditLogger;

    /**
     * @var CreditTransactionResultFactory&MockObject
     */
    private $resultFactory;

    /**
     * @var AdapterInterface&MockObject
     */
    private $connection;

    /**
     * @var CreditBalance&MockObject
     */
    private $balanceModel;

    /**
     * @var CreditTransaction&MockObject
     */
    private $transactionModel;

    /**
     * @var CreditTransactionManagement
     */
    private $service;

    protected function setUp(): void
    {
        $this->creditBalanceFactory = $this->createMock(CreditBalanceFactory::class);
        $this->balanceResource = $this->createMock(CreditBalanceResource::class);
        $this->creditTransactionFactory = $this->createMock(CreditTransactionFactory::class);
        $this->transactionResource = $this->createMock(CreditTransactionResource::class);
        $this->validator = $this->createMock(TransactionValidator::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->auditLogger = $this->createMock(AuthorizationAuditLogger::class);
        $this->resultFactory = $this->createMock(CreditTransactionResultFactory::class);

        $this->connection = $this->createMock(AdapterInterface::class);
        $this->balanceResource->method('getConnection')->willReturn($this->connection);

        $this->customerRepository->method('getById')->willReturn($this->createMock(CustomerInterface::class));

        $this->balanceModel = $this->createMock(CreditBalance::class);
        $this->balanceModel->method('getId')->willReturn(1);
        $this->balanceModel->method('getBalance')->willReturn(100);
        $this->balanceModel->method('getTotalCredited')->willReturn(0);
        $this->balanceModel->method('getTotalDebited')->willReturn(0);
        $this->creditBalanceFactory->method('create')->willReturn($this->balanceModel);

        $this->transactionModel = $this->createMock(CreditTransaction::class);
        $this->creditTransactionFactory->method('create')->willReturn($this->transactionModel);

        $this->service = new CreditTransactionManagement(
            $this->creditBalanceFactory,
            $this->balanceResource,
            $this->creditTransactionFactory,
            $this->transactionResource,
            $this->validator,
            $this->customerRepository,
            $this->userContext,
            $this->logger,
            $this->auditLogger,
            $this->resultFactory
        );
    }

    public function testCreditWritesExactlyOneLedgerRowAndCommits(): void
    {
        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::once())->method('commit');
        $this->connection->expects(self::never())->method('rollBack');
        $this->transactionResource->expects(self::once())->method('save')->with($this->transactionModel);
        $this->balanceResource->expects(self::once())->method('save')->with($this->balanceModel);

        $result = $this->service->credit(
            42,
            25,
            CreditTransactionInterface::TRANSACTION_TYPE_PURCHASE,
            CreditTransactionInterface::SOURCE_SYSTEM
        );

        self::assertSame($this->transactionModel, $result);
    }

    public function testDebitExceedingBalanceRollsBackAndDoesNotWriteLedgerRow(): void
    {
        $this->validator->method('validateSufficientBalance')
            ->willThrowException(new InsufficientBalanceException(__('Requested amount exceeds available balance.')));

        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::once())->method('rollBack');
        $this->connection->expects(self::never())->method('commit');
        $this->transactionResource->expects(self::never())->method('save');

        $this->expectException(InsufficientBalanceException::class);
        $this->service->debit(
            42,
            250,
            CreditTransactionInterface::TRANSACTION_TYPE_REDEEM,
            CreditTransactionInterface::SOURCE_API
        );
    }

    public function testUnknownCustomerThrowsCustomerNotFoundBeforeAnyTransactionBegins(): void
    {
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->customerRepository->method('getById')->willThrowException(new NoSuchEntityException(__('No such entity.')));

        $service = new CreditTransactionManagement(
            $this->creditBalanceFactory,
            $this->balanceResource,
            $this->creditTransactionFactory,
            $this->transactionResource,
            $this->validator,
            $this->customerRepository,
            $this->userContext,
            $this->logger,
            $this->auditLogger,
            $this->resultFactory
        );

        $this->connection->expects(self::never())->method('beginTransaction');

        $this->expectException(CustomerNotFoundException::class);
        $service->credit(999, 10, CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_ADD, CreditTransactionInterface::SOURCE_ADMIN, 'reason');
    }

    public function testInfrastructureFailureRollsBackAndLogsStructuredFailureWithoutCredentials(): void
    {
        $this->transactionResource->method('save')->willThrowException(new \RuntimeException('DB connection lost'));

        $this->connection->expects(self::once())->method('rollBack');
        $this->connection->expects(self::never())->method('commit');

        $this->logger->expects(self::once())->method('error')->with(
            self::stringContains('Quick Consult Credit transaction failed'),
            self::callback(function (array $context): bool {
                self::assertSame(42, $context['customer_id']);
                self::assertArrayNotHasKey('password', $context);
                self::assertArrayNotHasKey('token', $context);
                return true;
            })
        );

        $this->expectException(\RuntimeException::class);
        $this->service->credit(42, 25, CreditTransactionInterface::TRANSACTION_TYPE_PURCHASE, CreditTransactionInterface::SOURCE_SYSTEM);
    }

    public function testCreateTransactionRejectsMissingRequiredFieldWithInvalidRequest(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->service->createTransaction(null, CreditTransactionInterface::TRANSACTION_TYPE_REDEEM, 10);
    }

    public function testCreateTransactionRejectsNonIntegerAmountWithInvalidRequest(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->service->createTransaction(42, CreditTransactionInterface::TRANSACTION_TYPE_REDEEM, 25.5);
    }

    public function testCreateTransactionRejectsNonRedeemTypeWithInvalidTransactionType(): void
    {
        $this->expectException(InvalidTransactionTypeException::class);
        $this->service->createTransaction(42, CreditTransactionInterface::TRANSACTION_TYPE_PURCHASE, 10);
    }

    public function testCreateTransactionLogsRejectionForBusinessValidationFailure(): void
    {
        $this->auditLogger->expects(self::once())->method('logRejection')
            ->with(InvalidTransactionTypeException::ERROR_CODE, self::anything());

        try {
            $this->service->createTransaction(42, CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_ADD, 10);
        } catch (InvalidTransactionTypeException $e) {
            // expected - assertion is the logRejection call above
        }
    }

    public function testCreateTransactionSuccessReturnsFixedFieldNamesAndLogsResubmissionAccepted(): void
    {
        $this->transactionModel->method('getEntityId')->willReturn(77);
        $this->transactionModel->method('getTransactionType')->willReturn(CreditTransactionInterface::TRANSACTION_TYPE_REDEEM);
        $this->transactionModel->method('getAmount')->willReturn(25);
        $this->transactionModel->method('getBalanceBefore')->willReturn(100);
        $this->transactionModel->method('getBalanceAfter')->willReturn(75);

        $this->userContext->method('getUserType')->willReturn(UserContextInterface::USER_TYPE_INTEGRATION);
        $this->userContext->method('getUserId')->willReturn(3);

        $expectedResult = $this->createMock(CreditTransactionResult::class);
        $this->resultFactory->expects(self::once())->method('create')->with([
            'transactionId' => 77,
            'type' => CreditTransactionInterface::TRANSACTION_TYPE_REDEEM,
            'amount' => 25,
            'previousBalance' => 100,
            'currentBalance' => 75,
        ])->willReturn($expectedResult);

        $this->auditLogger->expects(self::once())->method('logResubmissionAccepted')
            ->with(['customer_id' => 42, 'transaction_id' => 77]);

        $result = $this->service->createTransaction(42, CreditTransactionInterface::TRANSACTION_TYPE_REDEEM, 25);
        self::assertSame($expectedResult, $result);
    }
}
