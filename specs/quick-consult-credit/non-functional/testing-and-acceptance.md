# Specification: Testing, Acceptance, and Non-Functional Requirements

**Specification**: quick-consult-credit / testing-and-acceptance
**Version**: 1.2
**Status**: Draft
**Type**: Normative (NFRs) + Architectural (test-strategy guidance)
**Requirement ID prefix**: QCC-NFR

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §7.2; Technical Architecture §23, §24, §28 | New specification |
| 1.1 | Relocated to `non-functional/` and updated cross-reference links | Constitution v1.4.0 Principle XIII, 2026-09-16 | Structural only; no requirement content changed |
| 1.2 | Resolved QCC-NFR-001: no formal performance/throughput SLA is required for this release (explicit decision, not a pending TBD); removed blocking sign-off language | /speckit-clarify session 2026-09-16 (CLA-012) | Removes a previously blocking implementation-sign-off item |

## Purpose

Converts qualitative expectations into measurable non-functional requirements where the source documents permit, defines the required test-layer coverage, and consolidates acceptance scenarios referenced across the other sub-specifications.

## Non-Functional Requirements

### QCC-NFR-001 — No formal performance SLA for this release (resolved)

**Statement (EARS)**: No formal performance or throughput SLA is required for this release. This is an explicit specification decision, not a pending/undefined item, and does not relax any correctness guarantee (e.g., [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) QCC-DATA-001, QCC-DATA-006).

**Source**: Resolved clarification; see [clarifications.md](../clarifications.md) CLA-012 (resolved 2026-09-16 via /speckit-clarify session)

**Acceptance Criteria**:
- AC-1: Given the absence of a formal performance SLA, when implementation sign-off is evaluated, then performance is not a blocking criterion for sign-off.

### QCC-NFR-002 — Reliability: atomicity and rollback

**Statement (EARS)**: The system shall guarantee that every balance-changing operation either fully commits (balance and ledger both updated) or fully rolls back (neither updated), with no partial state observable externally.

**Source**: SRS §6.2; Technical Architecture §9.1, §9.2

**Acceptance Criteria**:
- AC-1: Given a simulated failure mid-operation, when the account and ledger are inspected afterward, then both reflect the pre-operation state exactly (duplicated from [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) QCC-DATA-003 for NFR-level test coverage).

### QCC-NFR-003 — Recoverability from persistence failure

**Statement (EARS)**: If a persistence failure occurs during a balance-changing operation, the system shall log the failure with sufficient context to diagnose and retry the operation, without leaving the account or ledger in an inconsistent state.

**Source**: Technical Architecture §17, §18

**Acceptance Criteria**:
- AC-1: Given a persistence failure during a credit or debit operation, when the failure is logged, then it includes customer identifier, transaction type, amount, reference, and exception summary, and the account/ledger remain consistent.

### QCC-NFR-004 — Maintainability: shared service-contract reuse

**Statement (EARS)**: The system shall implement all balance-changing business rules once, behind the shared service-contract boundary, and shall not duplicate those rules in the customer UI, Admin UI, or REST API layers.

**Source**: Technical Architecture §4, §8, §20

**Acceptance Criteria**:
- AC-1: Given the customer redemption path (API) and the admin adjustment path (Admin UI), when their validation/atomicity behavior is compared, then both rely on the same underlying business rules rather than independently reimplemented logic.

### QCC-NFR-005 — Compatibility with existing Magento behavior

**Statement (EARS)**: The system shall preserve existing Magento checkout, customer account, order-processing, and admin customer-management behavior for all products and customers not involved with Quick Consult Credit.

**Source**: SRS §2.1; Technical Architecture §2

**Acceptance Criteria**:
- AC-1: Given existing checkout, customer account, order-processing, and admin customer-management regression suites, when they are run after this feature is deployed, then all previously passing cases continue to pass.

## Test Layer Coverage (architectural guidance)

| Test layer | Coverage |
|---|---|
| Unit | Validators, transaction-type rules, balance calculations, amount/precision handling |
| Integration | Database transaction behavior, row locking, ledger + balance atomicity, repository/collection operations |
| API | Authentication, authorization, payload validation, success/error responses, duplicate-request behavior |
| Magento functional | Successful purchase posts correct credit; failed/cancelled/non-qualifying orders do not post; customer dashboard; admin adjustments |
| Regression | Checkout, customer account, admin customer edit, order processing, existing integrations |
| Security | Cross-customer access attempts, unauthorized admin/API attempts, ACL enforcement |
| Performance | Not applicable — no formal SLA is required for this release (QCC-NFR-001) |

## Consolidated Acceptance Test Scenarios

### Purchase

- Successful qualifying purchase (QCC-PURCHASE-001, QCC-PURCHASE-002)
- Quantity-based credit amount (QCC-PURCHASE-002)
- Pending order (QCC-PURCHASE-005)
- Failed payment (QCC-PURCHASE-006)
- Cancelled/non-qualifying order (QCC-PURCHASE-007, QCC-PURCHASE-008)
- Duplicate purchase processing (QCC-PURCHASE-001, QCC-PURCHASE-009, QCC-PURCHASE-010)
- Retry after processing failure (QCC-PURCHASE-011; QCC-DATA-003)

### Redemption

- Valid redemption (QCC-REDEEM-001, QCC-REDEEM-002)
- Exact-balance redemption (QCC-REDEEM-007)
- Insufficient balance (QCC-REDEEM-006)
- Zero amount (QCC-REDEEM-004)
- Negative amount (QCC-REDEEM-005)
- Unknown customer (QCC-REDEEM-008)
- Unauthorized request (QCC-REDEEM-009)
- Duplicate request — not deduplicated; MAY double-debit if independently validated (QCC-REDEEM-010)
- Concurrent redemptions (QCC-REDEEM-011, QCC-REDEEM-012)

### Admin

- Authorized add (QCC-ADMIN-003)
- Authorized remove (QCC-ADMIN-004)
- Insufficient admin removal (QCC-ADMIN-004 AC-2)
- Zero/negative adjustment (QCC-ADMIN-003 AC-2, QCC-ADMIN-004 AC-3)
- Missing reason (QCC-ADMIN-005)
- Audit identity (QCC-ADMIN-006)
- Unauthorized administrator (QCC-ADMIN-009)

### Customer UI

- Own balance (QCC-CUSTOMER-002)
- Own transaction history (QCC-CUSTOMER-003)
- Pagination (QCC-CUSTOMER-004)
- Unauthorized access to another customer (QCC-CUSTOMER-006)

### API

- Authentication (QCC-API-002)
- Authorization (QCC-API-009, QCC-API-011)
- Request validation (QCC-API-006)
- Success responses (QCC-API-001, QCC-API-005, QCC-API-010)
- Standardized errors (QCC-API-012, Error Contract in [credit-rest-api.md](../functional/credit-rest-api.md))
- Replay/idempotency — no dedicated mechanism; validated independently per attempt (QCC-API-013)

### Data Integrity

- Atomic rollback (QCC-DATA-003)
- Immutable ledger (QCC-LEDGER-001, QCC-DATA-004)
- Balance/ledger consistency (QCC-ACCOUNT-005, QCC-AUDIT-004)
- No negative balance (QCC-DATA-001)
- Concurrency (QCC-DATA-006)

### Regression

- Checkout (QCC-NFR-005)
- Customer account (QCC-NFR-005)
- Admin customer management (QCC-NFR-005)
- Order processing (QCC-NFR-005)
- Existing integrations (QCC-NFR-005)

## Related Specifications

All sub-specifications listed in [spec.md](../spec.md#specification-index) contribute requirement IDs referenced above.
[clarifications.md](../clarifications.md) — CLA-012 (performance targets, resolved: no formal SLA required).
