# Specification: Customer Dashboard

**Specification**: quick-consult-credit / customer-dashboard
**Version**: 1.0
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-CUSTOMER

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §5.1; Technical Architecture §12 | New specification |

## Purpose

Defines the customer-facing account dashboard for viewing Quick Consult Credit balance and history. This specification defines observable behavior only; it does not prescribe HTML, CSS, JavaScript, or template implementation.

## Requirements

### QCC-CUSTOMER-001 — Account navigation entry

**Statement (EARS)**: The system shall provide a "Quick Consult Credit" entry in the customer's My Account navigation.

**Source**: SRS §5.1

**Acceptance Criteria**:
- AC-1: Given an authenticated customer viewing My Account, when the navigation is inspected, then a "Quick Consult Credit" entry is present and leads to the dashboard section.

### QCC-CUSTOMER-002 — Current balance display

**Statement (EARS)**: When a customer opens the Quick Consult Credit dashboard, the system shall display their current available balance.

**Source**: SRS §5.1

**Acceptance Criteria**:
- AC-1: Given an authenticated customer with balance B, when they open the dashboard, then the displayed balance equals B.

### QCC-CUSTOMER-003 — Transaction history display with defined columns

**Statement (EARS)**: When a customer opens the Quick Consult Credit dashboard, the system shall display their transaction history including, at minimum, date, type, amount, resulting balance, and message for each entry.

**Source**: SRS §5.1; Technical Architecture §12

**Acceptance Criteria**:
- AC-1: Given a customer with ledger entries, when the history is displayed, then each row shows date, type, amount, balance, and message consistent with the underlying ledger entry.

### QCC-CUSTOMER-004 — Pagination

**Statement (EARS)**: When a customer's transaction history exceeds the configured page size, the system shall paginate the displayed history.

**Source**: SRS §5.1; Technical Architecture §12, §19

**Acceptance Criteria**:
- AC-1: Given a customer with more transactions than one configured page size, when the history is displayed, then it is split across pages with no entry omitted or duplicated, ordered consistently with [credit-ledger.md](./credit-ledger.md) QCC-LEDGER-009.

### QCC-CUSTOMER-005 — Customer-only access to own data

**Statement (EARS)**: The system shall restrict the dashboard to displaying only the authenticated customer's own balance and history.

**Source**: SRS §5.1; Technical Architecture §12

**Acceptance Criteria**:
- AC-1: Given an authenticated customer, when the dashboard is loaded, then only that customer's own account data is displayed, using the authenticated session as the sole source of customer identity (not a client-supplied parameter).

### QCC-CUSTOMER-006 — Access to another customer's data denied

**Statement (EARS)**: If an authenticated customer attempts to view another customer's balance or history (e.g., via a manipulated request), the system shall deny the request and disclose no data belonging to the other customer.

**Source**: SRS §5.1; Technical Architecture §12

**Acceptance Criteria**:
- AC-1: Given customer A is authenticated, when a request attempts to retrieve customer B's balance or history, then the request is denied and no data belonging to customer B is returned.

## Test Scenarios (Given/When/Then summary)

| Scenario | Given | When | Then |
|---|---|---|---|
| Own balance | Customer with balance | Views dashboard | Correct balance shown |
| Own transaction history | Customer with ledger entries | Views dashboard | Correct history, correct columns |
| Pagination | Customer with more entries than page size | Views history | Paginated, no gaps/duplicates |
| Unauthorized access to another customer | Customer A authenticated | Requests customer B's data | Denied, no disclosure |

## Related Specifications

- [customer-credit-account.md](./customer-credit-account.md) — the account fields displayed.
- [credit-ledger.md](./credit-ledger.md) — the ledger entries displayed.
- [configuration.md](./configuration.md) — QCC-CONFIG-004 (history page size).
- [security-and-access-control.md](./security-and-access-control.md) — customer isolation model.
