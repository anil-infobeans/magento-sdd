# Specification: Customer Credit Account

**Specification**: quick-consult-credit / customer-credit-account
**Version**: 1.0
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-ACCOUNT

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §3.2; Technical Architecture §6, §7.1, §9 | New specification |

## Purpose

Defines the customer credit account: its cardinality, contents, invariants, and the rules governing when its materialized fields change. The account represents current state; the immutable audit trail is defined separately in [credit-ledger.md](./credit-ledger.md).

## Requirements

### QCC-ACCOUNT-001 — One account per customer

**Statement (EARS)**: The system shall maintain exactly one credit account per Magento customer.

**Source**: SRS §3.2; Technical Architecture §6 ("1 row per customer")

**Acceptance Criteria**:
- AC-1: Given a customer with an existing credit account, when a second account-creation is attempted for the same customer, then no duplicate account is created.

### QCC-ACCOUNT-002 — Account contents

**Statement (EARS)**: The system shall maintain, for each customer credit account: the customer identity, the current available balance, the lifetime credited total, and the lifetime debited total.

**Source**: SRS §3.2; Technical Architecture §7.1

**Acceptance Criteria**:
- AC-1: Given any credit account, when its state is read, then customer identity, current balance, lifetime credited total, and lifetime debited total are all present and individually retrievable.

### QCC-ACCOUNT-003 — Balance non-negativity invariant

**Statement (EARS)**: The system shall not allow the current balance of any credit account to become negative.

**Source**: SRS §7.2; Technical Architecture §9.2

**Acceptance Criteria**:
- AC-1: Given any sequence of accepted balance-changing operations, when the resulting balance is inspected at any point in time, then it is greater than or equal to zero.

### QCC-ACCOUNT-004 — Account creation on first qualifying operation

**Statement (EARS)**: When a customer has no existing credit account and a qualifying balance-changing operation is requested for that customer, the system shall create the account with a zero starting balance before applying the operation.

**Source**: Derived clarification (Technical Architecture §8, `getOrCreateBalance`)

**Acceptance Criteria**:
- AC-1: Given a customer with no prior credit account, when their first qualifying purchase is posted, then an account is created with balance 0 immediately prior to the posting operation, and the posting operation then applies normally.

### QCC-ACCOUNT-005 — Balance is a materialized read-optimized value

**Statement (EARS)**: The system shall maintain the current balance as a materialized value derived from, and always consistent with, the transaction ledger, rather than requiring the ledger to be replayed to determine current balance.

**Source**: Technical Architecture §3 ("Combines audit history with efficient operational reads")

**Acceptance Criteria**:
- AC-1: Given the full ledger for a customer, when all ledger entries' movements are summed, then the sum equals the account's current balance.

### QCC-ACCOUNT-006 — Lifetime credited total update rule

**Statement (EARS)**: When a balance-changing operation with a CREDIT direction is successfully applied, the system shall increase the account's lifetime credited total by the operation's amount.

**Source**: Technical Architecture §9.1; Derived clarification — see [clarifications.md](./clarifications.md) CLA-011 for whether this includes ADMIN_ADD as well as PURCHASE.

**Acceptance Criteria**:
- AC-1: Given an account with lifetime credited total T, when a CREDIT-direction operation of amount A is successfully applied, then lifetime credited total becomes T + A.

### QCC-ACCOUNT-007 — Lifetime debited total update rule

**Statement (EARS)**: When a balance-changing operation with a DEBIT direction is successfully applied, the system shall increase the account's lifetime debited total by the operation's amount.

**Source**: Technical Architecture §9.2; Derived clarification — see [clarifications.md](./clarifications.md) CLA-011 for whether this includes ADMIN_REMOVE as well as REDEEM.

**Acceptance Criteria**:
- AC-1: Given an account with lifetime debited total T, when a DEBIT-direction operation of amount A is successfully applied, then lifetime debited total becomes T + A.

### QCC-ACCOUNT-008 — No unauthorized account access

**Statement (EARS)**: The system shall not disclose a customer's credit account state to any actor other than the account's own customer (self-access), an authorized administrator, or an authorized integration, as governed by [security-and-access-control.md](./security-and-access-control.md).

**Source**: SRS §5.1, §4.1; Technical Architecture §12

**Acceptance Criteria**:
- AC-1: Given customer A's credit account, when customer B (authenticated, non-admin) requests customer A's balance, then the request is denied and no account data is returned.

## Related Specifications

- [credit-ledger.md](./credit-ledger.md) — the append-only movement records that back QCC-ACCOUNT-005.
- [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) — atomicity and concurrency guarantees for account updates.
- [security-and-access-control.md](./security-and-access-control.md) — access rules referenced by QCC-ACCOUNT-008.
- [clarifications.md](./clarifications.md) — CLA-002 (precision, resolved), CLA-011 (admin adjustments vs. lifetime totals, open).
