<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\Service;

use ICC\QuickConsultCredit\Api\CreditTransactionManagementInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionResultInterface;
use ICC\QuickConsultCredit\Model\CreditBalance;
use ICC\QuickConsultCredit\Model\CreditBalanceFactory;
use ICC\QuickConsultCredit\Model\CreditTransaction;
use ICC\QuickConsultCredit\Model\CreditTransactionFactory;
use ICC\QuickConsultCredit\Model\CreditTransactionResultFactory;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditBalance as CreditBalanceResource;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction as CreditTransactionResource;
use ICC\QuickConsultCredit\Api\Exception\CreditApiExceptionInterface;
use ICC\QuickConsultCredit\Model\Service\Exception\CustomerNotFoundException;
use ICC\QuickConsultCredit\Model\Service\Exception\InvalidRequestException;
use ICC\QuickConsultCredit\Model\Service\Exception\InvalidTransactionTypeException;
use ICC\QuickConsultCredit\Model\Service\Validator\TransactionValidator;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\DB\Adapter\DuplicateException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 *
 * The single atomic entry point for every balance-changing operation (QCC-DATA-007/008).
 * Every credit/debit begins a DB transaction, performs a locking read of the customer's
 * balance row, validates, writes exactly one ledger row, updates the materialized
 * balance, commits, and fully rolls back on any failure (QCC-DATA-001/002/003/006).
 */
class CreditTransactionManagement implements CreditTransactionManagementInterface
{
    /**
     * @var CreditBalanceFactory
     */
    private $creditBalanceFactory;

    /**
     * @var CreditBalanceResource
     */
    private $balanceResource;

    /**
     * @var CreditTransactionFactory
     */
    private $creditTransactionFactory;

    /**
     * @var CreditTransactionResource
     */
    private $transactionResource;

    /**
     * @var TransactionValidator
     */
    private $validator;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AuthorizationAuditLogger
     */
    private $auditLogger;

    /**
     * @var CreditTransactionResultFactory
     */
    private $resultFactory;

    /**
     * @param CreditBalanceFactory $creditBalanceFactory
     * @param CreditBalanceResource $balanceResource
     * @param CreditTransactionFactory $creditTransactionFactory
     * @param CreditTransactionResource $transactionResource
     * @param TransactionValidator $validator
     * @param CustomerRepositoryInterface $customerRepository
     * @param UserContextInterface $userContext
     * @param LoggerInterface $logger
     * @param AuthorizationAuditLogger $auditLogger
     * @param CreditTransactionResultFactory $resultFactory
     */
    public function __construct(
        CreditBalanceFactory $creditBalanceFactory,
        CreditBalanceResource $balanceResource,
        CreditTransactionFactory $creditTransactionFactory,
        CreditTransactionResource $transactionResource,
        TransactionValidator $validator,
        CustomerRepositoryInterface $customerRepository,
        UserContextInterface $userContext,
        LoggerInterface $logger,
        AuthorizationAuditLogger $auditLogger,
        CreditTransactionResultFactory $resultFactory
    ) {
        $this->creditBalanceFactory = $creditBalanceFactory;
        $this->balanceResource = $balanceResource;
        $this->creditTransactionFactory = $creditTransactionFactory;
        $this->transactionResource = $transactionResource;
        $this->validator = $validator;
        $this->customerRepository = $customerRepository;
        $this->userContext = $userContext;
        $this->logger = $logger;
        $this->auditLogger = $auditLogger;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritDoc
     */
    public function credit(
        int $customerId,
        int $amount,
        string $transactionType,
        string $source,
        ?string $message = null,
        ?string $createdBy = null,
        ?string $sourceReference = null
    ): CreditTransactionInterface {
        $this->validator->validateAmount($amount);
        $this->validator->validateMessage($transactionType, $message);

        try {
            return $this->execute(
                $customerId,
                $amount,
                $transactionType,
                CreditTransactionInterface::DIRECTION_CREDIT,
                $source,
                $message,
                $createdBy,
                $sourceReference
            );
        } catch (DuplicateException $e) {
            // QCC-PURCHASE-001/009/010/011: a race between two concurrent posting attempts
            // for the same source_reference is resolved by the unique constraint; the
            // losing attempt returns the already-posted transaction rather than erroring.
            if ($sourceReference !== null) {
                $existing = $this->findBySourceReference($sourceReference);
                if ($existing !== null) {
                    return $existing;
                }
            }
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function debit(
        int $customerId,
        int $amount,
        string $transactionType,
        string $source,
        ?string $message = null,
        ?string $createdBy = null
    ): CreditTransactionInterface {
        $this->validator->validateAmount($amount);
        $this->validator->validateMessage($transactionType, $message);

        return $this->execute(
            $customerId,
            $amount,
            $transactionType,
            CreditTransactionInterface::DIRECTION_DEBIT,
            $source,
            $message,
            $createdBy,
            null
        );
    }

    /**
     * @inheritDoc
     */
    public function createTransaction(
        $customerId = null,
        $transactionType = null,
        $amount = null,
        ?string $message = null
    ): CreditTransactionResultInterface {
        try {
            $transaction = $this->doCreateTransaction($customerId, $transactionType, $amount, $message);
        } catch (CreditApiExceptionInterface $e) {
            // QCC-AUDIT-007: a distinct rejected-request audit entry, separate from the
            // operational failure log emitted by execute() for infrastructure errors.
            $this->auditLogger->logRejection($e->getErrorCode(), ['customer_id' => $customerId]);
            throw $e;
        }

        // QCC-AUDIT-007: since no idempotency/dedup mechanism exists for this endpoint
        // (QCC-API-013, CLA-004 resolved), every accepted request is logged uniformly,
        // which necessarily includes every accepted resubmission.
        $this->auditLogger->logResubmissionAccepted([
            'customer_id' => $customerId,
            'transaction_id' => $transaction->getEntityId(),
        ]);

        // QCC-API-010: the REST response shape uses fixed field names (transaction_id,
        // type, amount, previous_balance, current_balance) distinct from the internal
        // ledger-entry field names.
        return $this->resultFactory->create([
            'transactionId' => (int) $transaction->getEntityId(),
            'type' => $transaction->getTransactionType(),
            'amount' => $transaction->getAmount(),
            'previousBalance' => $transaction->getBalanceBefore(),
            'currentBalance' => $transaction->getBalanceAfter(),
        ]);
    }

    /**
     * Validate the raw REST request payload and delegate to the REDEEM debit flow.
     *
     * @param mixed $customerId
     * @param mixed $transactionType
     * @param mixed $amount
     * @param string|null $message
     * @return CreditTransactionInterface
     */
    private function doCreateTransaction(
        $customerId,
        $transactionType,
        $amount,
        ?string $message
    ): CreditTransactionInterface {
        // QCC-API-019: missing required field -> INVALID_REQUEST (evaluated before any
        // value-level check, per the fixed precedence order in QCC-API-012).
        if ($customerId === null || $transactionType === null || $amount === null) {
            throw new InvalidRequestException(__('customer_id, transaction_type, and amount are required.'));
        }

        if (!is_int($customerId)) {
            throw new InvalidRequestException(__('customer_id must be an integer.'));
        }

        if (!is_string($transactionType)) {
            throw new InvalidRequestException(__('transaction_type must be a string.'));
        }

        // Non-integer/fractional amount (e.g. 25.5) -> INVALID_REQUEST, distinct from a
        // structurally valid integer <= 0, which is INVALID_AMOUNT (QCC-API-019 AC-2).
        if (!is_int($amount)) {
            throw new InvalidRequestException(__('amount must be a whole integer.'));
        }

        // QCC-API-017/CLA-016: only REDEEM is accepted on this endpoint.
        if ($transactionType !== CreditTransactionInterface::TRANSACTION_TYPE_REDEEM) {
            throw new InvalidTransactionTypeException(
                __('Only the REDEEM transaction type is accepted on this endpoint.')
            );
        }

        [$source, $createdBy] = $this->resolveCallerContext();

        return $this->debit(
            $customerId,
            $amount,
            CreditTransactionInterface::TRANSACTION_TYPE_REDEEM,
            $source,
            $message,
            $createdBy
        );
    }

    /**
     * Resolve the calling identity's `source`/`created_by` ledger attribution.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveCallerContext(): array
    {
        $userId = $this->userContext->getUserId();

        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
            return [CreditTransactionInterface::SOURCE_ADMIN, 'admin:' . $userId];
        }

        return [CreditTransactionInterface::SOURCE_API, 'integration:' . $userId];
    }

    /**
     * Apply a single ledger movement to the customer's balance and persist the transaction record.
     *
     * @param int $customerId
     * @param int $amount
     * @param string $transactionType
     * @param string $direction
     * @param string $source
     * @param string|null $message
     * @param string|null $createdBy
     * @param string|null $sourceReference
     * @return CreditTransactionInterface
     */
    private function execute(
        int $customerId,
        int $amount,
        string $transactionType,
        string $direction,
        string $source,
        ?string $message,
        ?string $createdBy,
        ?string $sourceReference
    ): CreditTransactionInterface {
        try {
            $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            throw new CustomerNotFoundException(__('No such customer.'));
        }

        $connection = $this->balanceResource->getConnection();
        $connection->beginTransaction();

        try {
            /** @var CreditBalance $balanceModel */
            $balanceModel = $this->creditBalanceFactory->create();
            $this->balanceResource->loadByCustomerIdForUpdate($balanceModel, $customerId);

            if (!$balanceModel->getId()) {
                $balanceModel->setCustomerId($customerId);
                $balanceModel->setBalance(0);
                $balanceModel->setTotalCredited(0);
                $balanceModel->setTotalDebited(0);
            }

            $balanceBefore = $balanceModel->getBalance();

            if ($direction === CreditTransactionInterface::DIRECTION_DEBIT) {
                $this->validator->validateSufficientBalance($balanceBefore, $amount);
                $balanceAfter = $balanceBefore - $amount;
                $balanceModel->setTotalDebited($balanceModel->getTotalDebited() + $amount);
            } else {
                $balanceAfter = $balanceBefore + $amount;
                $balanceModel->setTotalCredited($balanceModel->getTotalCredited() + $amount);
            }

            $balanceModel->setBalance($balanceAfter);
            $this->balanceResource->save($balanceModel);

            /** @var CreditTransaction $transactionModel */
            $transactionModel = $this->creditTransactionFactory->create();
            $transactionModel->setCustomerId($customerId);
            $transactionModel->setTransactionType($transactionType);
            $transactionModel->setDirection($direction);
            $transactionModel->setAmount($amount);
            $transactionModel->setBalanceBefore($balanceBefore);
            $transactionModel->setBalanceAfter($balanceAfter);
            $transactionModel->setMessage($message);
            $transactionModel->setSource($source);
            $transactionModel->setCreatedBy($createdBy);
            $transactionModel->setSourceReference($sourceReference);
            $this->transactionResource->save($transactionModel);

            $connection->commit();

            return $transactionModel;
        } catch (\Throwable $e) {
            $connection->rollBack();

            if (!$e instanceof DuplicateException) {
                // QCC-AUDIT-005/QCC-NFR-003: structured failure logging, never including credentials.
                $this->logger->error('Quick Consult Credit transaction failed', [
                    'customer_id' => $customerId,
                    'transaction_type' => $transactionType,
                    'amount' => $amount,
                    'source_reference' => $sourceReference,
                    'exception' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Find the previously posted transaction for a deterministic purchase reference, if any.
     *
     * @param string $sourceReference
     * @return CreditTransactionInterface|null
     */
    private function findBySourceReference(string $sourceReference): ?CreditTransactionInterface
    {
        $connection = $this->transactionResource->getConnection();
        $select = $connection->select()
            ->from($this->transactionResource->getMainTable())
            ->where('source_reference = ?', $sourceReference);
        $data = $connection->fetchRow($select);

        if (!$data) {
            return null;
        }

        /** @var CreditTransaction $transactionModel */
        $transactionModel = $this->creditTransactionFactory->create();
        $transactionModel->setData($data);
        $transactionModel->setOrigData();

        return $transactionModel;
    }
}
