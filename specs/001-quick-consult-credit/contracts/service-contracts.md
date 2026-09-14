# Contract: Magento Service Contracts (PHP Interfaces)

Signatures only — no method bodies/implementation. Derived from Technical Architecture §8/§21 and requirements in [account.md](../account.md), [ledger.md](../ledger.md), [redemption.md](../redemption.md), [purchase.md](../purchase.md), [security.md](../security.md).

Namespace root: `ICC\QuickConsultCredit\Api`.

## Data Interfaces (`Api/Data`)

```php
interface CreditBalanceInterface
{
    public function getCustomerId(): int;
    public function getCurrencyCode(): string;
    public function getBalance(): string;       // decimal as string, 2 places
    public function getTotalCredited(): string;
    public function getTotalDebited(): string;
    public function getUpdatedAt(): string;      // ISO-8601
}

interface CreditTransactionInterface
{
    public function getEntityId(): int;
    public function getCustomerId(): int;
    public function getTransactionType(): string; // PURCHASE|REDEEM|ADMIN_ADD|ADMIN_REMOVE
    public function getDirection(): string;        // CREDIT|DEBIT
    public function getAmount(): string;
    public function getBalanceBefore(): string;
    public function getBalanceAfter(): string;
    public function getCurrencyCode(): string;
    public function getReferenceType(): ?string;
    public function getReferenceId(): ?string;
    public function getIdempotencyKey(): ?string;
    public function getMessage(): ?string;
    public function getSource(): string;           // SYSTEM|API|ADMIN|CUSTOMER
    public function getCreatedBy(): ?string;
    public function getCreatedAt(): string;
}
```

## `CreditBalanceManagementInterface`

Read-facing service used by both the REST balance endpoint and the customer/admin UI blocks (QCC-API-001, QCC-CUSTOMER-002, QCC-ADMIN-003).

```php
interface CreditBalanceManagementInterface
{
    /**
     * Returns the account, creating a zero-balance record on first access if none exists (QCC-ACCOUNT-003).
     * @throws \Magento\Framework\Exception\NoSuchEntityException if $customerId does not reference a real customer.
     */
    public function getBalance(int $customerId): CreditBalanceInterface;
}
```

## `CreditTransactionManagementInterface`

Write-facing service handling REDEEM (external/API-initiated) and ADMIN_ADD/ADMIN_REMOVE (admin-initiated) operations. Enforces authorization-before-existence ordering (QCC-SEC-008) and idempotency (QCC-IDEMP-001-006) at the implementation level, not in this contract.

```php
interface CreditTransactionManagementInterface
{
    /**
     * @throws \ICC\QuickConsultCredit\Api\Exception\InsufficientBalanceException
     * @throws \ICC\QuickConsultCredit\Api\Exception\IdempotencyKeyConflictException
     * @throws \Magento\Framework\Exception\NoSuchEntityException CUSTOMER_NOT_FOUND
     */
    public function redeem(
        int $customerId,
        string $amount,
        string $referenceType,
        string $referenceId,
        string $idempotencyKey,
        ?string $message = null
    ): CreditTransactionInterface;

    /**
     * Admin-only credit addition (QCC-ADMIN-005). $createdBy is the acting administrator identity.
     */
    public function adminAdd(
        int $customerId,
        string $amount,
        string $message,
        string $createdBy
    ): CreditTransactionInterface;

    /**
     * Admin-only credit removal (QCC-ADMIN-008).
     * @throws \ICC\QuickConsultCredit\Api\Exception\InsufficientBalanceException
     */
    public function adminRemove(
        int $customerId,
        string $amount,
        string $message,
        string $createdBy
    ): CreditTransactionInterface;
}
```

## `CreditPurchaseProcessor`

Internal (non-webapi-exposed) service invoked by the sales observer described in [research.md](../research.md) Decision 2. Not part of the public REST surface.

```php
interface CreditPurchaseProcessor
{
    /**
     * Posts a PURCHASE credit for a single qualifying order item, exactly once per item
     * (idempotency key = sales_order_item_id, QCC-PURCHASE-009).
     * No-op (returns null) if the item was already posted.
     */
    public function postPurchase(\Magento\Sales\Api\Data\OrderItemInterface $orderItem): ?CreditTransactionInterface;
}
```

## `CreditLedgerInterface`

Read-facing history/audit service (QCC-LEDGER-010-012, QCC-AUDIT-002-004).

```php
interface CreditLedgerInterface
{
    /**
     * Returns transactions for a customer, ordered newest-first, paginated.
     * @return CreditTransactionInterface[]
     */
    public function getHistory(int $customerId, int $pageSize = 20, int $currentPage = 1): array;

    public function getHistoryCount(int $customerId): int;
}
```
