<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Controller\Account;

use ICC\QuickConsultCredit\Controller\Account\Transactions;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;

/**
 * Covers that the paginated transaction history controller renders the page without
 * ever reading a customer identity from the request — the `p` (page) query parameter
 * is only ever consumed by the displaying block, scoped to the session customer
 * (QCC-CUSTOMER-004/005, QCC-NFR-006).
 */
class TransactionsTest extends TestCase
{
    public function testExecuteRendersPageWithExpectedTitle(): void
    {
        $context = $this->createMock(Context::class);

        $page = $this->createMock(Page::class);
        $pageConfig = $this->createMock(PageConfig::class);
        $title = $this->createMock(\Magento\Framework\View\Page\Title::class);

        $page->method('getConfig')->willReturn($pageConfig);
        $pageConfig->method('getTitle')->willReturn($title);
        $title->expects(self::once())->method('set')->with('Quick Consult Credit');

        $resultPageFactory = $this->createMock(PageFactory::class);
        $resultPageFactory->expects(self::once())->method('create')->willReturn($page);

        $controller = new Transactions($context, $resultPageFactory);

        self::assertSame($page, $controller->execute());
    }
}
