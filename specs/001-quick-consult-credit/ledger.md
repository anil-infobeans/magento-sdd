# Specification: Immutable Credit Transaction Ledger

## Metadata

- **Specification name**: Immutable Credit Transaction Ledger
- **Specification identifier**: `QCC-LEDGER`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §3.2, §6.1
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §6, §7.2, §9
- **Dependencies**: [account.md](./account.md)
- **Impacted existing specifications**: None found

## Purpose

Define the measurable structure, immutability guarantees, and query behavior of the append-only transaction ledger that serves as the audit source of truth for all Quick Consult Credit balance movements.

## Scope

**In scope**: Ledger record structure, required fields, transaction type/direction semantics, immutability rules, correction-via-compensating-transaction policy, query/pagination support for downstream consumers.

**Out of scope**: The business rules that decide *when* a ledger entry is created (see [purchase.md](./purchase.md), [redemption.md](./redemption.md), [admin.md](./admin.md)).

**Actors**: Magento system/process (writer), authorized administrator (reader), registered customer (reader, own records only), authorized external integration (reader/writer via [api.md](./api.md)).

**System boundaries**: The ledger is written to exclusively through the service contracts described in [idempotency-concurrency.md](./idempotency-concurrency.md) and the domain specifications that reference it; no actor writes to the ledger directly.

**External dependencies**: None beyond the customer credit account (see [account.md](./account.md)).

## Definitions

- **Ledger transaction / ledger entry**: A single immutable record of one balance movement.
- **Direction**: Whether a transaction increases (`CREDIT`) or decreases (`DEBIT`) the balance.
- **Transaction type**: The business reason for the movement — `PURCHASE`, `REDEEM`, `ADMIN_ADD`, `ADMIN_REMOVE`.
- **Compensating transaction**: A new, forward-only ledger entry that corrects the practical effect of a prior entry without modifying or deleting it.

## Actors

- **Magento system/process**: The only writer of ledger entries, via service contracts.
- **Registered customer**: Reader of their own ledger entries only (see [customer-dashboard.md](./customer-dashboard.md)).
- **Authorized administrator**: Reader of any customer's ledger entries, and indirect cause of `ADMIN_ADD`/`ADMIN_REMOVE` entries (see [admin.md](./admin.md)).
- **Authorized external integration**: Indirect cause of `PURCHASE`/`REDEEM` entries via [purchase.md](./purchase.md) and [api.md](./api.md).

## Functional Requirements

### Structure

- **QCC-LEDGER-001**: THE SYSTEM SHALL persist every successful balance-changing operation as a new, distinct ledger record before the operation is considered complete. *(Source: SRS §3.3.1 step 5, §3.3.2 step 4; Architecture §9)*
- **QCC-LEDGER-002**: THE SYSTEM SHALL record, at minimum, the following attributes on each ledger transaction: a unique transaction identifier, customer identifier, transaction type, amount (positive magnitude), direction (`CREDIT`/`DEBIT`), balance before the movement, balance after the movement, account currency, reference type, reference identifier, idempotency key (where applicable to externally initiated writes), message/reason, source (e.g., `CUSTOMER`, `API`, `ADMIN`, `SYSTEM`), actor/creator identity where applicable, and a creation timestamp. *(Source: SRS §6.1; Architecture §7.2)*
- **QCC-LEDGER-003**: THE SYSTEM SHALL restrict `transaction_type` to the enumerated values `PURCHASE`, `REDEEM`, `ADMIN_ADD`, `ADMIN_REMOVE`. *(Source: SRS §3.2, §6.1; Architecture §7.2)*
- **QCC-LEDGER-004**: THE SYSTEM SHALL set `direction` to `CREDIT` for `PURCHASE` and `ADMIN_ADD` transaction types, and to `DEBIT` for `REDEEM` and `ADMIN_REMOVE` transaction types. *(Source: Architecture §7.2)*
- **QCC-LEDGER-005**: THE SYSTEM SHALL record `amount` as a positive magnitude regardless of direction; the sign/effect of the movement is expressed exclusively through `direction`. *(Source: Architecture §7.2 "Positive magnitude of the movement")*
- **QCC-LEDGER-006**: THE SYSTEM SHALL compute and store `balance_before` and `balance_after` for each ledger transaction such that `balance_after` equals `balance_before` plus the signed effect of `amount`/`direction`, and equals the account's resulting available balance at commit time. *(Source: Architecture §7.2, §9)*

### Immutability

- **QCC-LEDGER-007**: THE SYSTEM SHALL NOT modify the value of any field on an existing ledger transaction as part of normal purchase, redemption, or admin-adjustment operations. *(Source: SRS §6.1 "append-only"; Architecture §6)*
- **QCC-LEDGER-008**: THE SYSTEM SHALL NOT delete an existing ledger transaction as part of normal purchase, redemption, or admin-adjustment operations. *(Source: SRS §6.1 "append-only")*
- **QCC-LEDGER-009**: IF a correction to a previously posted transaction is ever required, THEN THE SYSTEM SHALL represent the correction as a new, explicit compensating ledger transaction referencing the original transaction, rather than mutating or removing the original record. *(Source: Architecture §25 "Data correction scripts... should create explicit adjustment ledger entries rather than silently editing balances")*

### Query & Read Behavior

- **QCC-LEDGER-010**: THE SYSTEM SHALL support retrieval of a customer's ledger transactions ordered by creation time, with the most recent transaction available first for display purposes. *(Source: SRS §5.1; Architecture §8 "getList(customerId, searchCriteria)")*
- **QCC-LEDGER-011**: THE SYSTEM SHALL support retrieval of a single ledger transaction by its unique transaction identifier. *(Source: Architecture §8 "getById(transactionId)")*
- **QCC-LEDGER-012**: THE SYSTEM SHALL support paginated retrieval of a customer's ledger transactions to bound the size of any single response (see [customer-dashboard.md](./customer-dashboard.md), [admin.md](./admin.md) for page-size specifics). *(Source: Architecture §12 "Use pagination for transaction history")*

## Acceptance Criteria

1. **Given** a successful $100 purchase, **When** the ledger is queried, **Then** exactly one `PURCHASE`/`CREDIT` transaction exists with `balance_before` = prior balance and `balance_after` = prior balance + 100.00. *(Validates QCC-LEDGER-001, QCC-LEDGER-004, QCC-LEDGER-006)*
2. **Given** an existing ledger transaction, **When** any subsequent normal operation for that customer is performed, **Then** the existing transaction's stored field values remain unchanged and the record still exists. *(Validates QCC-LEDGER-007, QCC-LEDGER-008)*
3. **Given** a need to correct a previously posted transaction, **When** the correction is applied, **Then** a new compensating transaction is created referencing the original, and the original transaction is unchanged. *(Validates QCC-LEDGER-009)*
4. **Given** a customer with 45 ledger transactions and a page size of 20, **When** the second page of history is requested, **Then** exactly 20 distinct transactions are returned, none of which appeared on page one. *(Validates QCC-LEDGER-010, QCC-LEDGER-012)*

## Traceability

See [traceability.md](./traceability.md). Contributes `QCC-LEDGER-001`…`QCC-LEDGER-012`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability) |
