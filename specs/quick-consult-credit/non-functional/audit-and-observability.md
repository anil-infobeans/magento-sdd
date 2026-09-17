# Specification: Audit and Observability

**Specification**: quick-consult-credit / audit-and-observability
**Version**: 1.3
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-AUDIT

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §7.1; Technical Architecture §14, §18 | New specification |
| 1.1 | Relocated to `non-functional/` and updated cross-reference links | Constitution v1.4.0 Principle XIII, 2026-09-16 | Structural only; no requirement content changed |
| 1.2 | Resolved QCC-AUDIT-006: no specific regulation applies; ledger retained indefinitely | /speckit-clarify session 2026-09-16 (CLA-013) | Removes prior open-item language; behavior unchanged |
| 1.3 | Added QCC-AUDIT-007: rejected (business-validation/authorization) requests and accepted replayed/retried requests require a distinct audit-logging signal, separate from QCC-AUDIT-005's operational-failure logging | `/speckit-analyze` finding W5, 2026-09-16 | Closes a requirements-completeness gap (checklists/api.md CHK027); no other requirement changed |

## Purpose

Defines audit-trail and operational-observability requirements: what must be captured for every balance-changing operation, and how operational failures are logged without exposing sensitive data.

## Requirements

### QCC-AUDIT-001 — Every successful balance-changing operation is auditable

**Statement (EARS)**: When any balance-changing operation succeeds, the system shall produce an immutable ledger record sufficient to reconstruct what changed, when, why, and by what source.

**Source**: SRS §6.1; Technical Architecture §7.2

**Acceptance Criteria**:
- AC-1: Given any successful PURCHASE, REDEEM, ADMIN_ADD, or ADMIN_REMOVE operation, when the ledger is inspected, then a single entry exists containing type, amount, direction, balance-before, balance-after, source, and timestamp.

### QCC-AUDIT-002 — Administrator identity captured

**Statement (EARS)**: When an administrator performs an Add Credit or Remove Credit action, the system shall capture that administrator's identity on the resulting ledger entry.

**Source**: SRS §5.2, §7.1; Technical Architecture §14

**Acceptance Criteria**:
- AC-1: Given a successful admin adjustment, when the ledger entry is inspected, then the administrator's identity (e.g., username/email) is present.

### QCC-AUDIT-003 — Reason/message captured for admin operations

**Statement (EARS)**: When an administrator performs an Add Credit or Remove Credit action, the system shall capture the supplied reason/message on the resulting ledger entry.

**Source**: SRS §5.2

**Acceptance Criteria**:
- AC-1: Given a successful admin adjustment with reason R, when the ledger entry is inspected, then it stores R unchanged.

### QCC-AUDIT-004 — Ledger sufficient for reconciliation

**Statement (EARS)**: The system shall ensure the ledger, taken as a whole, allows operational reconciliation of any customer's current balance from their complete transaction history.

**Source**: Technical Architecture §3, §18 ("Operational metrics should be derivable from the ledger")

**Acceptance Criteria**:
- AC-1: Given a customer's complete ledger, when all entries are replayed in order from zero, then the computed balance equals the customer's current materialized balance.

### QCC-AUDIT-005 — Operational failure logging without sensitive data

**Statement (EARS)**: When an operational failure occurs during a balance-changing operation, the system shall log the failure with sufficient context (customer identifier, transaction type, amount, reference identifier, source, and exception summary) while excluding authentication tokens and credentials.

**Source**: Technical Architecture §18

**Acceptance Criteria**:
- AC-1: Given an operational failure (e.g., persistence error), when it is logged, then the log entry includes the defined context fields and excludes any credential or token value.

### QCC-AUDIT-006 — No invented regulatory compliance claim (resolved)

**Statement (EARS)**: The system's documentation and behavior shall not assert compliance with a specific named financial or data-protection regulation unless such a requirement is explicitly established by the source documents. No specific regulatory jurisdiction applies to this feature; the ledger is retained indefinitely as standard operational/audit data (resolved via [clarifications.md](../clarifications.md) CLA-013, resolved 2026-09-16 via /speckit-clarify session).

**Source**: Resolved clarification; see [clarifications.md](../clarifications.md) CLA-013

**Acceptance Criteria**:
- AC-1: Given the complete Quick Consult Credit specification set, when reviewed, then no specific regulatory framework or jurisdiction is claimed as satisfied.

### QCC-AUDIT-007 — Rejected and replayed request audit logging (distinct from operational-failure logging)

**Statement (EARS)**: When a create-transaction or redemption request is rejected due to a business validation or authorization failure (e.g., `INVALID_REQUEST`, `INVALID_AMOUNT`, `INVALID_TRANSACTION_TYPE`, `INSUFFICIENT_BALANCE`, `CUSTOMER_NOT_FOUND`, `UNAUTHORIZED`), or when a replayed/retried request is independently accepted as an additional transaction (per [credit-rest-api.md](../functional/credit-rest-api.md) QCC-API-013, CLA-004), the system shall log this as a distinct audit-logging event — a rejected-request or resubmission-accepted event — separate from the operational-failure logging defined in QCC-AUDIT-005, which covers only infrastructure/persistence failures. Each such log entry shall include the customer identifier, transaction type, amount, resulting `error_code` or an indicator that the event was an accepted resubmission, source, and the `authorization_category`/`decision` fields defined in [credit-rest-api.md](../functional/credit-rest-api.md) QCC-API-011 where applicable, while excluding authentication tokens and credentials.

**Source**: SRS §7.2; Technical Architecture §18; `/speckit-analyze` finding W5 (2026-09-16)

**Rationale**: QCC-AUDIT-005 exists to diagnose infrastructure/persistence failures (a materially different signal), and does not by itself require capturing rejected business-validation/authorization outcomes or accepted replays. Given the accepted duplicate-debit risk documented under CLA-004 ([clarifications.md](../clarifications.md)), a distinguishable audit trail for rejected and replayed requests is necessary to support reconciliation and fraud/anomaly monitoring without conflating it with operational-failure diagnostics.

**Acceptance Criteria**:
- AC-1: Given a create-transaction or redemption request rejected for a business-validation or authorization reason, when it is logged, then the log entry is tagged as a rejected-request audit event (not an operational-failure event) and excludes credentials/tokens.
- AC-2: Given a replayed/retried create-transaction request that is independently validated and accepted as an additional transaction (QCC-API-013), when it is logged, then the log entry is tagged as a resubmission-accepted audit event, distinguishable from both an operational failure and a first-time acceptance.
- AC-3: Given the full set of rejected-request and resubmission-accepted audit log entries for a time period, when reviewed, then they are distinguishable from QCC-AUDIT-005's operational-failure log entries by event type, without requiring correlation across separate log sources.

## Related Specifications

- [credit-ledger.md](../functional/credit-ledger.md) — ledger entry structure that satisfies these audit requirements.
- [credit-rest-api.md](../functional/credit-rest-api.md) — QCC-API-011 (authorization-category logging), QCC-API-013 (no dedicated replay/idempotency mechanism, resolved) referenced by QCC-AUDIT-007.
- [security-and-access-control.md](./security-and-access-control.md) — QCC-SEC-005 (no sensitive data in logs), QCC-SEC-008 (replay/duplicate protection narrowed to purchase posting), QCC-SEC-010 (auditable record without regulatory claims).
- [clarifications.md](../clarifications.md) — CLA-004 (no dedicated API idempotency mechanism, resolved; underlying risk motivating QCC-AUDIT-007), CLA-013 (regulatory jurisdiction/retention, resolved).
