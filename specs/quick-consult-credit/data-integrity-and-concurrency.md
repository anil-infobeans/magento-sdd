# Specification: Data Integrity and Concurrency

**Specification**: quick-consult-credit / data-integrity-and-concurrency
**Version**: 1.0
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-DATA

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §6.2, §7.2; Technical Architecture §9, §28 | New specification |

## Purpose

Defines the measurable invariants that protect balance and ledger correctness under normal operation, failure, and concurrent access. States required observable behavior only; does not prescribe SQL locking syntax, specific persistence APIs, or implementation mechanisms.

## Requirements

### QCC-DATA-001 — Balance never negative

**Statement (EARS)**: The system shall not allow any customer's balance to become negative under any sequence of operations, including concurrent operations.

**Source**: SRS §7.2; Technical Architecture §9.2, §24

**Acceptance Criteria**:
- AC-1: Given any combination of concurrent redemption, admin-remove, and purchase operations, when all have been processed, then no customer's balance is observed to be negative at any point.

### QCC-DATA-002 — One ledger movement per successful balance change

**Statement (EARS)**: The system shall create exactly one corresponding ledger movement for every successful balance-changing operation.

**Source**: SRS §6.2; Technical Architecture §9.1, §9.2

**Acceptance Criteria**:
- AC-1: Given N successful balance-changing operations for a customer, when the ledger is counted, then it contains exactly N corresponding entries.

### QCC-DATA-003 — No partial state on failure

**Statement (EARS)**: If a balance-changing operation fails at any stage, the system shall leave neither a partial balance update nor a partial/orphaned ledger entry.

**Source**: SRS §6.2; Technical Architecture §9.1 ("If any step fails, roll back the entire operation")

**Acceptance Criteria**:
- AC-1: Given an operation that fails after starting but before completing, when the account and ledger are inspected afterward, then both reflect the pre-operation state exactly.

### QCC-DATA-004 — Ledger remains append-only after successful creation

**Statement (EARS)**: The system shall not permit any operation to alter a ledger entry once it has been successfully created (duplicated from [credit-ledger.md](./credit-ledger.md) QCC-LEDGER-001 for cross-cutting integrity emphasis).

**Source**: SRS §6.1; Technical Architecture §6

**Acceptance Criteria**:
- AC-1: Given a successfully created ledger entry, when any later process attempts to change it, then the attempt does not succeed.

### QCC-DATA-005 — Purchase posting does not double-credit for the same reference

**Statement (EARS)**: The system shall not increase a customer's credit more than once for the same purchase reference.

**Source**: SRS §7.2; Technical Architecture §7.2, §10

**Acceptance Criteria**:
- AC-1: Given a purchase reference that has already resulted in a posted credit, when posting is attempted again for that same reference (including concurrently), then no additional credit is applied.

### QCC-DATA-006 — Concurrent redemptions cannot double-spend

**Statement (EARS)**: The system shall logically serialize concurrent redemption requests against the same account such that accepted transactions cannot consume the same credit twice.

**Source**: Technical Architecture §9.2, §24, §28

**Acceptance Criteria**:
- AC-1: Given concurrent redemption requests whose combined amount exceeds the balance at the start of processing, when all are processed, then the accepted subset's combined amount does not exceed that starting balance.

### QCC-DATA-007 — All balance-changing operations pass through the defined service boundary

**Statement (EARS)**: The system shall require every balance-changing operation, regardless of initiating layer (customer UI, Admin UI, REST API, purchase-posting process), to pass through a single defined business/service-contract boundary.

**Source**: Technical Architecture §4, §6.2 ("Service Contracts & Extension Attributes"), §20

**Acceptance Criteria**:
- AC-1: Given any initiating layer, when a balance-changing action is performed, then it is observably routed through the same business-rule enforcement (validation, atomicity, ledger creation) regardless of origin.

### QCC-DATA-008 — Presentation/API layers do not directly modify persistence

**Statement (EARS)**: The system shall not permit presentation-layer code (controllers, blocks, templates) or API-translation code to directly modify credit account or ledger persistence state.

**Source**: Technical Architecture §4, §13 ("The controller must never directly update qcc_customer_credit or qcc_credit_transaction")

**Acceptance Criteria**:
- AC-1: Given the architecture of any consuming layer, when a balance change is initiated, then persistence modification occurs only via the shared business/service boundary, never directly from that layer.

## Related Specifications

- [credit-ledger.md](./credit-ledger.md), [customer-credit-account.md](./customer-credit-account.md) — data structures protected by these invariants.
- [credit-purchase-posting.md](./credit-purchase-posting.md), [credit-redemption.md](./credit-redemption.md), [admin-credit-management.md](./admin-credit-management.md) — operations governed by these invariants.
- [testing-and-acceptance.md](./testing-and-acceptance.md) — concurrency and atomicity test scenarios.
