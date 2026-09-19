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
     * Get the transaction's unique entity identifier.
     *
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * Get the customer this transaction belongs to.
     *
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * Set the customer this transaction belongs to.
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self;

    /**
     * Get the transaction type (e.g. PURCHASE, REDEEM, ADMIN_ADD, ADMIN_REMOVE).
     *
     * @return string
     */
    public function getTransactionType(): string;

    /**
     * Set the transaction type (e.g. PURCHASE, REDEEM, ADMIN_ADD, ADMIN_REMOVE).
     *
     * @param string $transactionType
     * @return $this
     */
    public function setTransactionType(string $transactionType): self;

    /**
     * Get the ledger direction (CREDIT or DEBIT).
     *
     * @return string
     */
    public function getDirection(): string;

    /**
     * Set the ledger direction (CREDIT or DEBIT).
     *
     * @param string $direction
     * @return $this
     */
    public function setDirection(string $direction): self;

    /**
     * Get the transaction amount, in whole-number credit points.
     *
     * @return int
     */
    public function getAmount(): int;

    /**
     * Set the transaction amount, in whole-number credit points.
     *
     * @param int $amount
     * @return $this
     */
    public function setAmount(int $amount): self;

    /**
     * Get the customer's balance immediately before this transaction was applied.
     *
     * @return int
     */
    public function getBalanceBefore(): int;

    /**
     * Set the customer's balance immediately before this transaction was applied.
     *
     * @param int $balanceBefore
     * @return $this
     */
    public function setBalanceBefore(int $balanceBefore): self;

    /**
     * Get the customer's balance immediately after this transaction was applied.
     *
     * @return int
     */
    public function getBalanceAfter(): int;

    /**
     * Set the customer's balance immediately after this transaction was applied.
     *
     * @param int $balanceAfter
     * @return $this
     */
    public function setBalanceAfter(int $balanceAfter): self;

    /**
     * Get the optional free-text reason/message attached to this transaction.
     *
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * Set the optional free-text reason/message attached to this transaction.
     *
     * @param string|null $message
     * @return $this
     */
    public function setMessage(?string $message): self;

    /**
     * Get the originating source of this transaction (e.g. CUSTOMER, API, ADMIN, SYSTEM).
     *
     * @return string
     */
    public function getSource(): string;

    /**
     * Set the originating source of this transaction (e.g. CUSTOMER, API, ADMIN, SYSTEM).
     *
     * @param string $source
     * @return $this
     */
    public function setSource(string $source): self;

    /**
     * Get the identity (e.g. admin username) that created this transaction.
     *
     * @return string|null
     */
    public function getCreatedBy(): ?string;

    /**
     * Set the identity (e.g. admin username) that created this transaction.
     *
     * @param string|null $createdBy
     * @return $this
     */
    public function setCreatedBy(?string $createdBy): self;

    /**
     * Get the optional external reference (e.g. order increment ID) tied to this transaction.
     *
     * @return string|null
     */
    public function getSourceReference(): ?string;

    /**
     * Set the optional external reference (e.g. order increment ID) tied to this transaction.
     *
     * @param string|null $sourceReference
     * @return $this
     */
    public function setSourceReference(?string $sourceReference): self;

    /**
     * Get the timestamp this transaction was recorded at.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;
}
