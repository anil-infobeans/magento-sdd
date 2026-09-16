# Specification: Credit Ledger

**Specification**: quick-consult-credit / credit-ledger
**Version**: 1.1
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-LEDGER

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §3.2, §6.1; Technical Architecture §6, §7.2, §9 | New specification |
| 1.1 | Clarified QCC-LEDGER-003: amount/balance fields are whole-number credit points, not decimal monetary values | /speckit-clarify session 2026-09-15 (CLA-001, CLA-002) | Resolves prior open precision question for this specification |

## Purpose

Defines the immutable, append-only transaction ledger that constitutes the authoritative audit trail for every credit balance movement.

## Requirements

### QCC-LEDGER-001 — Append-only, immutable

**Statement (EARS)**: The system shall treat every ledger entry as append-only and shall not permit modification or deletion of a ledger entry after it has been successfully created.

**Source**: SRS §6.1; Technical Architecture §6

**Acceptance Criteria**:
- AC-1: Given a successfully created ledger entry, when any subsequent operation attempts to alter or remove it, then the attempt is rejected and the original entry remains unchanged.

### QCC-LEDGER-002 — Supported transaction types

**Statement (EARS)**: The system shall classify every ledger entry as one of exactly four transaction types: purchase, redeem, admin add, or admin remove.

**Source**: SRS §3.2, §6.1; Technical Architecture §7.2

**Acceptance Criteria**:
- AC-1: Given any ledger entry, when its transaction type is inspected, then it is one of the four defined types and no other value.

*Note*: Final casing/naming convention for these types is an open item — see [clarifications.md](./clarifications.md) CLA-006.

### QCC-LEDGER-003 — Direction and balance snapshots

**Statement (EARS)**: The system shall record, for every ledger entry, the movement direction (credit or debit), the account balance immediately before the movement, and the account balance immediately after the movement, expressed as whole-number credit points (no fractional/decimal value — see [clarifications.md](./clarifications.md) CLA-001, CLA-002, resolved 2026-09-15).

**Source**: Technical Architecture §7.2; Resolved clarification (CLA-001, CLA-002)

**Acceptance Criteria**:
- AC-1: Given any ledger entry, when its fields are inspected, then `balance_after` equals `balance_before` plus the amount (for credit direction) or minus the amount (for debit direction), and all three values are whole numbers.

### QCC-LEDGER-004 — Message/reason capture

**Statement (EARS)**: The system shall record an optional message for purchase and redeem ledger entries, and shall require a non-empty reason/message for admin add and admin remove ledger entries.

**Source**: SRS §5.2, §6.1; Technical Architecture §7.2, §13

**Acceptance Criteria**:
- AC-1: Given an admin add or admin remove request with an empty or missing reason, when the request is submitted, then the operation is rejected before any ledger entry is created.
- AC-2: Given a valid admin add or admin remove request with a reason, when the ledger entry is created, then the reason is stored on that entry.

### QCC-LEDGER-005 — Source and creator identity

**Statement (EARS)**: The system shall record, for every ledger entry, the originating source (customer, API/integration, admin, or system) and, where applicable, the identity of the creator (administrator identity or system/integration identifier).

**Source**: SRS §6.1; Technical Architecture §7.2, §14

**Acceptance Criteria**:
- AC-1: Given an admin add or admin remove ledger entry, when its creator field is inspected, then it contains the identity of the authenticated administrator who performed the action.
- AC-2: Given a purchase ledger entry created by automated order processing, when its source field is inspected, then it identifies the entry as system-originated.

### QCC-LEDGER-006 — Timestamp

**Statement (EARS)**: The system shall record a creation timestamp for every ledger entry at the time the entry is created.

**Source**: SRS §6.1; Technical Architecture §7.2

**Acceptance Criteria**:
- AC-1: Given any ledger entry, when its timestamp is inspected, then it reflects the moment the entry was committed, not a client-supplied value.

### QCC-LEDGER-007 — Atomic pairing with balance change

**Statement (EARS)**: The system shall treat a successful balance change and its corresponding ledger entry as a single atomic business operation.

**Source**: SRS §6.2; Technical Architecture §9.1, §9.2

**Acceptance Criteria**:
- AC-1: Given a balance-changing operation, when it succeeds, then exactly one corresponding ledger entry exists.
- AC-2: Given a balance-changing operation, when it fails at any stage, then neither the balance change nor the ledger entry is committed (see [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) QCC-DATA-003).

### QCC-LEDGER-008 — No silent modification of history

**Statement (EARS)**: The system shall not provide any supported mechanism for silently modifying a historical ledger entry; corrections shall only occur through new, separately auditable ledger entries.

**Source**: SRS §6.1; Technical Architecture §25 ("Data correction scripts ... should create explicit adjustment ledger entries rather than silently editing balances")

**Acceptance Criteria**:
- AC-1: Given a need to correct a past error, when a correction is performed, then it is recorded as a new ledger entry (e.g., an admin adjustment) rather than an edit to the original entry.

### QCC-LEDGER-009 — Queryable, paginated, per-customer history

**Statement (EARS)**: The system shall allow the ledger for a given customer to be retrieved in a paginated, chronologically ordered form.

**Source**: SRS §5.1; Technical Architecture §12

**Acceptance Criteria**:
- AC-1: Given a customer with more ledger entries than one page size, when their history is requested, then results are returned in pages ordered by creation time, with no entry omitted or duplicated across pages.

## Related Specifications

- [customer-credit-account.md](./customer-credit-account.md) — the materialized balance that ledger entries collectively reconcile to.
- [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) — atomicity requirements referenced by QCC-LEDGER-007.
- [audit-and-observability.md](./audit-and-observability.md) — audit use of ledger data.
- [clarifications.md](./clarifications.md) — CLA-002 (precision, resolved), CLA-006 (naming/casing, open).
