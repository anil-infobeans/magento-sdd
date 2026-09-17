<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model;

use ICC\QuickConsultCredit\Api\Data\CreditBalanceInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Customer Credit Account model (`qcc_customer_credit`).
 */
class CreditBalance extends AbstractModel implements CreditBalanceInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\CreditBalance::class);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId(): int
    {
        return (int) $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId(int $customerId): CreditBalanceInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getBalance(): int
    {
        return (int) $this->getData(self::BALANCE);
    }

    /**
     * @inheritDoc
     */
    public function setBalance(int $balance): CreditBalanceInterface
    {
        return $this->setData(self::BALANCE, $balance);
    }

    /**
     * @inheritDoc
     */
    public function getTotalCredited(): int
    {
        return (int) $this->getData(self::TOTAL_CREDITED);
    }

    /**
     * @inheritDoc
     */
    public function setTotalCredited(int $totalCredited): CreditBalanceInterface
    {
        return $this->setData(self::TOTAL_CREDITED, $totalCredited);
    }

    /**
     * @inheritDoc
     */
    public function getTotalDebited(): int
    {
        return (int) $this->getData(self::TOTAL_DEBITED);
    }

    /**
     * @inheritDoc
     */
    public function setTotalDebited(int $totalDebited): CreditBalanceInterface
    {
        return $this->setData(self::TOTAL_DEBITED, $totalDebited);
    }
}
