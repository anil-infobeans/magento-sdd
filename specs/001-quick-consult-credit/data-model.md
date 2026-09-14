# Data Model: Quick Consult Credit

Derived from [account.md](./account.md), [ledger.md](./ledger.md), [product.md](./product.md), and the Technical Architecture §7 database schema, translated into concrete field-level design per [research.md](./research.md) Decisions 1, 3, and 5. This is an implementation-level data model (Phase 1 design artifact); the specification files remain the source of truth for *why* each field/rule exists.

## Entity: Customer Credit Account (`qcc_customer_credit`)

Represents one customer's current Quick Consult Credit balance and lifetime statistics (spec: [account.md](./account.md)).

| Field | Type | Nullable | Default | Description / Validation Rule |
|---|---|---|---|---|
| `entity_id` | BIGINT UNSIGNED | No | auto-increment | Primary key. |
| `customer_id` | INT UNSIGNED | No | — | FK → `customer_entity.entity_id`; **unique** (QCC-ACCOUNT-001: at most one account per customer per currency). |
| `currency_code` | CHAR(3) | No | store base currency | Account currency, fixed at creation (QCC-CURR-001); immutable after creation. |
| `balance` | DECIMAL(14,2) | No | `0.00` | Current available balance; invariant: `balance >= 0` always (QCC-ACCOUNT-006), enforced at the service layer before commit. |
| `total_credited` | DECIMAL(14,2) | No | `0.00` | Cumulative sum of all successful CREDIT-direction ledger movements (QCC-ACCOUNT-008). |
| `total_debited` | DECIMAL(14,2) | No | `0.00` | Cumulative sum of all successful DEBIT-direction ledger movements (QCC-ACCOUNT-009). |
| `created_at` | TIMESTAMP | No | current timestamp | Account creation time (QCC-ACCOUNT-005). |
| `updated_at` | TIMESTAMP | No | current timestamp, on update | Last balance-change time. |

**Indexes**: UNIQUE (`customer_id`).

**Invariant (cross-field)**: `balance` at any point in time equals the net sum of all `qcc_credit_transaction` rows for that `customer_id` (QCC-ACCOUNT-007, QCC-AUDIT-006). This is not a stored/derived column — it is enforced procedurally by requiring every balance-column update to occur in the same DB transaction as its corresponding ledger insert (see [research.md](./research.md) Decision 4).

**Lifecycle**: Created on demand (zero balance) the first time a credit-worthy event occurs for a customer (QCC-ACCOUNT-003); never deleted through normal operation.

## Entity: Credit Transaction / Ledger Entry (`qcc_credit_transaction`)

Immutable, append-only record of a single balance movement (spec: [ledger.md](./ledger.md)).

| Field | Type | Nullable | Default | Description / Validation Rule |
|---|---|---|---|---|
| `entity_id` | BIGINT UNSIGNED | No | auto-increment | Primary key. |
| `customer_id` | INT UNSIGNED | No | — | FK → `customer_entity.entity_id`. |
| `transaction_type` | VARCHAR(32) | No | — | One of `PURCHASE`, `REDEEM`, `ADMIN_ADD`, `ADMIN_REMOVE` (QCC-LEDGER-003). |
| `amount` | DECIMAL(14,2) | No | — | Positive magnitude; `amount > 0` always (QCC-LEDGER-005). |
| `direction` | VARCHAR(8) | No | — | `CREDIT` for `PURCHASE`/`ADMIN_ADD`; `DEBIT` for `REDEEM`/`ADMIN_REMOVE` (QCC-LEDGER-004). |
| `balance_before` | DECIMAL(14,2) | No | — | Account balance immediately before this movement (QCC-LEDGER-006). |
| `balance_after` | DECIMAL(14,2) | No | — | Account balance immediately after this movement; `balance_after = balance_before ± amount` per direction. |
| `currency_code` | CHAR(3) | No | — | Must equal the owning account's `currency_code` (QCC-CURR-004: mismatch rejected before this row is ever created). |
| `reference_type` | VARCHAR(32) | Yes | null | `ORDER_ITEM` (purchase), `CONSULTATION` (redeem), `ADMIN` (admin add/remove), `API` (external). |
| `reference_id` | VARCHAR(128) | Yes | null | The concrete reference value (e.g., `sales_order_item_id`, consultation ticket, admin-supplied reference). |
| `idempotency_key` | VARCHAR(128) | Yes | null | Caller-supplied key for externally initiated writes (QCC-IDEMP-003); null for system/admin-only writes that don't require one. |
| `message` | TEXT | Yes | null | Human-readable reason (mandatory at the service-layer for `ADMIN_ADD`/`ADMIN_REMOVE` per QCC-ADMIN-005/008, optional otherwise). |
| `source` | VARCHAR(32) | No | — | `SYSTEM` (purchase posting), `API` (external redeem), `ADMIN` (admin add/remove), `CUSTOMER` (reserved for future customer-initiated writes; unused in this release). |
| `created_by` | VARCHAR(128) | Yes | null | Authenticated administrator identity for `ADMIN_ADD`/`ADMIN_REMOVE` (QCC-ADMIN-010, QCC-SEC-005); null for `SYSTEM`/`API`-sourced rows. |
| `created_at` | TIMESTAMP | No | current timestamp | Ledger timestamp; used for ordering (QCC-LEDGER-010) and pagination (QCC-LEDGER-012). |

**Indexes**:
- `(customer_id, created_at, entity_id)` — composite, supports ordered/paginated history retrieval.
- UNIQUE `(reference_type, reference_id)` **where `reference_type = 'ORDER_ITEM'`** — enforces purchase-posting exactly-once semantics (QCC-PURCHASE-009, QCC-IDEMP-001; see [research.md](./research.md) Decision 3). Implemented as a partial/filtered unique constraint or, if the target MySQL/MariaDB version lacks partial-index support, as a unique index on a dedicated nullable `purchase_posting_key` column populated only for `PURCHASE` rows.
- UNIQUE `(idempotency_key)` — nulls excluded from uniqueness by standard MySQL/MariaDB unique-index semantics (QCC-IDEMP-005; see [research.md](./research.md) Decision 5).

**Immutability rule**: No `UPDATE` or `DELETE` statement is ever issued against this table by normal application code paths (QCC-LEDGER-007/008). Corrections are new rows referencing the original `entity_id` in `reference_id`/`message` (QCC-LEDGER-009).

**Relationships**: Many `qcc_credit_transaction` rows reference one `qcc_customer_credit` row (via shared `customer_id`); a `PURCHASE`-type row additionally references one `sales_order_item` row (via `reference_id` when `reference_type = 'ORDER_ITEM'`).

## Entity: Quick Consult Credit Product & Denomination (configuration, not a new table)

Per [research.md](./research.md) Decision 1, denominations are **not** a persisted entity — they are a Magento System Configuration value (array/serialized list of decimal amounts) consumed at storefront render time and at purchase-posting time to validate/derive the credit amount (QCC-PROD-003/004/008). No new table is introduced for this concept.

| Conceptual Field | Source | Validation Rule |
|---|---|---|
| Denomination value | `etc/system.xml` configuration, scope: default/website/store | Must be a positive decimal with exactly 2 fractional digits (QCC-CURR-002); the configured set defaults to 25.00/50.00/100.00/250.00 (QCC-PROD-003) but is administrator-editable without deployment (QCC-PROD-004/005). |
| Product ↔ denomination association | Magento product entity (Consultation Services attribute set, per QCC-PROD-002/002a) | The specific mechanism binding a purchasable product/option to a configured denomination value is an implementation detail resolved at task-breakdown time (`/speckit-tasks`); this plan fixes only that the source of truth for the *set of valid amounts* is System Configuration, not a hard-coded value or a separate denomination table. |

## State Transitions

- **Customer Credit Account**: `(does not exist)` → `created (balance=0.00)` → `balance mutated by CREDIT or DEBIT operations` (loop; balance never leaves the `>= 0` invariant). No terminal/deleted state under normal operation.
- **Credit Transaction**: `(does not exist)` → `created (immutable, terminal)`. No further transitions — a ledger row has exactly one lifecycle state once persisted.

## Cross-Entity Invariants (enforced at the service layer, not the schema layer)

1. For any successful operation, exactly one new `qcc_credit_transaction` row is created and the corresponding `qcc_customer_credit` row is updated, within the same database transaction (QCC-CONC-002/004).
2. `qcc_customer_credit.balance` is never mutated except as part of an operation that also inserts a `qcc_credit_transaction` row (no orphaned balance changes).
3. `qcc_credit_transaction.currency_code` always equals the `currency_code` of the `qcc_customer_credit` row sharing its `customer_id` (single-currency-per-account rule, QCC-CURR-006).
