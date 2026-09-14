# Specification: Idempotency, Atomicity & Concurrency

## Metadata

- **Specification name**: Idempotency, Atomicity & Concurrency
- **Specification identifier**: `QCC-IDEMP` / `QCC-CONC`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §7.2, §6.2
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §9, §15, §16, §28
- **Dependencies**: [account.md](./account.md), [ledger.md](./ledger.md), [purchase.md](./purchase.md), [redemption.md](./redemption.md), [admin.md](./admin.md), [api.md](./api.md)
- **Impacted existing specifications**: None found

## Purpose

Define the measurable atomicity, concurrency-safety, and duplicate-prevention guarantees that every Quick Consult Credit state-changing operation must satisfy, independent of which business operation (purchase, redeem, admin add/remove) triggers it.

## Scope

**In scope**: Atomic commit of balance + statistic + ledger changes; the customer credit account as the concurrency synchronization point; purchase-posting idempotency; externally initiated API idempotency; behavior for repeated/conflicting idempotency keys.

**Out of scope**: The specific business validation rules of each operation (see [purchase.md](./purchase.md), [redemption.md](./redemption.md), [admin.md](./admin.md)).

**Actors**: Magento system/process (enforces atomicity/locking), authorized external integration (supplies idempotency keys), registered customer (indirect beneficiary via purchase idempotency).

**System boundaries**: These requirements apply uniformly to every operation that changes a customer's balance, regardless of caller.

**External dependencies**: MySQL/MariaDB transaction and locking support (Architecture §1, §16).

## Definitions

- **Atomic operation**: A set of persistence steps (balance update, cumulative statistic update, ledger insert, idempotency-key persistence where applicable) that either all succeed together or all fail together, with no partially visible intermediate state.
- **Synchronization point**: The customer credit account row, which must be locked before its balance is read for the purpose of computing a new balance, to prevent concurrent operations from computing conflicting new balances from the same stale read.
- **Idempotency key**: A caller-supplied or system-derived deterministic value used to detect and safely handle a replayed state-changing request.

## Actors

- **Magento system/process**: Executes all state-changing operations under a database transaction with row-level locking.

## Functional Requirements

### Atomicity

- **QCC-CONC-001**: THE SYSTEM SHALL treat the customer credit account row as the synchronization point for every state-changing operation on that customer's balance. *(Source: Architecture §16 "The balance row should be treated as the synchronization point for a customer")*
- **QCC-CONC-002**: THE SYSTEM SHALL commit the balance change, the corresponding cumulative statistic change (total credited or total debited), the new ledger transaction, and any idempotency-key record (where applicable) as a single atomic database transaction. *(Source: SRS §6.2 "Enforce database transactions... to ensure balance modifications and ledger writes are committed atomically"; Architecture §9.1, §9.2, §15)*
- **QCC-CONC-003**: IF any required persistence step within a state-changing operation fails, THEN THE SYSTEM SHALL roll back the entire operation such that no partial balance change, cumulative-statistic change, or ledger entry is committed or observable. *(Source: Architecture §9.1 "If any step fails, roll back the entire operation"; §17 "Could not save transaction -> Roll back balance change and log critical error")*
- **QCC-CONC-004**: THE SYSTEM SHALL NOT allow a state in which a ledger entry exists without its corresponding balance update, or a balance update exists without its corresponding ledger entry. *(Source: Architecture §16 risk table)*

### Concurrency

- **QCC-CONC-005**: THE SYSTEM SHALL lock the customer credit account row for update before reading the current balance as part of any state-changing operation, and SHALL NOT compute a new balance from a non-locked read followed by a later update. *(Source: Architecture §9.2 "The implementation must not use a non-locked read followed by a later update"; §16)*
- **QCC-CONC-006**: WHEN two or more state-changing requests for the same customer are submitted concurrently, THE SYSTEM SHALL serialize their effect on the balance such that each request observes the committed result of any prior request that has already committed, and no two requests compute conflicting new balances from the same pre-transaction balance. *(Source: Architecture §9.2, §16)*
- **QCC-CONC-007**: GIVEN a customer with a starting balance of $100.00, WHEN two concurrent redemption requests of $80.00 each are submitted, THEN THE SYSTEM SHALL ensure: no negative balance results; at most one request succeeds; the final balance is deterministically either $20.00 (one success) or $100.00 (both rejected, e.g., due to an unrelated failure) depending on which request is applied; no partial transaction state exists; and no duplicate successful debit is recorded. *(Source: Architecture §24 "Concurrent redeems"; user-specified concurrency scenario)*

### Purchase Idempotency

- **QCC-IDEMP-001**: THE SYSTEM SHALL derive a deterministic purchase reference for each qualifying Quick Consult Credit purchase (e.g., order item identifier) and SHALL ensure that reference is protected by a database-level uniqueness constraint against duplicate posting. *(Source: Architecture §15 "A purchase should have a deterministic reference... a successful posting must be unique"; see [purchase.md](./purchase.md) QCC-PURCHASE-007/009)*
- **QCC-IDEMP-002**: WHEN the same qualifying purchase event is observed more than once, THE SYSTEM SHALL post credit at most once for the associated purchase reference. *(Source: Architecture §10, §24 "Duplicate purchase event")*

### External API Idempotency

- **QCC-IDEMP-003**: WHERE the module is configured to require idempotency keys for externally initiated state-changing requests (default: required, per Architecture §19), THE SYSTEM SHALL require the caller to supply an `idempotency_key` and SHALL persist that key atomically with the resulting transaction. *(Source: Architecture §15, §19)*
- **QCC-IDEMP-004**: WHEN a state-changing request is received with an `idempotency_key` matching a previously completed request, THE SYSTEM SHALL NOT perform a second balance movement and SHALL return the result of the original transaction (or a deterministic duplicate-response indicator) rather than executing the operation again. *(Source: Architecture §11.3, §15 "Duplicate idempotency key -> Return original transaction or a deterministic duplicate response")*
- **QCC-IDEMP-005**: THE SYSTEM SHALL enforce idempotency-key uniqueness using a database-level uniqueness constraint, and SHALL NOT rely solely on an application-level "check then insert" sequence. *(Source: Architecture §15 "Do not rely on application-level 'check then insert' without a database uniqueness constraint. The database constraint is the final protection against race conditions")*
- **QCC-IDEMP-006**: IF a request is received with an `idempotency_key` that matches a previously completed request but with a materially different payload (e.g., a different amount or customer), THEN the system's response behavior is undefined by the source documents and is recorded as an open decision in [ambiguity-register.md](./ambiguity-register.md) (**AMB-006**). *(Source: absence of guidance in SRS/Architecture on conflicting-payload replay)*

## Acceptance Criteria

1. **Given** a simulated persistence failure after the ledger insert but before the balance-column update within one operation, **When** the operation is inspected post-failure, **Then** neither the ledger insert nor the balance change is visible (full rollback). *(Validates QCC-CONC-002, QCC-CONC-003, QCC-CONC-004)*
2. **Given** a customer with a $100.00 balance, **When** two concurrent $80.00 redemption requests are submitted, **Then** exactly one succeeds (or, in the case of an unrelated failure, both are safely rejected), the final balance is never negative, and exactly one or zero `REDEEM` ledger rows are created — never two. *(Validates QCC-CONC-005, QCC-CONC-006, QCC-CONC-007)*
3. **Given** the same qualifying purchase event delivered twice, **When** both deliveries are processed, **Then** only one `PURCHASE` ledger row exists for that purchase reference. *(Validates QCC-IDEMP-001, QCC-IDEMP-002)*
4. **Given** a transaction-creation API request with a previously used `idempotency_key`, **When** the request is replayed, **Then** no second balance movement occurs and the response is deterministic. *(Validates QCC-IDEMP-003, QCC-IDEMP-004, QCC-IDEMP-005)*

## Traceability

See [traceability.md](./traceability.md). Contributes `QCC-CONC-001`…`QCC-CONC-007` and `QCC-IDEMP-001`…`QCC-IDEMP-006`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability) |
