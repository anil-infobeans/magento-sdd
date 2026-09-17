# Specification: Security and Access Control

**Specification**: quick-consult-credit / security-and-access-control
**Version**: 1.2
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-SEC

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §7.1; Technical Architecture §14 | New specification |
| 1.1 | Relocated to `non-functional/` and updated cross-reference links | Constitution v1.4.0 Principle XIII, 2026-09-16 | Structural only; no requirement content changed |
| 1.2 | Narrowed QCC-SEC-008 to purchase posting only (CLA-004: no replay protection on the create-transaction API); resolved QCC-SEC-010 (CLA-013: no specific regulation; retain indefinitely) | /speckit-clarify session 2026-09-16 (CLA-004, CLA-013) | Removes a previously implied guarantee for the create-transaction/redeem API |
| 1.3 | Cross-referenced QCC-SEC-007 to the fixed validation-precedence order now defined in [credit-rest-api.md](../functional/credit-rest-api.md) QCC-API-012 (authentication → authorization → `INVALID_REQUEST` → `INVALID_AMOUNT`/`INVALID_TRANSACTION_TYPE` → remaining business rules), clarifying that "any balance-changing logic" includes request-shape/value validation evaluated after authorization | `/speckit-analyze` finding W1, 2026-09-16 | Clarification only; no behavioral change to QCC-SEC-007 itself |

## Purpose

Defines authentication, authorization, ACL, customer isolation, and safe error-handling requirements that apply across all Quick Consult Credit capabilities. Treats credit transactions as financial-like, auditable movements without asserting compliance with any specific regulation.

## Requirements

### QCC-SEC-001 — Magento-native authentication

**Statement (EARS)**: The system shall authenticate every customer, admin, and API request using Magento-native authentication mechanisms (customer session/token, admin session, or integration token).

**Source**: SRS §2.5, §7.1; Technical Architecture §14

**Acceptance Criteria**:
- AC-1: Given any request to a Quick Consult Credit interface or API, when authentication is evaluated, then it relies exclusively on Magento-supported authentication mechanisms.

### QCC-SEC-002 — Dedicated ACL resource for admin management

**Statement (EARS)**: The system shall define a dedicated Magento ACL resource controlling access to view, add, and remove customer credit in the Admin Panel.

**Source**: SRS §7.1; Technical Architecture §14

**Acceptance Criteria**:
- AC-1: Given the Admin Panel role configuration, when the Quick Consult Credit ACL resource is inspected, then it exists as a distinct, assignable permission.

### QCC-SEC-003 — Least-privilege granular permissions

**Statement (EARS)**: The system shall support granular administrative permissions distinguishing, at minimum, viewing credit/history from adding or removing credit.

**Source**: Technical Architecture §14 ("Recommended permissions: view credit, view transaction history, add credit, remove credit, and API transaction access")

**Acceptance Criteria**:
- AC-1: Given an administrator role granted only view permission, when they attempt to add or remove credit, then the action is denied while viewing remains permitted.

### QCC-SEC-004 — Customer account isolation

**Statement (EARS)**: The system shall not disclose one customer's balance or transaction history to another customer under any circumstance.

**Source**: SRS §5.1; Technical Architecture §12

**Acceptance Criteria**:
- AC-1: Given customer A's data and an authenticated request from customer B, when B requests A's data by any means, then the request is denied and no data is disclosed.

### QCC-SEC-005 — No sensitive data in logs or errors

**Statement (EARS)**: The system shall not include authentication tokens, credentials, or other sensitive authentication material in logs, exceptions, or user-facing error messages.

**Source**: Technical Architecture §18 ("without logging sensitive credentials or full authentication tokens")

**Acceptance Criteria**:
- AC-1: Given any logged operational event or error response, when its contents are inspected, then no authentication token, password, or credential value is present.

### QCC-SEC-006 — Safe, generic error responses

**Statement (EARS)**: The system shall return user-safe, actionable error messages without exposing internal implementation details, stack traces, or persistence-layer information.

**Source**: SRS §7.2; Technical Architecture §17

**Acceptance Criteria**:
- AC-1: Given any error response, when its content is inspected, then it contains only the standardized error code and a business-level message, with no internal exception detail.

### QCC-SEC-007 — Authorization checked before any state change

**Statement (EARS)**: The system shall verify authorization for the requested operation before evaluating or applying any balance-changing logic, including before any request-shape or value-level payload validation (`INVALID_REQUEST`, `INVALID_AMOUNT`, `INVALID_TRANSACTION_TYPE`). The fixed cross-endpoint evaluation order is authentication → authorization → `INVALID_REQUEST` → `INVALID_AMOUNT`/`INVALID_TRANSACTION_TYPE` → remaining business-rule validation, per [credit-rest-api.md](../functional/credit-rest-api.md) QCC-API-012 (resolved `/speckit-analyze` finding W1, 2026-09-16).

**Source**: Technical Architecture §11.3, §14; [credit-rest-api.md](../functional/credit-rest-api.md) QCC-API-012

**Acceptance Criteria**:
- AC-1: Given an unauthorized request to redeem, add, or remove credit, when the request is processed, then authorization failure is determined before any balance calculation or ledger write is attempted.
- AC-2: Given an unauthorized caller submitting a structurally malformed or value-invalid payload, when the request is processed, then the response is the authorization error (`UNAUTHORIZED`), not a payload-validation error, because authorization is evaluated first.

### QCC-SEC-008 — Replay/duplicate protection is limited to purchase posting (narrowed)

**Statement (EARS)**: The system shall protect purchase posting against duplicate/replayed processing, as detailed in [credit-purchase-posting.md](../functional/credit-purchase-posting.md). The transaction-creation REST API (redeem and admin-adjustment paths) does not implement a dedicated replay/idempotency mechanism; a replayed or retried API request MAY produce an additional accepted transaction if each individual attempt independently passes validation (resolved via [clarifications.md](../clarifications.md) CLA-004, resolved 2026-09-16 via /speckit-clarify session). This explicitly narrows the general expectation previously implied by this requirement for the API surface; it does not affect purchase-posting duplicate prevention.

**Source**: SRS §7.2 (narrowed for the API surface); Technical Architecture §28; Resolved clarification (CLA-004)

**Acceptance Criteria**:
- AC-1: Given a replayed purchase-posting event previously processed successfully, when reprocessed, then no additional balance-changing effect occurs (see [credit-purchase-posting.md](../functional/credit-purchase-posting.md) QCC-PURCHASE-001/009/010/011).
- AC-2: Given a replayed create-transaction API request previously processed successfully, when reprocessed, then it is validated independently and MAY produce an additional accepted transaction if it individually passes validation (see [credit-rest-api.md](../functional/credit-rest-api.md) QCC-API-013).

### QCC-SEC-009 — Negative balance prevention at the service boundary

**Statement (EARS)**: The system shall enforce negative-balance prevention within the shared business/service-contract boundary, not solely within presentation or API-layer validation.

**Source**: SRS §7.2; Technical Architecture §9.2

**Acceptance Criteria**:
- AC-1: Given any caller (customer UI, Admin UI, or API) attempting to over-debit an account, when the request reaches the business/service boundary, then it is rejected regardless of which caller submitted it.

### QCC-SEC-010 — Auditable record without invented regulatory claims

**Statement (EARS)**: The system shall preserve an auditable record of every successful credit balance movement sufficient to support operational reconciliation and applicable organizational compliance controls, without asserting compliance with any specific unnamed regulation or jurisdiction. No specific regulatory jurisdiction applies to this feature, and the ledger shall be retained indefinitely as standard operational/audit data (resolved via [clarifications.md](../clarifications.md) CLA-013, resolved 2026-09-16 via /speckit-clarify session).

**Source**: Resolved clarification; see [clarifications.md](../clarifications.md) CLA-013

**Acceptance Criteria**:
- AC-1: Given the full transaction ledger, when reviewed for reconciliation purposes, then every successful balance movement is represented by exactly one traceable ledger entry.
- AC-2: Given any specification or documentation artifact produced under this feature, when reviewed, then it does not claim compliance with a specific named regulation unless that regulation is explicitly established by the source documents (none is, as of v1.0).

## Related Specifications

- [audit-and-observability.md](./audit-and-observability.md) — logging and audit detail.
- [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) — negative-balance and atomicity invariants.
- [credit-rest-api.md](../functional/credit-rest-api.md) — endpoint-specific authentication/authorization.
- [clarifications.md](../clarifications.md) — CLA-004 (no dedicated API idempotency mechanism, resolved), CLA-007 (customer self-redemption authorization, resolved), CLA-013 (regulatory jurisdiction, resolved).
