<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Controller\Adminhtml\Customer\Credit;

use ICC\QuickConsultCredit\Api\CreditTransactionManagementInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Controller\Adminhtml\Customer\Credit\Save;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Covers mandatory-reason/business-rule rejection surfacing, delegation-only to the
 * service contract (no direct persistence access, QCC-DATA-008), and that only
 * `LocalizedException` messages are ever shown to the admin verbatim — any other
 * exception is logged internally and replaced with a generic message (QCC-SEC-005/006,
 * QCC-ADMIN-005/008/009, QCC-NFR-006).
 */
class SaveTest extends TestCase
{
    /**
     * @var CreditTransactionManagementInterface&MockObject
     */
    private $transactionManagement;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * @var RequestInterface&MockObject
     */
    private $request;

    /**
     * @var ManagerInterface&MockObject
     */
    private $messageManager;

    /**
     * @var Redirect&MockObject
     */
    private $redirect;

    /**
     * @var Save
     */
    private $controller;

    protected function setUp(): void
    {
        $this->transactionManagement = $this->createMock(CreditTransactionManagementInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);

        $this->redirect = $this->createMock(Redirect::class);
        $this->redirect->method('setPath')->willReturnSelf();

        $resultFactory = $this->createMock(ResultFactory::class);
        $resultFactory->method('create')->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirect);

        $admin = $this->createMock(User::class);
        $admin->method('getUsername')->willReturn('admin1');
        $auth = $this->createMock(Auth::class);
        $auth->method('getUser')->willReturn($admin);

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getResultFactory')->willReturn($resultFactory);
        $context->method('getMessageManager')->willReturn($this->messageManager);
        $context->method('getAuth')->willReturn($auth);

        $this->controller = new Save($context, $this->transactionManagement, $this->logger);
    }

    private function withParams(int $customerId, string $action, int $amount, string $message): void
    {
        $this->request->method('getParam')->willReturnMap([
            ['customer_id', null, $customerId],
            ['action', null, $action],
            ['amount', null, $amount],
            ['message', null, $message],
        ]);
    }

    public function testAddCreditDelegatesToTransactionManagementCredit(): void
    {
        $this->withParams(42, 'add', 20, 'Goodwill credit');

        $this->transactionManagement->expects(self::once())->method('credit')->with(
            42,
            20,
            CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_ADD,
            CreditTransactionInterface::SOURCE_ADMIN,
            'Goodwill credit',
            'admin1'
        );
        $this->transactionManagement->expects(self::never())->method('debit');

        $this->messageManager->expects(self::once())->method('addSuccessMessage');

        $this->controller->execute();
    }

    public function testRemoveCreditDelegatesToTransactionManagementDebit(): void
    {
        $this->withParams(42, 'remove', 20, 'Correction');

        $this->transactionManagement->expects(self::once())->method('debit')->with(
            42,
            20,
            CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_REMOVE,
            CreditTransactionInterface::SOURCE_ADMIN,
            'Correction',
            'admin1'
        );
        $this->transactionManagement->expects(self::never())->method('credit');

        $this->controller->execute();
    }

    public function testLocalizedExceptionMessageIsShownVerbatimToAdmin(): void
    {
        $this->withParams(42, 'add', 0, '');
        $this->transactionManagement->method('credit')->willThrowException(
            new LocalizedException(__('Amount must be a positive whole number of credit points.'))
        );

        $this->messageManager->expects(self::once())->method('addErrorMessage')
            ->with('Amount must be a positive whole number of credit points.');
        $this->logger->expects(self::never())->method('error');

        $this->controller->execute();
    }

    public function testGenericExceptionIsLoggedInternallyAndNotShownVerbatim(): void
    {
        $this->withParams(42, 'add', 20, 'reason');
        $this->transactionManagement->method('credit')->willThrowException(
            new \RuntimeException('Connection refused to internal database host')
        );

        $this->logger->expects(self::once())->method('error')->with(
            self::stringContains('Quick Consult Credit admin adjustment failed'),
            self::anything()
        );

        $this->messageManager->expects(self::once())->method('addErrorMessage')->with(
            self::logicalNot(self::stringContains('Connection refused'))
        );
        $this->messageManager->expects(self::never())->method('addSuccessMessage');

        $this->controller->execute();
    }

    public function testRedirectAlwaysTargetsCustomerEditPage(): void
    {
        $this->withParams(42, 'add', 20, 'reason');

        $this->redirect->expects(self::once())->method('setPath')
            ->with('customer/index/edit', ['id' => 42, '_current' => false])
            ->willReturnSelf();

        $result = $this->controller->execute();
        self::assertSame($this->redirect, $result);
    }
}
