# Specification: Credit Redemption

**Specification**: quick-consult-credit / credit-redemption
**Version**: 1.3
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-REDEEM

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §3.3.2, §4.2; Technical Architecture §9.2, §24, §28 | New specification |
| 1.1 | Clarified QCC-REDEEM-009: authorized callers are limited to external integrations and Magento admins; customers cannot self-redeem | /speckit-clarify session 2026-09-15 (CLA-007) | Narrows the definition of "authorized caller" |
| 1.2 | Relocated to `functional/` and updated cross-reference links | Constitution v1.4.0 Principle XIII, 2026-09-16 | Structural only; no requirement content changed |
| 1.3 | Resolved QCC-REDEEM-010: no dedicated replay/idempotency mechanism exists; a replayed request MAY double-debit if each attempt individually passes validation | /speckit-clarify session 2026-09-16 (CLA-004) | Narrows the guarantee previously implied by QCC-REDEEM-010 |

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

**Statement (EARS)**: If the requesting caller is not an authorized external integration system or an authorized Magento admin, the system shall reject the request with an authorization error and shall not evaluate or disclose the customer's balance. A directly authenticated customer session is not, by itself, an authorized caller for redemption (resolved via [clarifications.md](../clarifications.md) CLA-007, resolved 2026-09-15).

**Source**: SRS §4.2; Technical Architecture §11.3; Resolved clarification (CLA-007)

**Acceptance Criteria**:
- AC-1: Given an unauthorized caller (including an authenticated customer acting on their own behalf), when a redemption is submitted, then the request is rejected with an authorization error, and the response does not disclose the customer's balance.

### QCC-REDEEM-010 — Replayed request is not guaranteed to be deduplicated (resolved)

**Statement (EARS)**: The system does not implement a dedicated request-idempotency/deduplication mechanism for redemption requests. When a redemption request is replayed or retried after the original has already been successfully processed, the system shall validate the replayed request independently against the balance at that time; the replayed request MAY be accepted and produce an additional debit and REDEEM ledger entry if it individually passes standard validation (QCC-REDEEM-001 through QCC-REDEEM-008). This explicitly narrows the general duplicate-prevention expectation in SRS §7.2 for this endpoint. It does not affect or weaken the balance-never-negative guarantee (QCC-REDEEM-011, QCC-REDEEM-012) or purchase-posting idempotency (see [credit-purchase-posting.md](./credit-purchase-posting.md)).

**Source**: SRS §7.2 (narrowed); Technical Architecture §28; Resolved clarification (see [clarifications.md](../clarifications.md) CLA-004, resolved 2026-09-16 via /speckit-clarify session)

**Acceptance Criteria**:
- AC-1: Given a redemption request that already succeeded, when the same logical request is resubmitted as a separate request, then it is validated independently and MAY be accepted (resulting in an additional debit and a new REDEEM ledger entry) if it individually passes validation; no dedicated mechanism prevents this.
- AC-2: Given any individual redemption request (original or replayed), when it is processed, then standard validation (QCC-REDEEM-001 through QCC-REDEEM-008) still applies without exception, and the balance never becomes negative.

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
- [data-integrity-and-concurrency.md](../non-functional/data-integrity-and-concurrency.md) — concurrency-serialization requirements underlying QCC-REDEEM-011/012.
- [clarifications.md](../clarifications.md) — CLA-004 (no dedicated idempotency mechanism, resolved), CLA-007 (customer self-redemption authorization, resolved).
