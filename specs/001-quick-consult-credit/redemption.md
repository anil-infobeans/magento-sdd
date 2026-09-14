# Specification: Credit Redemption / Debit

## Metadata

- **Specification name**: Credit Redemption / Debit
- **Specification identifier**: `QCC-REDEEM`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §3.3.2
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §9.2, §22.2
- **Dependencies**: [account.md](./account.md), [ledger.md](./ledger.md), [api.md](./api.md), [idempotency-concurrency.md](./idempotency-concurrency.md)
- **Impacted existing specifications**: None found

## Purpose

Define redemption (debit) as an atomic, measurable state-changing business operation, including all validation, rejection, and response behavior.

## Scope

**In scope**: Preconditions for a valid redemption, the atomic decrement sequence, rejection behavior for insufficient balance and invalid input, response content.

**Out of scope**: The transport/authentication mechanics of the API that invokes redemption (see [api.md](./api.md)); concurrency locking mechanics (see [idempotency-concurrency.md](./idempotency-concurrency.md), which this specification depends on for the concurrent-redemption guarantee).

**Actors**: Authorized external integration, authorized administrator (where administratively permitted to invoke redemption on behalf of a consultation event), Magento system/process.

**System boundaries**: Redemption is always invoked through the transaction service contract; no caller updates balance or ledger tables directly (Architecture §12, §13).

**External dependencies**: Customer credit account (see [account.md](./account.md)).

## Definitions

- **Redemption / debit**: A state-changing operation that reduces a customer's available balance by a requested amount in exchange for a consumed consultation service.
- **Available balance**: The current spendable amount as defined in [account.md](./account.md).

## Actors

- **Authorized external integration**: The typical caller of redemption, on behalf of a completed consultation.
- **Authorized administrator**: May be authorized to trigger equivalent operations through the admin "Remove Credit" action (see [admin.md](./admin.md), which is a distinct transaction type from `REDEEM`).
- **Magento system/process**: Executes the atomic redemption operation.

## Functional Requirements

### Preconditions

- **QCC-REDEEM-001**: THE SYSTEM SHALL require that the targeted customer identifier resolves to an existing Magento customer before processing a redemption. *(Source: SRS §3.3.2 step 1)*
- **QCC-REDEEM-002**: THE SYSTEM SHALL require the requested redemption amount to be strictly greater than zero. *(Source: SRS §4.2 "If amount <= 0 -> Return INVALID_AMOUNT")*
- **QCC-REDEEM-003**: THE SYSTEM SHALL require the requested redemption amount to be less than or equal to the customer's current available balance. *(Source: SRS §3.3.2 step 2)*

### Successful Redemption

- **QCC-REDEEM-004**: WHEN a redemption request satisfies all preconditions, THE SYSTEM SHALL reduce the available balance by exactly the requested amount. *(Source: SRS §3.3.2 step 4)*
- **QCC-REDEEM-005**: WHEN a redemption request succeeds, THE SYSTEM SHALL increase the customer's total redeemed (total debited) amount by exactly the requested amount. *(Source: SRS §3.2, §3.3.2 step 4)*
- **QCC-REDEEM-006**: WHEN a redemption request succeeds, THE SYSTEM SHALL create exactly one `REDEEM`/`DEBIT` ledger transaction recording `balance_before`, `balance_after`, amount, and the supplied reference/message. *(Source: SRS §3.3.2 step 4-5; Architecture §7.2)*
- **QCC-REDEEM-007**: WHEN a redemption request succeeds, THE SYSTEM SHALL return the resulting available balance and the applicable reference (e.g., consultation reference/ticket) to the caller. *(Source: SRS §3.3.2 step 5, §4.2 response payload)*

### Rejection Behavior

- **QCC-REDEEM-008**: IF the requested redemption amount exceeds the current available balance, THEN THE SYSTEM SHALL reject the transaction with an `INSUFFICIENT_BALANCE` error, leave the available balance unchanged, and SHALL NOT create a debit ledger transaction. *(Source: SRS §3.3.2 step 3, §4.2, §7.2)*
- **QCC-REDEEM-009**: IF the requested redemption amount is zero or negative, THEN THE SYSTEM SHALL reject the transaction with an `INVALID_AMOUNT` error and SHALL NOT create a ledger transaction. *(Source: SRS §4.2)*
- **QCC-REDEEM-010**: IF the targeted customer does not exist, THEN THE SYSTEM SHALL reject the transaction with a `CUSTOMER_NOT_FOUND` error and SHALL NOT create a ledger transaction. *(Source: SRS §4.1, §4.2)*
- **QCC-REDEEM-011**: IF the caller is not authorized to perform redemption for the specified customer, THEN THE SYSTEM SHALL reject the transaction with a uniform authorization error, regardless of whether the specified customer exists, and SHALL NOT create a ledger transaction, return `CUSTOMER_NOT_FOUND`, or disclose balance information in that case. THE SYSTEM SHALL evaluate this authorization check before the customer-existence check in QCC-REDEEM-001/QCC-REDEEM-010. *(Source: SRS §7.1; see [security.md](./security.md) QCC-SEC-008; Clarified 2026-09-12, resolves AMB-005)*
- **QCC-REDEEM-012**: IF a redemption request is a duplicate of a previously processed request (per the idempotency rules in [idempotency-concurrency.md](./idempotency-concurrency.md)), THEN THE SYSTEM SHALL NOT create a second debit ledger transaction and SHALL return a deterministic response consistent with the original transaction's outcome. *(Source: SRS §7.2; Architecture §15)*
- **QCC-REDEEM-013**: WHEN two or more redemption requests for the same customer are processed concurrently, THE SYSTEM SHALL ensure at most one request that would overdraw the balance succeeds, per the concurrency rules in [idempotency-concurrency.md](./idempotency-concurrency.md) QCC-CONC-003. *(Source: Architecture §16, §24 "Concurrent redeems")*

## Acceptance Criteria

1. **Given** a $100.00 balance, **When** a $25.00 redemption is requested, **Then** the balance becomes $75.00, total debited increases by $25.00, and exactly one `REDEEM` ledger transaction is created. *(Validates QCC-REDEEM-004, QCC-REDEEM-005, QCC-REDEEM-006)*
2. **Given** a $100.00 balance, **When** a $120.00 redemption is requested, **Then** the request is rejected with `INSUFFICIENT_BALANCE`, the balance remains $100.00, and no debit ledger transaction is created. *(Validates QCC-REDEEM-008)*
3. **Given** any balance, **When** a redemption for $0.00 or a negative amount is requested, **Then** the request is rejected with `INVALID_AMOUNT` and no ledger transaction is created. *(Validates QCC-REDEEM-009)*
4. **Given** a non-existent customer identifier, **When** a redemption is requested, **Then** the request is rejected with `CUSTOMER_NOT_FOUND`. *(Validates QCC-REDEEM-010)*
5. **Given** a caller without authorization for the target customer, **When** a redemption is requested, **Then** the request is denied with a uniform authorization error without disclosing balance information or whether the target customer exists. *(Validates QCC-REDEEM-011)*
6. **Given** a $100.00 balance, **When** two concurrent $80.00 redemption requests are submitted, **Then** at most one succeeds, the final balance is either $20.00 or $100.00, and no negative balance or duplicate successful debit occurs. *(Validates QCC-REDEEM-013)*
7. **Given** a successful redemption already processed under a specific idempotency key, **When** the same request is replayed with the same key, **Then** no second debit occurs and the response reflects the original outcome. *(Validates QCC-REDEEM-012)*

## Traceability

See [traceability.md](./traceability.md). Contributes `QCC-REDEEM-001`…`QCC-REDEEM-013`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability) |
