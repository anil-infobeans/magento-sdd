# Specification: Credit REST API

**Specification**: quick-consult-credit / credit-rest-api
**Version**: 1.10
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-API

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §4.1, §4.2; Technical Architecture §11 | New specification |
| 1.1 | Resolved authoritative resource path (Architecture variant), removed customer-self-access from create-transaction authorization, updated balance value format to whole-number credit points | /speckit-clarify session 2026-09-15 (CLA-001, CLA-005, CLA-007) | QCC-API-001, QCC-API-004, QCC-API-005, QCC-API-014 updated; SRS path variant retained for reference only |
| 1.2 | Relocated to `functional/` and updated cross-reference links | Constitution v1.4.0 Principle XIII, 2026-09-16 | Structural only; no requirement content changed |
| 1.3 | Resolved QCC-API-013 (no dedicated replay/idempotency mechanism); added QCC-API-015 (rate limiting relies on platform defaults) and QCC-API-016 (V1 versioning policy); updated Error Contract Detail | /speckit-clarify session 2026-09-16 (CLA-004, CLA-014, CLA-015) | Narrows the create-transaction endpoint's duplicate-request guarantee; adds two new non-blocking requirements |
| 1.4 | Added QCC-API-017: the create-transaction endpoint accepts `transaction_type = REDEEM` only; `PURCHASE` is system-triggered only and `ADMIN_ADD`/`ADMIN_REMOVE` are Admin-UI-only | `/speckit-analyze` finding C2, resolved via CLA-016, 2026-09-16 | Removes prior ambiguity in the endpoint's accepted transaction types; no other requirement changed |
| 1.5 | Resolved three `/speckit-analyze` findings (H2, H3, H4): scoped QCC-API-012's standardized error payload to exclude bare-401 authentication failures (QCC-API-002) and confirmed it applies to 403 authorization failures; added QCC-API-018 for malformed/out-of-range `customerId` path parameters (rejected with `CUSTOMER_NOT_FOUND` and HTTP 401); fixed the create-transaction success status to a single HTTP 200 and removed the "or equivalently named" field-name hedge in QCC-API-010; removed the "e.g." hedge on the `INSUFFICIENT_BALANCE` status in the Error Contract Detail | CLA-017, CLA-018, CLA-019, 2026-09-16 | Removes remaining ambiguity flagged by `/speckit-analyze`; no other requirement changed |
| 1.6 | Added QCC-API-019: a create-transaction request missing a required field or supplying a non-integer/fractional `amount` is rejected with a new `INVALID_REQUEST` error (HTTP 400), distinct from `INVALID_AMOUNT`; narrowed QCC-API-006's scope to structurally valid, non-positive integer amounts only; added `INVALID_REQUEST` to the Error Contract Detail | `/speckit-analyze` finding I1, resolved via CLA-020, 2026-09-16 | Closes a requirements-completeness gap (checklists/api.md CHK001, CHK012); no other requirement changed |
| 1.7 | Added an explicit, fixed validation-order rule to QCC-API-012 (authentication → authorization → `INVALID_REQUEST` → `INVALID_AMOUNT`/`INVALID_TRANSACTION_TYPE` → remaining business-rule validation) so that an unauthorized caller submitting a malformed payload deterministically receives `UNAUTHORIZED`, not `INVALID_REQUEST` | `/speckit-analyze` finding W1, 2026-09-16 | Removes prior ambiguity in error-precedence ordering; no other requirement changed |
| 1.8 | Made QCC-API-011 measurable: replaced the unmeasurable "determinable" phrasing with a concrete, externally verifiable mechanism — every authorization decision is recorded in a structured operational log entry with a fixed `authorization_category` enum (`AUTHENTICATION`, `CUSTOMER_OWNERSHIP`, `INTEGRATION_ACL`, `ADMIN_ACL`) and a `decision` (`GRANTED`/`DENIED`) field | `/speckit-analyze` finding W4, 2026-09-16 | Closes a measurability gap (checklists/api.md CHK011, CHK016); no other requirement changed |
| 1.9 | Added QCC-API-020: the `message` field on the create-transaction endpoint has no maximum length constraint — an explicit specification decision, not an open gap | `/speckit-analyze` finding W6, 2026-09-16 | Closes a requirements-completeness gap (checklists/api.md CHK005); no other requirement changed |
| 1.10 | Added QCC-API-009 AC-2, explicitly stating that an authenticated admin/integration caller (not a customer session) may access the balance endpoint for any `customerId` value permitted by the `ICC_QuickConsultCredit::credit` ACL resource, reconciling the normative requirement with the design contract's authorization row | `/speckit-checklist` finding CHK014, resolved 2026-09-17 | Closes a spec/contract consistency gap (checklists/api.md CHK014); no other requirement changed |

## Purpose

Defines the externally consumable REST API surface for retrieving balance and creating credit/debit transactions, including authentication, authorization, validation, error behavior, and customer-data isolation. The SRS and Technical Architecture originally disagreed on the exact resource path; this was resolved via /speckit-clarify on 2026-09-15 in favor of the Architecture variant (see [clarifications.md](../clarifications.md) CLA-005, resolved). The SRS variant is retained below for traceability only and is not implemented.

## Endpoint: Get Current Balance

| Property | Authoritative (Architecture variant) | SRS variant (superseded, not implemented) |
|---|---|---|
| HTTP method | GET | GET |
| Path | `/V1/quick-consult-credit/balance/:customerId` | `/V1/customers/:customerId/consult-credit-balance` |
| Authentication | Customer self-access or integration ACL | Magento Customer/Admin token |
| Path parameter | `customerId` (integer) | `customerId` (integer) |

**Success response** (authoritative payload): `customer_id` (integer), `balance` (integer credit points; whole number, not a decimal monetary amount — see [clarifications.md](../clarifications.md) CLA-001, CLA-002, resolved).

### QCC-API-001 — Balance endpoint exists and is authenticated

**Statement (EARS)**: The system shall expose an authenticated REST endpoint that returns a customer's current credit balance given a customer identifier.

**Source**: SRS §4.1; Technical Architecture §11.1

**Acceptance Criteria**:
- AC-1: Given a valid, authorized, authenticated request for a customer identifier, when the endpoint is called, then the response includes the customer identifier and current balance.

### QCC-API-002 — Unauthenticated request rejected

**Statement (EARS)**: If a balance request is not authenticated, the system shall reject it with HTTP 401 Unauthorized.

**Source**: SRS §4.1

**Acceptance Criteria**:
- AC-1: Given no valid authentication credential, when the balance endpoint is called, then HTTP 401 is returned and no balance data is disclosed.

### QCC-API-003 — Non-existent customer

**Statement (EARS)**: If the requested customer identifier does not correspond to an existing customer, the system shall return the `CUSTOMER_NOT_FOUND` business error.

**Source**: SRS §4.1

**Acceptance Criteria**:
- AC-1: Given a customer identifier with no matching customer, when the balance endpoint is called, then `CUSTOMER_NOT_FOUND` is returned.

### QCC-API-004 — Customer with no credit account returns zero balance

**Statement (EARS)**: If a customer exists but has no credit account, the system shall return a balance of zero rather than an error.

**Source**: SRS §4.1

**Acceptance Criteria**:
- AC-1: Given an existing customer with no credit account, when the balance endpoint is called, then the response reports a balance of 0 credit points and no error.

### QCC-API-018 — Malformed or out-of-range customerId path parameter (resolved)

**Statement (EARS)**: If the `customerId` path parameter on the Get Current Balance endpoint is not a well-formed positive integer (e.g., non-numeric, zero, negative, or exceeding the maximum valid customer identifier range), the system shall reject the request with the `CUSTOMER_NOT_FOUND` error code and HTTP status 401, without disclosing whether any customer identifier in that malformed form could otherwise exist.

**Source**: Resolved clarification (see [clarifications.md](../clarifications.md) CLA-018, resolved 2026-09-16, following `/speckit-analyze` finding H3)

**Acceptance Criteria**:
- AC-1: Given a `customerId` path parameter that is non-numeric, zero, negative, or otherwise malformed, when the balance endpoint is called, then the response is HTTP 401 with `error_code = CUSTOMER_NOT_FOUND` and no balance data is disclosed.
- AC-2: Given a `customerId` path parameter that is well-formed but does not correspond to an existing customer, when the balance endpoint is called, then the response is HTTP 404 with `error_code = CUSTOMER_NOT_FOUND` per QCC-API-003 (unchanged; this requirement applies only to malformed input, not to a valid-format nonexistent customer).

## Endpoint: Create Transaction

| Property | Authoritative (Architecture variant) | SRS variant (superseded, not implemented) |
|---|---|---|
| HTTP method | POST | POST |
| Path | `/V1/quick-consult-credit/transactions` | `/V1/customers/consult-credit-transaction` |
| Authentication | Integration/admin ACL only (no customer self-access — see [clarifications.md](../clarifications.md) CLA-007, resolved) | Authorized external integration token or Magento admin authentication |
| Request payload | `customer_id`, `transaction_type`, `amount`, `message` | `customer_id`, `transaction_type`, `amount`, `message` |
| Accepted `transaction_type` values | `REDEEM` only (see QCC-API-017) | Not specified |
| Success payload | `transaction_id`, `type`, `amount`, `previous_balance`, `current_balance` | `success`, `customer_id`, `transaction_type`, `amount`, `previous_balance`, `current_balance` |

### QCC-API-005 — Create-transaction endpoint exists and is authenticated/authorized to integrations and admins only

**Statement (EARS)**: The system shall expose an authenticated REST endpoint, restricted to authorized external integration systems and Magento admins, that creates a credit or debit transaction for a specified customer. The system shall not accept a directly authenticated customer session as sufficient authorization to call this endpoint on their own behalf.

**Source**: SRS §4.2; Technical Architecture §11.2; Resolved clarification (see [clarifications.md](../clarifications.md) CLA-007, resolved 2026-09-15)

**Acceptance Criteria**:
- AC-1: Given a valid, authorized request payload from an authorized integration or admin, when the endpoint is called, then a transaction is created per [credit-redemption.md](./credit-redemption.md) (for debit) or the applicable credit rules, and the response reports the resulting balance.
- AC-2: Given a request authenticated only as a customer session (no integration/admin authorization), when the endpoint is called, then the request is rejected with an authorization error.

### QCC-API-006 — Invalid amount rejected

**Statement (EARS)**: If the request `amount` is structurally valid (present and a whole integer, per QCC-API-019) but is less than or equal to zero, the system shall reject the request with the `INVALID_AMOUNT` error.

**Source**: SRS §4.2

**Acceptance Criteria**:
- AC-1: Given a structurally valid integer amount ≤ 0, when submitted, then `INVALID_AMOUNT` is returned and no state change occurs.

### QCC-API-007 — Insufficient balance rejected

**Statement (EARS)**: If a debit-type request amount exceeds the customer's current balance, the system shall reject the request with the `INSUFFICIENT_BALANCE` error.

**Source**: SRS §4.2

**Acceptance Criteria**:
- AC-1: Given a debit amount greater than current balance, when submitted, then `INSUFFICIENT_BALANCE` is returned and no state change occurs.

### QCC-API-008 — Customer not found rejected

**Statement (EARS)**: If the specified customer does not exist, the system shall reject the request with the `CUSTOMER_NOT_FOUND` error.

**Source**: SRS §4.2

**Acceptance Criteria**:
- AC-1: Given a non-existent customer identifier, when submitted, then `CUSTOMER_NOT_FOUND` is returned.

### QCC-API-019 — Missing required fields or non-integer amount rejected (resolved)

**Statement (EARS)**: If a create-transaction request is missing any required field (`customer_id`, `transaction_type`, or `amount`), or if `amount` is supplied as a non-integer/fractional value, the system shall reject the request with a new `INVALID_REQUEST` error and HTTP status 400, evaluated before `INVALID_AMOUNT`, `INVALID_TRANSACTION_TYPE`, or any business-rule validation. This is distinct from `INVALID_AMOUNT` (QCC-API-006), which applies only to a structurally valid integer `amount` that is ≤ 0.

**Source**: Resolved clarification (see [clarifications.md](../clarifications.md) CLA-020, resolved 2026-09-16, following `/speckit-analyze` finding I1)

**Acceptance Criteria**:
- AC-1: Given a request missing `customer_id`, `transaction_type`, or `amount` entirely, when submitted, then the response is HTTP 400 with `error_code = INVALID_REQUEST` and no state change occurs.
- AC-2: Given a request with `amount` supplied as a non-integer/fractional value (e.g., `25.5`), when submitted, then the response is HTTP 400 with `error_code = INVALID_REQUEST` and no state change occurs, consistent with the whole-number-only credit-point rule (see [clarifications.md](../clarifications.md) CLA-001, CLA-002).

### QCC-API-020 — No maximum length constraint on the message field (resolved)

**Statement (EARS)**: The `message` field on the create-transaction endpoint shall not be subject to a maximum-length or field-size validation rule. The system shall accept and persist any `message` value supplied by an authorized caller without truncation or a length-based `INVALID_REQUEST` rejection, subject only to the underlying persistence column's storage capacity (see [data-model.md](../plan/data-model.md) Credit Transaction `message` field, type `text`).

**Source**: `/speckit-analyze` finding W6 (2026-09-16); no length constraint is defined by the SRS or Technical Architecture

**Rationale**: Neither source document specifies a maximum `message` length, and no business or storage requirement necessitates one for this release; the underlying `text` column type imposes no practically-reachable limit for this use case. This is an explicit specification decision ("no length limit"), not an unresolved gap, consistent with how [testing-and-acceptance.md](../non-functional/testing-and-acceptance.md) QCC-NFR-001 and [clarifications.md](../clarifications.md) CLA-012/CLA-014 record other deliberate "no additional constraint" decisions.

**Acceptance Criteria**:
- AC-1: Given a create-transaction request with an unusually long `message` value, when submitted, then the request is not rejected for message length and the full value is persisted on the resulting ledger entry.
- AC-2: Given the create-transaction request-validation rules, when reviewed, then no `error_code` exists for a message-length violation.
- AC-3: Given a request with a structurally valid, present, integer `amount` that is ≤ 0, when submitted, then `INVALID_AMOUNT` (QCC-API-006) applies instead of `INVALID_REQUEST`.

### QCC-API-009 — Authenticated customer identity is authoritative for self-access endpoints

**Statement (EARS)**: For the Get Current Balance endpoint, the system shall not allow a `customer_id` value supplied in an untrusted request payload or path to override the identity of an authenticated customer session. This session-authoritative rule applies only when the caller is authenticated as a customer; an authenticated admin or integration caller authorized under the `ICC_QuickConsultCredit::credit` ACL resource is not a customer session and instead supplies the target `customerId` via the path parameter, per QCC-SEC-002/003.

**Source**: Technical Architecture §12 ("customer_id must not be accepted from arbitrary browser input as an authority boundary"); scope clarified by resolution of CLA-007 (Create Transaction has no customer-self-access path); AC-2 added to reconcile with [contracts/rest-api.md](../contracts/rest-api.md) Endpoint 1's authorization row (`/speckit-checklist` finding CHK014, resolved 2026-09-17)

**Acceptance Criteria**:
- AC-1: Given an authenticated customer session, when a balance request path/payload supplies a different `customer_id`, then the authenticated session's customer identity governs, not the supplied value; a mismatched value is not treated as a data-lookup override.
- AC-2: Given an authenticated admin or integration caller authorized under the `ICC_QuickConsultCredit::credit` ACL resource (not a customer session), when the balance endpoint is called, then the path `customerId` value is honored as the target customer identity, subject to that resource's ACL grant and all other error conditions (QCC-API-003, QCC-API-018).

### QCC-API-010 — Response includes previous and current balance

**Statement (EARS)**: When a create-transaction request succeeds, the system shall return HTTP 200 and shall include `previous_balance` and `current_balance` in the response, using exactly those field names.

**Source**: SRS §4.2; Technical Architecture §11.2; Resolved clarification (see [clarifications.md](../clarifications.md) CLA-019, resolved 2026-09-16, following `/speckit-analyze` finding H4)

**Acceptance Criteria**:
- AC-1: Given a successful transaction, when the response is inspected, then it has HTTP status 200 and contains `previous_balance` and `current_balance` fields (exact names, no alternate naming) with values consistent with the created ledger entry.

### QCC-API-011 — Distinct authorization models per consumer type (measurable via structured logging)

**Statement (EARS)**: The system shall distinguish, for every endpoint, between authentication (identity proof), authorization (permission to perform the requested action), customer ownership (self-access boundary), integration authorization (external system permission), and admin authorization (administrative permission). The system shall make this distinction externally verifiable by recording, for every authorization decision, a structured operational log entry containing a fixed `authorization_category` value — one of `AUTHENTICATION`, `CUSTOMER_OWNERSHIP`, `INTEGRATION_ACL`, or `ADMIN_ACL` — and a `decision` value of `GRANTED` or `DENIED` (excluding credentials/tokens per QCC-SEC-005). No response body, header, or client-visible field is required to carry this distinction; the log entry is the defined, externally verifiable observation point.

**Source**: Technical Architecture §11.1, §11.2, §14; `/speckit-analyze` finding W4 (2026-09-16)

**Acceptance Criteria**:
- AC-1: Given a request from any consumer type, when authorization is evaluated, then a log entry is produced containing exactly one of the four defined `authorization_category` values and a `GRANTED`/`DENIED` decision.
- AC-2: Given a balance-endpoint request from a customer acting on their own account, when authorization is evaluated, then the log entry's `authorization_category` is `CUSTOMER_OWNERSHIP`.
- AC-3: Given a create-transaction request from an authorized external integration or an authorized admin, when authorization is evaluated, then the log entry's `authorization_category` is `INTEGRATION_ACL` or `ADMIN_ACL` respectively.
- AC-4: Given a request with no valid authentication credential at all, when authorization is evaluated, then the log entry's `authorization_category` is `AUTHENTICATION` with `decision = DENIED`, consistent with QCC-API-002's bare-401 response.

### QCC-API-012 — Standardized error payload and fixed validation-precedence order

**Statement (EARS)**: When any API request is rejected due to a business validation or authorization failure — including HTTP 403 responses (authenticated but not authorized for the requested customer/action) — the system shall return a standardized error payload containing a `success` flag set to false, a defined `error_code`, and a human-readable `message`. This requirement does not apply to a bare HTTP 401 response (no valid authentication credential at all), which returns no body per QCC-API-002 (resolved via [clarifications.md](../clarifications.md) CLA-017, resolved 2026-09-16, following `/speckit-analyze` finding H2).

For every endpoint, the system shall evaluate rejection conditions in the following fixed order, stopping and returning the first applicable result: (1) authentication (QCC-API-002 — bare HTTP 401, no body); (2) authorization/caller-permission, including customer-ownership and integration/admin ACL checks (QCC-API-005 AC-2, QCC-API-009, QCC-SEC-007 — HTTP 403 `UNAUTHORIZED`); (3) structural request validation (`INVALID_REQUEST` — missing required field or non-integer/fractional `amount`, QCC-API-019); (4) value-level request validation (`INVALID_AMOUNT`, QCC-API-006; `INVALID_TRANSACTION_TYPE`, QCC-API-017); (5) remaining business-rule validation (`INSUFFICIENT_BALANCE`, QCC-API-007; `CUSTOMER_NOT_FOUND`, QCC-API-008). An unauthorized caller submitting a structurally malformed payload therefore always receives `UNAUTHORIZED`, never `INVALID_REQUEST`, because authorization (step 2) is evaluated before any request-shape validation (step 3), consistent with QCC-SEC-007's requirement that authorization is checked before any balance-changing logic executes.

**Source**: SRS §7.2; Resolved clarification (CLA-017); `/speckit-analyze` finding W1 (2026-09-16)

**Acceptance Criteria**:
- AC-1: Given any rejected request covered by the [Error Contract](#error-contract), when the response is inspected, then it matches the standardized error payload shape.
- AC-2: Given a request rejected with HTTP 403 (authenticated but unauthorized for the target customer/action), when the response is inspected, then it includes the standardized error payload with `error_code = UNAUTHORIZED`.
- AC-3: Given a request rejected with HTTP 401 (no valid authentication at all), when the response is inspected, then no standardized error payload body is required.
- AC-4: Given a request from a caller who is not authorized for the requested customer/action, and whose payload is also structurally malformed (e.g., missing `amount`) or specifies an invalid `transaction_type`, when the request is evaluated, then the response is `UNAUTHORIZED` (HTTP 403), not `INVALID_REQUEST`/`INVALID_TRANSACTION_TYPE`, because authorization is evaluated before request-shape or value-level validation.

### QCC-API-013 — No dedicated replay/idempotency mechanism (resolved)

**Statement (EARS)**: The system shall validate and process each create-transaction request independently against the current balance at the time it is received. The system does not implement a dedicated request-idempotency/deduplication mechanism (e.g., a client-supplied idempotency key or header); consequently, a replayed or retried request (such as a network-retry resend) MAY result in more than one accepted transaction if each individual attempt independently satisfies validation. Standard per-request validation (amount > 0, amount ≤ balance for debits, customer exists, caller authorized) still applies without exception to every individual attempt, and the account balance shall still never go negative.

**Source**: SRS §7.2 (general duplicate-prevention expectation, explicitly narrowed for this endpoint by this resolution); Resolved clarification (see [clarifications.md](../clarifications.md) CLA-004, resolved 2026-09-16 via /speckit-clarify session)

**Acceptance Criteria**:
- AC-1: Given a create-transaction request that already succeeded, when the logically identical request is resubmitted as a separate HTTP request, then the resubmission is validated independently and MAY be accepted if it individually passes validation; no dedicated duplicate-request detection prevents this.
- AC-2: Given any individual create-transaction request (original or replayed), when it is processed, then standard amount/balance/authorization validation (QCC-API-006/007/008, QCC-API-005) still applies without exception, and the account balance never becomes negative (see [data-integrity-and-concurrency.md](../non-functional/data-integrity-and-concurrency.md) QCC-DATA-001).

### QCC-API-014 — Resource path conflict resolved

**Statement (EARS)**: The system shall implement the Architecture-variant resource paths (`/V1/quick-consult-credit/balance/:customerId` and `/V1/quick-consult-credit/transactions`) as the sole authoritative REST contract; the SRS path variant shall not be implemented.

**Source**: Resolved clarification (see [clarifications.md](../clarifications.md) CLA-005, resolved 2026-09-15 via /speckit-clarify session)

**Acceptance Criteria**:
- AC-1: Given the implementation plan, when the API path is defined, then it matches the Architecture-variant path exactly for both endpoints.

### QCC-API-015 — Rate limiting relies on platform defaults

**Statement (EARS)**: The system shall rely on Magento's existing platform-level API throttling for the Quick Consult Credit REST endpoints and shall not require an additional feature-specific rate limit.

**Source**: Resolved clarification (see [clarifications.md](../clarifications.md) CLA-014, resolved 2026-09-16 via /speckit-clarify session)

**Acceptance Criteria**:
- AC-1: Given the Quick Consult Credit REST endpoints, when their configuration is inspected, then no feature-specific rate-limit setting exists beyond the platform's standard API throttling configuration.

### QCC-API-016 — V1 contract stability and versioning policy

**Statement (EARS)**: The system shall treat the V1 Quick Consult Credit REST contract (paths, request/response fields, and error codes) as stable. Any backward-incompatible change shall be introduced as a new API version rather than modifying V1 in place.

**Source**: Resolved clarification (see [clarifications.md](../clarifications.md) CLA-015, resolved 2026-09-16 via /speckit-clarify session)

**Acceptance Criteria**:
- AC-1: Given a proposed backward-incompatible change to the Quick Consult Credit REST contract, when the change is implemented, then it is exposed under a new version path/identifier and the existing V1 contract continues to behave exactly as previously specified.
- AC-2: Given a backward-compatible additive change (e.g., a new optional response field), when it is implemented, then it may be added to V1 without a version bump.

### QCC-API-017 — Create-transaction endpoint accepts REDEEM only (resolved)

**Statement (EARS)**: The system shall accept only `transaction_type = REDEEM` on the `POST /V1/quick-consult-credit/transactions` endpoint. `PURCHASE` transactions shall only be created by the system-internal purchase-posting process (see [credit-purchase-posting.md](./credit-purchase-posting.md)), never via this endpoint. `ADMIN_ADD` and `ADMIN_REMOVE` transactions shall only be created via the existing Admin customer-edit Credit tab (see [admin-credit-management.md](./admin-credit-management.md)), never via this endpoint. If a request to this endpoint specifies any `transaction_type` other than `REDEEM`, the system shall reject the request with a validation error before any balance-changing logic executes.

**Source**: Resolved clarification (see [clarifications.md](../clarifications.md) CLA-016, resolved 2026-09-16, following `/speckit-analyze` finding C2)

**Acceptance Criteria**:
- AC-1: Given a request to this endpoint with `transaction_type = REDEEM` and a valid payload, when processed, then it is evaluated per [credit-redemption.md](./credit-redemption.md).
- AC-2: Given a request to this endpoint with `transaction_type` equal to `PURCHASE`, `ADMIN_ADD`, or `ADMIN_REMOVE`, when submitted, then the request is rejected with the `INVALID_TRANSACTION_TYPE` error, no balance change occurs, and no ledger entry is created.

## Error Contract

See [Error Contract](#error-contract-detail) detail below and cross-reference in each consuming specification.

### Error Contract Detail

| Error code | Triggering condition | Externally observable | State change | Ledger entry created | Retryable |
|---|---|---|---|---|---|
| `CUSTOMER_NOT_FOUND` | Referenced customer does not exist | Business error response | No | No | Yes, after correcting customer identifier |
| `INSUFFICIENT_BALANCE` | Debit amount exceeds current balance | Business error response (HTTP 400) | No | No | Yes, after balance changes or amount is reduced |
| `INVALID_REQUEST` | Required field missing (`customer_id`, `transaction_type`, `amount`), or `amount` is non-integer/fractional (QCC-API-019) | Validation error response (HTTP 400) | No | No | Yes, with a corrected, complete, integer-valued request |
| `INVALID_AMOUNT` | Structurally valid integer amount ≤ 0 (QCC-API-006) | Validation error response | No | No | Yes, with a corrected amount |
| `INVALID_TRANSACTION_TYPE` | `transaction_type` on the create-transaction endpoint is not `REDEEM` (QCC-API-017) | Validation error response | No | No | Yes, with `transaction_type` corrected to `REDEEM` |
| `UNAUTHORIZED` | Caller lacks authentication or authorization for the requested customer/action | HTTP 403 (authenticated, unauthorized) or bare HTTP 401 with no body (unauthenticated) — see QCC-API-012 | No | No | Yes, after obtaining valid credentials/permission |
| (not applicable) | A replayed/retried request is resubmitted | No dedicated duplicate-request response exists (resolved CLA-004); the resubmission is validated independently and follows the normal success/error path above based on the balance at that time | Possible (if it independently passes validation) | Possible (a new ledger entry, not a duplicate of the original) | N/A — not treated as a distinct error case |

Any error code not explicitly defined above is not a mandatory part of the public contract of this specification unless documented as a specification decision (see [clarifications.md](../clarifications.md)).

## Related Specifications

- [credit-redemption.md](./credit-redemption.md) — business rules for the debit/redeem transaction type.
- [security-and-access-control.md](../non-functional/security-and-access-control.md) — authentication/authorization/ACL requirements referenced by QCC-API-002, QCC-API-009, QCC-API-011.
- [clarifications.md](../clarifications.md) — CLA-004 (idempotency mechanism, resolved: none implemented), CLA-005 (path conflict, resolved), CLA-007 (customer self-redemption, resolved), CLA-014 (rate limiting, resolved), CLA-015 (versioning policy, resolved), CLA-016 (create-transaction `transaction_type` scope, resolved: REDEEM only), CLA-017 (401 vs. 403 error-payload scope, resolved), CLA-018 (malformed `customerId`, resolved), CLA-019 (fixed create-transaction success status, resolved), CLA-020 (missing/malformed required fields and non-integer amount, resolved: `INVALID_REQUEST`).
