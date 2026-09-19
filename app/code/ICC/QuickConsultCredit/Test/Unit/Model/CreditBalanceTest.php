<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Model;

use ICC\QuickConsultCredit\Model\CreditBalance;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Covers QCC-ACCOUNT-001/003/006/007 getter/setter round-tripping on the Customer
 * Credit Account model (QCC-NFR-006).
 */
class CreditBalanceTest extends TestCase
{
    /**
     * @var CreditBalance
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(CreditBalance::class);
    }

    public function testCustomerIdGetterAndSetter(): void
    {
        self::assertSame($this->model, $this->model->setCustomerId(42));
        self::assertSame(42, $this->model->getCustomerId());
    }

    public function testBalanceGetterAndSetter(): void
    {
        $this->model->setBalance(100);
        self::assertSame(100, $this->model->getBalance());
    }

    public function testTotalCreditedGetterAndSetter(): void
    {
        $this->model->setTotalCredited(150);
        self::assertSame(150, $this->model->getTotalCredited());
    }

    public function testTotalDebitedGetterAndSetter(): void
    {
        $this->model->setTotalDebited(50);
        self::assertSame(50, $this->model->getTotalDebited());
    }

    public function testDefaultValuesAreZeroWhenUnset(): void
    {
        self::assertSame(0, $this->model->getCustomerId());
        self::assertSame(0, $this->model->getBalance());
        self::assertSame(0, $this->model->getTotalCredited());
        self::assertSame(0, $this->model->getTotalDebited());
    }
}
