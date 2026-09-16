# Specification: Audit and Observability

**Specification**: quick-consult-credit / audit-and-observability
**Version**: 1.0
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-AUDIT

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §7.1; Technical Architecture §14, §18 | New specification |

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

### QCC-AUDIT-006 — No invented regulatory compliance claim

**Statement (EARS)**: The system's documentation and behavior shall not assert compliance with a specific named financial or data-protection regulation unless such a requirement is explicitly established by the source documents.

**Source**: Derived clarification; see [clarifications.md](./clarifications.md) CLA-013

**Acceptance Criteria**:
- AC-1: Given the complete Quick Consult Credit specification set, when reviewed, then no specific regulatory framework or jurisdiction is claimed as satisfied.

## Related Specifications

- [credit-ledger.md](./credit-ledger.md) — ledger entry structure that satisfies these audit requirements.
- [security-and-access-control.md](./security-and-access-control.md) — QCC-SEC-005 (no sensitive data in logs), QCC-SEC-010 (auditable record without regulatory claims).
- [clarifications.md](./clarifications.md) — CLA-013 (regulatory jurisdiction/retention).
