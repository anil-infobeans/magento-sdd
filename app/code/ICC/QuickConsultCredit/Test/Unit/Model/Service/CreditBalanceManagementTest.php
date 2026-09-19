<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Model\Service;

use ICC\QuickConsultCredit\Model\CreditBalance;
use ICC\QuickConsultCredit\Model\CreditBalanceFactory;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditBalance as CreditBalanceResource;
use ICC\QuickConsultCredit\Model\Service\AuthorizationAuditLogger;
use ICC\QuickConsultCredit\Model\Service\CreditBalanceManagement;
use ICC\QuickConsultCredit\Model\Service\Exception\CustomerNotFoundException;
use ICC\QuickConsultCredit\Model\Service\Exception\MalformedCustomerIdException;
use ICC\QuickConsultCredit\Model\Service\Exception\UnauthorizedException;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers lazy account creation with `balance = 0` when absent (QCC-ACCOUNT-004),
 * read-only no-side-effect behavior (QCC-API-004), customer-ownership authorization
 * (QCC-API-009), and malformed/unknown customerId handling (QCC-API-018), with the
 * resource/repository mocked (QCC-NFR-006).
 */
class CreditBalanceManagementTest extends TestCase
{
    /**
     * @var CreditBalanceFactory&MockObject
     */
    private $creditBalanceFactory;

    /**
     * @var CreditBalanceResource&MockObject
     */
    private $resource;

    /**
     * @var CustomerRepositoryInterface&MockObject
     */
    private $customerRepository;

    /**
     * @var UserContextInterface&MockObject
     */
    private $userContext;

    /**
     * @var CreditBalanceManagement
     */
    private $service;

    protected function setUp(): void
    {
        $this->creditBalanceFactory = $this->createMock(CreditBalanceFactory::class);
        $this->resource = $this->createMock(CreditBalanceResource::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);
        $auditLogger = $this->createMock(AuthorizationAuditLogger::class);

        $this->service = new CreditBalanceManagement(
            $this->creditBalanceFactory,
            $this->resource,
            $this->customerRepository,
            $this->userContext,
            $auditLogger
        );
    }

    public function testGetBalanceRejectsMalformedCustomerId(): void
    {
        $this->expectException(MalformedCustomerIdException::class);
        $this->service->getBalance(0);
    }

    public function testGetBalanceRejectsNegativeCustomerId(): void
    {
        $this->expectException(MalformedCustomerIdException::class);
        $this->service->getBalance(-5);
    }

    public function testGetBalanceThrowsCustomerNotFoundForUnknownCustomer(): void
    {
        $this->userContext->method('getUserType')->willReturn(UserContextInterface::USER_TYPE_ADMIN);
        $this->customerRepository->method('getById')->willThrowException(
            new NoSuchEntityException(__('No such entity.'))
        );

        $this->expectException(CustomerNotFoundException::class);
        $this->service->getBalance(999);
    }

    public function testCustomerCallerCannotAccessAnotherCustomersBalance(): void
    {
        $this->userContext->method('getUserType')->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->userContext->method('getUserId')->willReturn(5);

        $this->expectException(UnauthorizedException::class);
        $this->service->getBalance(42);
    }

    public function testCustomerCallerCanAccessOwnBalance(): void
    {
        $this->userContext->method('getUserType')->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->userContext->method('getUserId')->willReturn(42);
        $this->customerRepository->method('getById')->willReturn($this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class));

        $balanceModel = $this->createMock(CreditBalance::class);
        $balanceModel->method('getId')->willReturn(null);
        $this->creditBalanceFactory->method('create')->willReturn($balanceModel);

        $balanceModel->expects(self::once())->method('setCustomerId')->with(42);
        $balanceModel->expects(self::once())->method('setBalance')->with(0);
        $balanceModel->expects(self::once())->method('setTotalCredited')->with(0);
        $balanceModel->expects(self::once())->method('setTotalDebited')->with(0);

        $result = $this->service->getBalance(42);
        self::assertSame($balanceModel, $result);
    }

    public function testGetBalanceLazilyCreatesZeroBalanceWhenNoAccountExists(): void
    {
        $this->userContext->method('getUserType')->willReturn(UserContextInterface::USER_TYPE_ADMIN);
        $this->customerRepository->method('getById')->willReturn($this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class));

        $balanceModel = $this->createMock(CreditBalance::class);
        $balanceModel->method('getId')->willReturn(null);
        $this->creditBalanceFactory->method('create')->willReturn($balanceModel);

        $balanceModel->expects(self::once())->method('setBalance')->with(0);

        $this->service->getBalance(7);
    }

    public function testGetBalanceReturnsExistingAccountUnmodified(): void
    {
        $this->userContext->method('getUserType')->willReturn(UserContextInterface::USER_TYPE_ADMIN);
        $this->customerRepository->method('getById')->willReturn($this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class));

        $balanceModel = $this->createMock(CreditBalance::class);
        $balanceModel->method('getId')->willReturn(1);
        $this->creditBalanceFactory->method('create')->willReturn($balanceModel);

        $balanceModel->expects(self::never())->method('setBalance');

        $result = $this->service->getBalance(7);
        self::assertSame($balanceModel, $result);
    }
}
