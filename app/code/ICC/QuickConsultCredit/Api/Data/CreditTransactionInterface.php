<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Api\Data;

/**
 * Credit transaction (ledger entry) data contract.
 *
 * Represents a single immutable, append-only balance movement.
 * See specs/quick-consult-credit/plan/data-model.md "Entity: Credit Transaction (Ledger Entry)".
 */
interface CreditTransactionInterface
{
    public const ENTITY_ID = 'entity_id';
    public const CUSTOMER_ID = 'customer_id';
    public const TRANSACTION_TYPE = 'transaction_type';
    public const DIRECTION = 'direction';
    public const AMOUNT = 'amount';
    public const BALANCE_BEFORE = 'balance_before';
    public const BALANCE_AFTER = 'balance_after';
    public const MESSAGE = 'message';
    public const SOURCE = 'source';
    public const CREATED_BY = 'created_by';
    public const SOURCE_REFERENCE = 'source_reference';
    public const CREATED_AT = 'created_at';

    public const TRANSACTION_TYPE_PURCHASE = 'PURCHASE';
    public const TRANSACTION_TYPE_REDEEM = 'REDEEM';
    public const TRANSACTION_TYPE_ADMIN_ADD = 'ADMIN_ADD';
    public const TRANSACTION_TYPE_ADMIN_REMOVE = 'ADMIN_REMOVE';

    public const DIRECTION_CREDIT = 'CREDIT';
    public const DIRECTION_DEBIT = 'DEBIT';

    public const SOURCE_CUSTOMER = 'CUSTOMER';
    public const SOURCE_API = 'API';
    public const SOURCE_ADMIN = 'ADMIN';
    public const SOURCE_SYSTEM = 'SYSTEM';

    /**
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self;

    /**
     * @return string
     */
    public function getTransactionType(): string;

    /**
     * @param string $transactionType
     * @return $this
     */
    public function setTransactionType(string $transactionType): self;

    /**
     * @return string
     */
    public function getDirection(): string;

    /**
     * @param string $direction
     * @return $this
     */
    public function setDirection(string $direction): self;

    /**
     * @return int
     */
    public function getAmount(): int;

    /**
     * @param int $amount
     * @return $this
     */
    public function setAmount(int $amount): self;

    /**
     * @return int
     */
    public function getBalanceBefore(): int;

    /**
     * @param int $balanceBefore
     * @return $this
     */
    public function setBalanceBefore(int $balanceBefore): self;

    /**
     * @return int
     */
    public function getBalanceAfter(): int;

    /**
     * @param int $balanceAfter
     * @return $this
     */
    public function setBalanceAfter(int $balanceAfter): self;

    /**
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * @param string|null $message
     * @return $this
     */
    public function setMessage(?string $message): self;

    /**
     * @return string
     */
    public function getSource(): string;

    /**
     * @param string $source
     * @return $this
     */
    public function setSource(string $source): self;

    /**
     * @return string|null
     */
    public function getCreatedBy(): ?string;

    /**
     * @param string|null $createdBy
     * @return $this
     */
    public function setCreatedBy(?string $createdBy): self;

    /**
     * @return string|null
     */
    public function getSourceReference(): ?string;

    /**
     * @param string|null $sourceReference
     * @return $this
     */
    public function setSourceReference(?string $sourceReference): self;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;
}
