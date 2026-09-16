# Specification: Admin Credit Management

**Specification**: quick-consult-credit / admin-credit-management
**Version**: 1.1
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-ADMIN

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §5.2; Technical Architecture §13, §24 | New specification |
| 1.1 | Updated test scenario table to whole-number credit points (removed dollar formatting) | /speckit-clarify session 2026-09-15 (CLA-001, CLA-002) | Terminology consistency only; no behavioral change |

## Purpose

Defines the Magento Admin experience for viewing a customer's Quick Consult Credit account and manually adding or removing credit, including mandatory reason capture and administrator auditability. This specification defines observable behavior only; it does not prescribe controller/block implementation.

## Requirements

### QCC-ADMIN-001 — Integration into customer edit view

**Statement (EARS)**: The system shall present Quick Consult Credit information within the existing Magento Admin customer view/edit experience.

**Source**: SRS §5.2; Technical Architecture §13

**Acceptance Criteria**:
- AC-1: Given an administrator viewing a customer record, when they navigate to the Quick Consult Credit section, then it is presented as part of that customer's edit/view experience (not a separate cross-customer page).

### QCC-ADMIN-002 — Display balance, lifetime totals, and full history

**Statement (EARS)**: The system shall display, for the customer being viewed, the current balance, lifetime purchased (credited) total, lifetime redeemed (debited) total, and the complete transaction history.

**Source**: SRS §5.2; Technical Architecture §13

**Acceptance Criteria**:
- AC-1: Given a customer with account and ledger data, when an authorized administrator views the Quick Consult Credit section, then current balance, lifetime credited total, lifetime debited total, and full transaction history are all displayed.

### QCC-ADMIN-003 — Add Credit action

**Statement (EARS)**: If an authorized administrator submits an Add Credit action with an amount greater than zero and a non-empty reason, the system shall increase the customer's balance by that amount and create an ADMIN_ADD ledger entry.

**Source**: SRS §5.2; Technical Architecture §9.1, §13

**Acceptance Criteria**:
- AC-1: Given balance B, when an authorized Add Credit of amount A with reason R is submitted, then the new balance is B + A and an ADMIN_ADD ledger entry records amount A and reason R.
- AC-2: Given amount ≤ 0, when Add Credit is submitted, then the request is rejected, balance unchanged, no ledger entry created.

### QCC-ADMIN-004 — Remove Credit action

**Statement (EARS)**: If an authorized administrator submits a Remove Credit action with an amount greater than zero and less than or equal to the current balance, and a non-empty reason, the system shall decrease the customer's balance by that amount and create an ADMIN_REMOVE ledger entry.

**Source**: SRS §5.2; Technical Architecture §9.2, §13

**Acceptance Criteria**:
- AC-1: Given balance B, when an authorized Remove Credit of amount A (0 < A ≤ B) with reason R is submitted, then the new balance is B − A and an ADMIN_REMOVE ledger entry records amount A and reason R.
- AC-2: Given amount > B, when Remove Credit is submitted, then the request is rejected, balance unchanged, no ledger entry created.
- AC-3: Given amount ≤ 0, when Remove Credit is submitted, then the request is rejected, balance unchanged, no ledger entry created.

### QCC-ADMIN-005 — Mandatory reason for every adjustment

**Statement (EARS)**: If an Add Credit or Remove Credit request is submitted without a reason/message, the system shall reject the request before any balance or ledger change occurs.

**Source**: SRS §5.2

**Acceptance Criteria**:
- AC-1: Given a request with an empty or missing reason, when submitted, then it is rejected and no balance or ledger change occurs.

### QCC-ADMIN-006 — Administrator identity recorded

**Statement (EARS)**: When an Add Credit or Remove Credit action succeeds, the system shall record the identity of the currently authenticated administrator on the resulting ledger entry.

**Source**: SRS §5.2, §7.1; Technical Architecture §13, §14

**Acceptance Criteria**:
- AC-1: Given a successful adjustment by administrator X, when the ledger entry is inspected, then it records administrator X's identity.

### QCC-ADMIN-007 — Atomic commit

**Statement (EARS)**: The system shall commit a successful Add Credit or Remove Credit action's balance change and ledger entry as a single atomic operation.

**Source**: SRS §5.2; Technical Architecture §9.1, §9.2

**Acceptance Criteria**:
- AC-1: Given an Add Credit or Remove Credit action, when it succeeds, then both the balance change and the ledger entry are present; when it fails, neither is present.

### QCC-ADMIN-008 — Authorization required

**Statement (EARS)**: The system shall require a dedicated administrative permission before allowing an administrator to view, add, or remove customer credit.

**Source**: SRS §7.1; Technical Architecture §14

**Acceptance Criteria**:
- AC-1: Given an administrator without the required permission, when they attempt to view, add, or remove credit, then the action is denied.

### QCC-ADMIN-009 — Unauthorized administrator rejected

**Statement (EARS)**: If an administrator lacking the required permission attempts an Add Credit or Remove Credit action, the system shall reject the action and shall not change the balance or create a ledger entry.

**Source**: Technical Architecture §14, §24 ("Unauthorized administrator")

**Acceptance Criteria**:
- AC-1: Given an unauthorized administrator, when they attempt an adjustment, then it is rejected, balance unchanged, no ledger entry created.

## Test Scenarios (Given/When/Then summary)

| Scenario | Given | When | Then |
|---|---|---|---|
| Authorized add | Balance 75, authorized admin | Add 20 with reason | Balance 95; ADMIN_ADD entry with admin identity + reason |
| Authorized remove | Balance 95, authorized admin | Remove 30 with reason | Balance 65; ADMIN_REMOVE entry with admin identity + reason |
| Insufficient admin removal | Balance 65 | Remove 200 | Rejected; balance unchanged; no entry |
| Zero/negative adjustment | Any balance | Add/Remove 0 or negative | Rejected; balance unchanged; no entry |
| Missing reason | Any balance | Add/Remove without reason | Rejected before any change |
| Audit identity | Successful adjustment | Ledger inspected | Administrator identity present |
| Unauthorized administrator | Admin lacks permission | Attempts adjustment | Rejected |

## Related Specifications

- [credit-ledger.md](./credit-ledger.md) — ADMIN_ADD/ADMIN_REMOVE ledger entry structure.
- [security-and-access-control.md](./security-and-access-control.md) — ACL model referenced by QCC-ADMIN-008/009.
- [clarifications.md](./clarifications.md) — CLA-011 (admin adjustments vs. lifetime totals).
