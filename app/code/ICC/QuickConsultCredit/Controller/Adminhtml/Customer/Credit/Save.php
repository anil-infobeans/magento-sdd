<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Controller\Adminhtml\Customer\Credit;

use ICC\QuickConsultCredit\Api\CreditTransactionManagementInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Handles Admin "Add Credit"/"Remove Credit" form submission on the customer edit page
 * (QCC-ADMIN-003/004/005/006/007/009). Delegates entirely to
 * {@see CreditTransactionManagementInterface} — this controller performs no direct
 * persistence (QCC-DATA-008) and no business validation beyond what that service
 * already enforces (empty-reason rejection, amount validation, insufficient-balance
 * rejection are all re-verified there regardless of anything checked here).
 */
class Save extends Action
{
    /**
     * @var string
     */
    public const ADMIN_RESOURCE = 'ICC_QuickConsultCredit::manage';

    /**
     * @var CreditTransactionManagementInterface
     */
    private $transactionManagement;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param CreditTransactionManagementInterface $transactionManagement
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CreditTransactionManagementInterface $transactionManagement,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->transactionManagement = $transactionManagement;
        $this->logger = $logger;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        $customerId = (int) $this->getRequest()->getParam('customer_id');
        $action = (string) $this->getRequest()->getParam('action');
        $amount = (int) $this->getRequest()->getParam('amount');
        $message = (string) $this->getRequest()->getParam('message');

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('customer/index/edit', ['id' => $customerId, '_current' => false]);

        try {
            $admin = $this->_auth->getUser();
            $createdBy = $admin ? $admin->getUsername() : null;

            if ($action === 'remove') {
                $this->transactionManagement->debit(
                    $customerId,
                    $amount,
                    CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_REMOVE,
                    CreditTransactionInterface::SOURCE_ADMIN,
                    $message,
                    $createdBy
                );
            } else {
                $this->transactionManagement->credit(
                    $customerId,
                    $amount,
                    CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_ADD,
                    CreditTransactionInterface::SOURCE_ADMIN,
                    $message,
                    $createdBy
                );
            }

            $this->messageManager->addSuccessMessage(__('The Quick Consult Credit adjustment was saved.'));
        } catch (LocalizedException $e) {
            // Every business-rejection this module raises (missing reason, invalid
            // amount, insufficient balance, unknown customer) is a LocalizedException
            // with a deliberately curated, safe-to-display message (QCC-SEC-005/006).
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            // Anything else (e.g. an infrastructure/DB failure) is logged internally
            // only - never shown to the admin verbatim, to avoid disclosing internal
            // exception details (QCC-SEC-005/006, QCC-AUDIT-005).
            $this->logger->error('Quick Consult Credit admin adjustment failed', [
                'customer_id' => $customerId,
                'action' => $action,
                'exception' => $e->getMessage(),
            ]);
            $this->messageManager->addErrorMessage(
                __('The Quick Consult Credit adjustment could not be saved. Please try again.')
            );
        }

        return $resultRedirect;
    }
}
