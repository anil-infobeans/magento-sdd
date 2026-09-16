# Data Model: Quick Consult Credit

**Input**: [spec.md](./spec.md) Key Entities; [customer-credit-account.md](./customer-credit-account.md); [credit-ledger.md](./credit-ledger.md); [research.md](./research.md) §2

**Purpose**: Define the entities, fields, relationships, validation rules, and state-transition behavior for the Quick Consult Credit persistence model, at a logical (not SQL-implementation) level, consistent with the architecture-boundary constraint that specifications and design artifacts do not prescribe SQL statements or ORM code.

## Entity: Customer Credit Account

**Represents**: The current, materialized credit state for exactly one Magento customer (`qcc_customer_credit`).

**Source requirements**: [customer-credit-account.md](./customer-credit-account.md) QCC-ACCOUNT-001 through QCC-ACCOUNT-008.

| Field | Type | Nullable | Description |
|---|---|---|---|
| `entity_id` | integer (identity) | No | Primary key |
| `customer_id` | integer | No | Unique; references the Magento customer entity |
| `balance` | integer | No | Current available balance, in whole-number credit points; default 0 |
| `total_credited` | integer | No | Lifetime cumulative CREDIT-direction total; default 0 |
| `total_debited` | integer | No | Lifetime cumulative DEBIT-direction total; default 0 |
| `created_at` | datetime | No | Account creation timestamp |
| `updated_at` | datetime | No | Timestamp of the last balance change |

**Validation rules**:
- `balance >= 0` at all times (QCC-ACCOUNT-003; QCC-DATA-001) — enforced inside the same atomic operation that would otherwise violate it, not as a post-hoc check.
- Exactly one row per `customer_id` (QCC-ACCOUNT-001) — enforced via a uniqueness constraint on `customer_id`.
- `balance` is always derivable as the sum of all ledger movements for that customer (QCC-ACCOUNT-005; QCC-AUDIT-004).

**Lifecycle**: Created lazily with `balance = 0` on the first qualifying balance-changing operation for a customer who has no existing account (QCC-ACCOUNT-004). Never deleted as part of normal operation (no deletion requirement exists in any sub-specification).

**Relationships**:
- One-to-one with the Magento customer entity (`customer_id` references `customer_entity.entity_id`).
- One-to-many with Credit Transaction (one account has many ledger entries).

## Entity: Credit Transaction (Ledger Entry)

**Represents**: A single immutable, append-only balance movement (`qcc_credit_transaction`).

**Source requirements**: [credit-ledger.md](./credit-ledger.md) QCC-LEDGER-001 through QCC-LEDGER-009.

| Field | Type | Nullable | Description |
|---|---|---|---|
| `entity_id` | integer (identity) | No | Primary key |
| `customer_id` | integer | No | References the owning Customer Credit Account's customer |
| `transaction_type` | enumerated string | No | One of: `PURCHASE`, `REDEEM`, `ADMIN_ADD`, `ADMIN_REMOVE` (QCC-LEDGER-002; casing per [clarifications.md](./clarifications.md) CLA-006, open — Architecture casing adopted as working default) |
| `direction` | enumerated string | No | `CREDIT` or `DEBIT` (QCC-LEDGER-003) |
| `amount` | integer (positive) | No | Magnitude of the movement, in whole-number credit points |
| `balance_before` | integer | No | Account balance immediately prior to this movement |
| `balance_after` | integer | No | Account balance immediately after this movement; must equal `balance_before ± amount` per `direction` |
| `message` | text | Yes (required for ADMIN_ADD/ADMIN_REMOVE) | Reason/message (QCC-LEDGER-004) |
| `source` | enumerated string | No | `CUSTOMER`, `API`, `ADMIN`, `SYSTEM` (QCC-LEDGER-005) |
| `created_by` | string | Yes | Administrator identity or system/integration identifier (QCC-LEDGER-005) |
| `source_reference` | string | Yes (required for `PURCHASE`) | Deterministic purchase reference (qualifying order item identifier) used for posting idempotency (QCC-PURCHASE-011; [research.md](./research.md) §5); unique when present |
| `created_at` | datetime | No | Server-assigned creation timestamp (QCC-LEDGER-006) — never client-supplied |

**Validation rules**:
- Immutable after creation: no update or delete operation is defined for this entity (QCC-LEDGER-001; QCC-LEDGER-008; QCC-DATA-004).
- `message` is mandatory and non-empty when `transaction_type` is `ADMIN_ADD` or `ADMIN_REMOVE` (QCC-LEDGER-004; QCC-ADMIN-005).
- `source_reference` must be unique across all `PURCHASE`-type rows when present, to enforce purchase-posting idempotency (QCC-PURCHASE-001/009/010/011).
- `balance_after = balance_before + amount` when `direction = CREDIT`; `balance_after = balance_before - amount` when `direction = DEBIT` (QCC-LEDGER-003).

**Lifecycle**: Created exactly once per successful balance-changing operation, atomically with the corresponding Customer Credit Account update (QCC-LEDGER-007; QCC-DATA-002/003). Never modified or deleted.

**Relationships**:
- Many-to-one with Customer Credit Account (via `customer_id`).
- Optionally references a Magento sales order item (`source_reference`) when `transaction_type = PURCHASE`.

## Entity: Quick Consult Credit Product (reference only, not a new table)

**Represents**: The Magento catalog product customers purchase to acquire credit. This is an existing Magento entity (`catalog_product_entity` and related attribute tables) referenced by configuration, not a new persistence entity introduced by this feature.

**Source requirements**: [product-configuration.md](./product-configuration.md) QCC-PROD-001 through QCC-PROD-008; [configuration.md](./configuration.md) QCC-CONFIG-002.

**Relevant attributes** (existing Magento product attributes, no schema change required):
- Attribute set: Consultation Services.
- Quantity: standard Magento product quantity/qty field, used as the qualifying credit amount (QCC-PROD-003; QCC-PURCHASE-002).

## State Transitions

Neither the Customer Credit Account nor the Credit Transaction entity has a discrete "status" field with multiple lifecycle states beyond the linear, append-only movement history described above. The only meaningful "state transition" is the balance value itself changing as a result of a new Credit Transaction row being appended — there is no separate workflow/status state machine (e.g., no "draft"/"pending"/"approved" ledger states). This is consistent with the ledger being immutable and append-only (QCC-LEDGER-001).

## Indexing Considerations (logical, not SQL-prescriptive)

- Customer Credit Account: unique index on `customer_id` (QCC-ACCOUNT-001 uniqueness).
- Credit Transaction: composite index supporting ordered, paginated retrieval by `customer_id` and creation order (QCC-LEDGER-009; [customer-dashboard.md](./customer-dashboard.md) QCC-CUSTOMER-004); unique index on `source_reference` where not null (purchase-posting idempotency).

## Traceability Summary

| Entity | Governing sub-specification | Key requirement IDs |
|---|---|---|
| Customer Credit Account | [customer-credit-account.md](./customer-credit-account.md) | QCC-ACCOUNT-001…008 |
| Credit Transaction | [credit-ledger.md](./credit-ledger.md) | QCC-LEDGER-001…009 |
| Atomicity/consistency across both | [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) | QCC-DATA-001…008 |
