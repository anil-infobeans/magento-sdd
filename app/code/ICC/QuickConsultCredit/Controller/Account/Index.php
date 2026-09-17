<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Controller\Account;

use Magento\Customer\Controller\AccountInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * My Account "Quick Consult Credit" dashboard landing page (QCC-CUSTOMER-001/002/003).
 *
 * Implementing {@see AccountInterface} (rather than the deprecated
 * `Magento\Customer\Controller\AbstractAccount`) is sufficient for Magento's customer
 * auth-redirect plugin to require an authenticated session before this action runs; no
 * customer identity is ever read from the request (QCC-CUSTOMER-001/005) — the
 * displaying block resolves it solely from the customer session.
 */
class Index extends Action implements AccountInterface, HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Quick Consult Credit'));

        return $resultPage;
    }
}
