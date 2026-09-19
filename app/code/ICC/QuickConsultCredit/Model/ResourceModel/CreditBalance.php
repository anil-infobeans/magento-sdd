<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model for the Customer Credit Account entity (`qcc_customer_credit`).
 */
class CreditBalance extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('qcc_customer_credit', 'entity_id');
    }

    /**
     * Load a customer's credit account by customer ID (QCC-ACCOUNT-001 uniqueness).
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param int $customerId
     * @return $this
     */
    public function loadByCustomerId(\Magento\Framework\Model\AbstractModel $object, int $customerId): self
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('customer_id = ?', $customerId);
        $data = $connection->fetchRow($select);
        if ($data) {
            $object->setData($data);
        }
        $object->setOrigData();
        $this->unserializeFields($object);
        $this->_afterLoad($object);

        return $this;
    }

    /**
     * Load a customer's credit account row with a pessimistic lock for an open transaction (QCC-DATA-001/003/006).
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param int $customerId
     * @return $this
     */
    public function loadByCustomerIdForUpdate(\Magento\Framework\Model\AbstractModel $object, int $customerId): self
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('customer_id = ?', $customerId)
            ->forUpdate(true);
        $data = $connection->fetchRow($select);
        if ($data) {
            $object->setData($data);
        }
        $object->setOrigData();
        $this->unserializeFields($object);
        $this->_afterLoad($object);

        return $this;
    }
}
