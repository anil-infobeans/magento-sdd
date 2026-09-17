# Contract: Service Interfaces

**Input**: [data-integrity-and-concurrency.md](../non-functional/data-integrity-and-concurrency.md) QCC-DATA-007/008; Technical Architecture §8 (Appendix A); [research.md](../plan/research.md) §1–5

**Purpose**: Define the logical service-contract boundary that every layer (customer UI, Admin UI, REST API, purchase-posting observer) must go through for balance-changing operations. This is a behavioral/interface-level contract, not a PHP implementation — no method bodies, SQL, or framework wiring are included here (see architecture-boundary constraint in [spec.md](../spec.md)).

## Interface: Balance Read/Create

**Responsibility**: Read a customer's current credit account state; lazily create it if absent (QCC-ACCOUNT-004).

| Operation | Input | Output | Behavior |
|---|---|---|---|
| Get balance | customer identity | current balance (integer credit points), or 0 if no account exists | Read-only; no side effects (QCC-API-004) |
| Get or create account | customer identity | account state (creating with balance 0 if absent) | Used internally by credit/debit operations, not directly exposed as a mutating public API |

**Consumers**: REST balance endpoint, customer dashboard, Admin customer-edit tab.

## Interface: Transaction Management (the single business entry point)

**Responsibility**: The only path by which any balance-changing operation may occur (QCC-DATA-007/008). Encapsulates validation, atomicity, row-locking, ledger creation, and balance update for every transaction type.

| Operation | Preconditions | Effect on success | Effect on failure |
|---|---|---|---|
| Credit (generic) | Amount > 0 | Balance increases by amount; one ledger entry with `direction = CREDIT` created; `total_credited` increases | No balance change; no ledger entry; deterministic error returned |
| Debit (generic / redeem) | Amount > 0; amount ≤ current balance; caller authorized | Balance decreases by amount; one ledger entry with `direction = DEBIT` created; `total_debited` increases | No balance change; no ledger entry; deterministic error returned (`INVALID_AMOUNT`, `INSUFFICIENT_BALANCE`, `CUSTOMER_NOT_FOUND`, or authorization error) |
| Admin add | Amount > 0; non-empty reason; caller has admin add permission | Balance increases; `ADMIN_ADD` ledger entry records admin identity + reason | No balance change; no ledger entry |
| Admin remove | Amount > 0; amount ≤ current balance; non-empty reason; caller has admin remove permission | Balance decreases; `ADMIN_REMOVE` ledger entry records admin identity + reason | No balance change; no ledger entry |

**Consumers**: Redemption REST endpoint (debit/redeem), Admin Add/Remove controller, purchase-posting processor (credit).

**Invariant enforced by this interface alone** (no other layer may bypass it): balance never negative (QCC-DATA-001); exactly one ledger entry per successful operation (QCC-DATA-002); full rollback on any failure (QCC-DATA-003); concurrent operations against the same account cannot double-spend (QCC-DATA-006).

## Interface: Ledger Read

**Responsibility**: Query the append-only transaction history for a customer, paginated and ordered (QCC-LEDGER-009).

| Operation | Input | Output | Behavior |
|---|---|---|---|
| List transactions | customer identity, pagination/search criteria | Ordered page of ledger entries | Read-only; never returns another customer's entries (QCC-SEC-004) |
| Get transaction by ID | transaction identifier | Single ledger entry | Read-only |

**Consumers**: Customer dashboard, Admin customer-edit tab.

## Interface: Purchase Processor

**Responsibility**: Convert one qualifying order item into exactly one `PURCHASE` credit transaction, exactly once (QCC-PURCHASE-001/009/010/011).

| Operation | Input | Behavior |
|---|---|---|
| Post purchase | qualifying order/order-item reference | If not already posted for this reference: determine credit amount from quantity, invoke Transaction Management's credit operation with `source_reference` = the order item reference, `source = SYSTEM`. If already posted: no-op (idempotent skip) |

**Consumers**: `OrderCreditPost` observer (triggered on the configured qualifying event — see [research.md](../plan/research.md) §4).

## Data Contracts (field-level, not class-level)

### Credit Balance data contract

- Customer identity (read-only from the caller's perspective)
- Current balance (integer credit points)

### Credit Transaction data contract

- Transaction type (`PURCHASE` | `REDEEM` | `ADMIN_ADD` | `ADMIN_REMOVE`)
- Direction (`CREDIT` | `DEBIT`)
- Amount (integer credit points, positive magnitude)
- Balance before / balance after (integers)
- Message (required for admin types)
- Source (`CUSTOMER` | `API` | `ADMIN` | `SYSTEM`)
- Created-by identity (administrator or system identifier, where applicable)
- Created-at timestamp (server-assigned)

## Authorization Matrix Summary

| Interface | Customer self-access | Integration | Admin |
|---|---|---|---|
| Balance Read | Own balance only | Per integration ACL | Full (any customer) |
| Transaction Management — Debit/Redeem | Not permitted (CLA-007 resolved) | Permitted with integration ACL | Permitted with admin ACL |
| Transaction Management — Admin Add/Remove | Not permitted | Not permitted | Permitted with dedicated add/remove ACL |
| Ledger Read | Own history only | Not applicable to this feature's defined consumers | Full (any customer) |
| Purchase Processor | Not directly invokable by any external caller — system-triggered only | N/A | N/A |
