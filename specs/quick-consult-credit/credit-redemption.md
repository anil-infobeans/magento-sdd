# Specification: Credit Redemption

**Specification**: quick-consult-credit / credit-redemption
**Version**: 1.1
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-REDEEM

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §3.3.2, §4.2; Technical Architecture §9.2, §24, §28 | New specification |
| 1.1 | Clarified QCC-REDEEM-009: authorized callers are limited to external integrations and Magento admins; customers cannot self-redeem | /speckit-clarify session 2026-09-15 (CLA-007) | Narrows the definition of "authorized caller" |

## Purpose

Defines valid and invalid redemption behavior, including the exact state transition for a successful redemption, deterministic error behavior for rejected redemptions, and protection against concurrent over-redemption.

## Requirements

### QCC-REDEEM-001 — Valid redemption preconditions

**Statement (EARS)**: The system shall accept a redemption request only if all of the following hold: the requested amount is greater than zero, the requested amount is less than or equal to the customer's current available balance, the customer exists, and the requesting caller is authorized to redeem for that customer.

**Source**: SRS §3.3.2, §4.2; Technical Architecture §9.2

**Acceptance Criteria**:
- AC-1: Given all four preconditions hold, when a redemption request is submitted, then the request is accepted for processing.
- AC-2: Given any one precondition fails, when a redemption request is submitted, then the request is rejected and none of the other preconditions' checks are relied upon to permit the operation.

### QCC-REDEEM-002 — Successful redemption state transition

**Statement (EARS)**: When a redemption request is accepted, the system shall set the customer's new balance equal to the previous balance minus the requested amount, and shall create exactly one REDEEM ledger entry recording that movement.

**Source**: SRS §3.3.2; Technical Architecture §9.2

**Acceptance Criteria**:
- AC-1: Given an accepted redemption of amount A against previous balance B, when the operation completes, then the new balance is B − A and exactly one REDEEM ledger entry exists with `balance_before = B` and `balance_after = B − A`.

### QCC-REDEEM-003 — Rejected redemption leaves state unchanged

**Statement (EARS)**: When a redemption request is rejected, the system shall not change the customer's balance, shall not create a successful debit ledger entry, and shall return a deterministic business error.

**Source**: SRS §4.2, §7.2; Technical Architecture §17

**Acceptance Criteria**:
- AC-1: Given a rejected redemption request, when the response is inspected, then the balance is unchanged, no new REDEEM ledger entry exists, and a specific business error code is returned.

### QCC-REDEEM-004 — Zero amount rejected

**Statement (EARS)**: If the requested redemption amount equals zero, the system shall reject the request with the `INVALID_AMOUNT` error.

**Source**: SRS §4.2

**Acceptance Criteria**:
- AC-1: Given amount = 0, when submitted, then the request is rejected with `INVALID_AMOUNT`, balance unchanged, no ledger entry created.

### QCC-REDEEM-005 — Negative amount rejected

**Statement (EARS)**: If the requested redemption amount is less than zero, the system shall reject the request with the `INVALID_AMOUNT` error.

**Source**: SRS §4.2

**Acceptance Criteria**:
- AC-1: Given a negative amount, when submitted, then the request is rejected with `INVALID_AMOUNT`, balance unchanged, no ledger entry created.

### QCC-REDEEM-006 — Amount exceeding balance rejected

**Statement (EARS)**: If the requested redemption amount is greater than the customer's current available balance, the system shall reject the request with the `INSUFFICIENT_BALANCE` error.

**Source**: SRS §3.3.2, §4.2; Technical Architecture §9.2

**Acceptance Criteria**:
- AC-1: Given balance 100 and a requested amount of 120, when submitted, then the request is rejected with `INSUFFICIENT_BALANCE`, balance remains 100, no ledger entry created.

### QCC-REDEEM-007 — Exact-balance redemption succeeds

**Statement (EARS)**: If the requested redemption amount equals the customer's current available balance exactly, the system shall accept the request.

**Source**: SRS §3.3.2 ("greater than or equal to")

**Acceptance Criteria**:
- AC-1: Given balance 100 and a requested amount of 100, when submitted, then the request is accepted and the resulting balance is 0.

### QCC-REDEEM-008 — Unknown customer rejected

**Statement (EARS)**: If the referenced customer does not exist, the system shall reject the request with the `CUSTOMER_NOT_FOUND` error.

**Source**: SRS §4.1, §4.2

**Acceptance Criteria**:
- AC-1: Given a customer identifier with no matching account/customer, when a redemption is submitted, then the request is rejected with `CUSTOMER_NOT_FOUND`.

### QCC-REDEEM-009 — Unauthorized caller rejected

**Statement (EARS)**: If the requesting caller is not an authorized external integration system or an authorized Magento admin, the system shall reject the request with an authorization error and shall not evaluate or disclose the customer's balance. A directly authenticated customer session is not, by itself, an authorized caller for redemption (resolved via [clarifications.md](./clarifications.md) CLA-007, resolved 2026-09-15).

**Source**: SRS §4.2; Technical Architecture §11.3; Resolved clarification (CLA-007)

**Acceptance Criteria**:
- AC-1: Given an unauthorized caller (including an authenticated customer acting on their own behalf), when a redemption is submitted, then the request is rejected with an authorization error, and the response does not disclose the customer's balance.

### QCC-REDEEM-010 — Duplicate/replayed request does not double-debit

**Statement (EARS)**: When a redemption request is replayed or retried for a request that has already been successfully processed, the system shall not apply a second debit for that same logical request.

**Source**: SRS §7.2; Technical Architecture §28; Derived clarification (see [clarifications.md](./clarifications.md) CLA-004 for the exact replay-identity contract)

**Acceptance Criteria**:
- AC-1: Given a redemption request that already succeeded, when the same logical request is resubmitted, then the balance is not debited a second time and the ledger contains only the original REDEEM entry for that logical request.

### QCC-REDEEM-011 — Concurrent redemption safety

**Statement (EARS)**: The system shall not allow two or more concurrent redemption requests against the same account to collectively debit more than the balance available at the time the first request was accepted.

**Source**: SRS §3.3.2 (atomic operation); Technical Architecture §9.2, §24 ("At most one succeeds; final balance never negative")

**Acceptance Criteria**:
- AC-1: Given balance 100 and two concurrent redemption requests each for 80, when both are processed, then at most one is accepted, and the resulting balance is never negative.

### QCC-REDEEM-012 — Concurrent redemptions cannot both consume the same credit

**Statement (EARS)**: The system shall logically serialize concurrent redemption requests against the same account such that accepted transactions cannot consume the same portion of the available balance twice.

**Source**: Technical Architecture §9.2 ("Two parallel redeems must not both succeed against the same pre-transaction balance")

**Acceptance Criteria**:
- AC-1: Given N concurrent redemption requests whose combined amount exceeds the current balance, when all are processed, then the set of accepted requests' combined amount does not exceed the balance that existed before any of them were processed.

## Related Specifications

- [credit-ledger.md](./credit-ledger.md) — REDEEM entry structure.
- [credit-rest-api.md](./credit-rest-api.md) — API contract for the create-transaction (redeem) endpoint.
- [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) — concurrency-serialization requirements underlying QCC-REDEEM-011/012.
- [clarifications.md](./clarifications.md) — CLA-004 (idempotency-key contract), CLA-007 (customer self-redemption authorization).
