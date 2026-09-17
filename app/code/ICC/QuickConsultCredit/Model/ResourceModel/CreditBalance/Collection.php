<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\ResourceModel\CreditBalance;

use ICC\QuickConsultCredit\Model\CreditBalance;
use ICC\QuickConsultCredit\Model\ResourceModel\CreditBalance as CreditBalanceResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection of Customer Credit Account entities.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(CreditBalance::class, CreditBalanceResourceModel::class);
    }
}
