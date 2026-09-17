<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model;

use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Credit Transaction (ledger entry) model (`qcc_credit_transaction`).
 *
 * Immutable and append-only (QCC-LEDGER-001/008): see
 * \ICC\QuickConsultCredit\Model\ResourceModel\CreditTransaction, which rejects any save
 * on an entity that already has an entity_id.
 */
class CreditTransaction extends AbstractModel implements CreditTransactionInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\CreditTransaction::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): ?int
    {
        $id = $this->getData(self::ENTITY_ID);
        return $id === null ? null : (int) $id;
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
    public function setCustomerId(int $customerId): CreditTransactionInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getTransactionType(): string
    {
        return (string) $this->getData(self::TRANSACTION_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setTransactionType(string $transactionType): CreditTransactionInterface
    {
        return $this->setData(self::TRANSACTION_TYPE, $transactionType);
    }

    /**
     * @inheritDoc
     */
    public function getDirection(): string
    {
        return (string) $this->getData(self::DIRECTION);
    }

    /**
     * @inheritDoc
     */
    public function setDirection(string $direction): CreditTransactionInterface
    {
        return $this->setData(self::DIRECTION, $direction);
    }

    /**
     * @inheritDoc
     */
    public function getAmount(): int
    {
        return (int) $this->getData(self::AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setAmount(int $amount): CreditTransactionInterface
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * @inheritDoc
     */
    public function getBalanceBefore(): int
    {
        return (int) $this->getData(self::BALANCE_BEFORE);
    }

    /**
     * @inheritDoc
     */
    public function setBalanceBefore(int $balanceBefore): CreditTransactionInterface
    {
        return $this->setData(self::BALANCE_BEFORE, $balanceBefore);
    }

    /**
     * @inheritDoc
     */
    public function getBalanceAfter(): int
    {
        return (int) $this->getData(self::BALANCE_AFTER);
    }

    /**
     * @inheritDoc
     */
    public function setBalanceAfter(int $balanceAfter): CreditTransactionInterface
    {
        return $this->setData(self::BALANCE_AFTER, $balanceAfter);
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): ?string
    {
        $message = $this->getData(self::MESSAGE);
        return $message === null ? null : (string) $message;
    }

    /**
     * @inheritDoc
     */
    public function setMessage(?string $message): CreditTransactionInterface
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * @inheritDoc
     */
    public function getSource(): string
    {
        return (string) $this->getData(self::SOURCE);
    }

    /**
     * @inheritDoc
     */
    public function setSource(string $source): CreditTransactionInterface
    {
        return $this->setData(self::SOURCE, $source);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedBy(): ?string
    {
        $createdBy = $this->getData(self::CREATED_BY);
        return $createdBy === null ? null : (string) $createdBy;
    }

    /**
     * @inheritDoc
     */
    public function setCreatedBy(?string $createdBy): CreditTransactionInterface
    {
        return $this->setData(self::CREATED_BY, $createdBy);
    }

    /**
     * @inheritDoc
     */
    public function getSourceReference(): ?string
    {
        $reference = $this->getData(self::SOURCE_REFERENCE);
        return $reference === null ? null : (string) $reference;
    }

    /**
     * @inheritDoc
     */
    public function setSourceReference(?string $sourceReference): CreditTransactionInterface
    {
        return $this->setData(self::SOURCE_REFERENCE, $sourceReference);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        $createdAt = $this->getData(self::CREATED_AT);
        return $createdAt === null ? null : (string) $createdAt;
    }
}
