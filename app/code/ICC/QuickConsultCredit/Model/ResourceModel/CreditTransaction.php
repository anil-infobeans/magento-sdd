<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model for the Credit Transaction (ledger entry) entity (`qcc_credit_transaction`).
 *
 * Immutable and append-only (QCC-LEDGER-001/008): no update or delete save path is
 * exposed. {@see save()} only ever performs an INSERT; any attempt to persist a
 * transaction that already has an identity throws, since no requirement defines an
 * update/delete behavior for this entity.
 */
class CreditTransaction extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('qcc_credit_transaction', 'entity_id');
    }

    /**
     * @inheritDoc
     *
     * @param AbstractModel $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(AbstractModel $object)
    {
        if ($object->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Credit ledger transactions are immutable and cannot be updated once created.')
            );
        }

        return parent::save($object);
    }

    /**
     * Delete is never permitted for an append-only ledger (QCC-LEDGER-001/008).
     *
     * @param AbstractModel $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(AbstractModel $object)
    {
        throw new \Magento\Framework\Exception\LocalizedException(
            __('Credit ledger transactions are immutable and cannot be deleted.')
        );
    }

    /**
     * Whether a PURCHASE transaction was already posted for this reference (QCC-PURCHASE-001/009/010/011).
     *
     * @param string $sourceReference
     * @return bool
     */
    public function existsBySourceReference(string $sourceReference): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'entity_id')
            ->where('source_reference = ?', $sourceReference);

        return (bool) $connection->fetchOne($select);
    }
}
