<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\Service;

use ICC\QuickConsultCredit\Api\CreditBalanceManagementInterface;
use ICC\QuickConsultCredit\Api\Data\CreditBalanceInterface;
use ICC\QuickConsultCredit\Model\CreditBalance;
use ICC\QuickConsultCredit\Model\CreditBalanceFactory;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditBalance as CreditBalanceResource;
use ICC\QuickConsultCredit\Model\Service\Exception\CustomerNotFoundException;
use ICC\QuickConsultCredit\Model\Service\Exception\MalformedCustomerIdException;
use ICC\QuickConsultCredit\Model\Service\Exception\UnauthorizedException;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @inheritDoc
 *
 * Read-only: never persists a row for a customer with no existing account (QCC-API-004).
 */
class CreditBalanceManagement implements CreditBalanceManagementInterface
{
    /**
     * @var CreditBalanceFactory
     */
    private $creditBalanceFactory;

    /**
     * @var CreditBalanceResource
     */
    private $resource;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var AuthorizationAuditLogger
     */
    private $auditLogger;

    /**
     * @param CreditBalanceFactory $creditBalanceFactory
     * @param CreditBalanceResource $resource
     * @param CustomerRepositoryInterface $customerRepository
     * @param UserContextInterface $userContext
     * @param AuthorizationAuditLogger $auditLogger
     */
    public function __construct(
        CreditBalanceFactory $creditBalanceFactory,
        CreditBalanceResource $resource,
        CustomerRepositoryInterface $customerRepository,
        UserContextInterface $userContext,
        AuthorizationAuditLogger $auditLogger
    ) {
        $this->creditBalanceFactory = $creditBalanceFactory;
        $this->resource = $resource;
        $this->customerRepository = $customerRepository;
        $this->userContext = $userContext;
        $this->auditLogger = $auditLogger;
    }

    /**
     * @inheritDoc
     */
    public function getBalance(int $customerId): CreditBalanceInterface
    {
        // QCC-API-018: malformed/zero/negative/out-of-range customerId -> 401 CUSTOMER_NOT_FOUND
        if ($customerId <= 0 || $customerId > PHP_INT_MAX) {
            throw new MalformedCustomerIdException(__('No such customer.'));
        }

        $resolvedCustomerId = $this->authorizeAccess($customerId);

        try {
            $this->customerRepository->getById($resolvedCustomerId);
        } catch (NoSuchEntityException $e) {
            throw new CustomerNotFoundException(__('No such customer.'));
        }

        /** @var CreditBalance $model */
        $model = $this->creditBalanceFactory->create();
        $this->resource->loadByCustomerId($model, $resolvedCustomerId);

        if (!$model->getId()) {
            // QCC-ACCOUNT-004/QCC-API-004: no persisted account -> report a transient zero balance.
            $model->setCustomerId($resolvedCustomerId);
            $model->setBalance(0);
            $model->setTotalCredited(0);
            $model->setTotalDebited(0);
        }

        return $model;
    }

    /**
     * QCC-API-009: a customer-authenticated caller may only resolve their own identity.
     * Admin/integration callers (already ACL-gated at the webapi layer, or trusted
     * internal callers such as the Admin customer-edit tab) may resolve any customer.
     *
     * @param int $customerId
     * @return int The authoritative customer identity to use for the balance lookup.
     * @throws UnauthorizedException
     */
    private function authorizeAccess(int $customerId): int
    {
        $userType = $this->userContext->getUserType();

        if ($userType !== UserContextInterface::USER_TYPE_CUSTOMER) {
            return $customerId;
        }

        $sessionCustomerId = (int) $this->userContext->getUserId();

        if ($sessionCustomerId !== $customerId) {
            $this->auditLogger->log(
                AuthorizationAuditLogger::CATEGORY_CUSTOMER_OWNERSHIP,
                AuthorizationAuditLogger::DECISION_DENIED
            );
            throw new UnauthorizedException(__('You are not authorized to access this resource.'));
        }

        $this->auditLogger->log(
            AuthorizationAuditLogger::CATEGORY_CUSTOMER_OWNERSHIP,
            AuthorizationAuditLogger::DECISION_GRANTED
        );

        return $sessionCustomerId;
    }
}
