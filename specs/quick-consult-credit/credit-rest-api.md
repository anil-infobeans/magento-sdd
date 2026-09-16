# Specification: Credit REST API

**Specification**: quick-consult-credit / credit-rest-api
**Version**: 1.1
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-API

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §4.1, §4.2; Technical Architecture §11 | New specification |
| 1.1 | Resolved authoritative resource path (Architecture variant), removed customer-self-access from create-transaction authorization, updated balance value format to whole-number credit points | /speckit-clarify session 2026-09-15 (CLA-001, CLA-005, CLA-007) | QCC-API-001, QCC-API-004, QCC-API-005, QCC-API-014 updated; SRS path variant retained for reference only |

## Purpose

Defines the externally consumable REST API surface for retrieving balance and creating credit/debit transactions, including authentication, authorization, validation, error behavior, and customer-data isolation. The SRS and Technical Architecture originally disagreed on the exact resource path; this was resolved via /speckit-clarify on 2026-09-15 in favor of the Architecture variant (see [clarifications.md](./clarifications.md) CLA-005, resolved). The SRS variant is retained below for traceability only and is not implemented.

## Endpoint: Get Current Balance

| Property | Authoritative (Architecture variant) | SRS variant (superseded, not implemented) |
|---|---|---|
| HTTP method | GET | GET |
| Path | `/V1/quick-consult-credit/balance/:customerId` | `/V1/customers/:customerId/consult-credit-balance` |
| Authentication | Customer self-access or integration ACL | Magento Customer/Admin token |
| Path parameter | `customerId` (integer) | `customerId` (integer) |

**Success response** (authoritative payload): `customer_id` (integer), `balance` (integer credit points; whole number, not a decimal monetary amount — see [clarifications.md](./clarifications.md) CLA-001, CLA-002, resolved).

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

## Endpoint: Create Transaction

| Property | Authoritative (Architecture variant) | SRS variant (superseded, not implemented) |
|---|---|---|
| HTTP method | POST | POST |
| Path | `/V1/quick-consult-credit/transactions` | `/V1/customers/consult-credit-transaction` |
| Authentication | Integration/admin ACL only (no customer self-access — see [clarifications.md](./clarifications.md) CLA-007, resolved) | Authorized external integration token or Magento admin authentication |
| Request payload | `customer_id`, `transaction_type`, `amount`, `message` | `customer_id`, `transaction_type`, `amount`, `message` |
| Success payload | `transaction_id`, `type`, `amount`, `previous_balance`, `current_balance` | `success`, `customer_id`, `transaction_type`, `amount`, `previous_balance`, `current_balance` |

### QCC-API-005 — Create-transaction endpoint exists and is authenticated/authorized to integrations and admins only

**Statement (EARS)**: The system shall expose an authenticated REST endpoint, restricted to authorized external integration systems and Magento admins, that creates a credit or debit transaction for a specified customer. The system shall not accept a directly authenticated customer session as sufficient authorization to call this endpoint on their own behalf.

**Source**: SRS §4.2; Technical Architecture §11.2; Resolved clarification (see [clarifications.md](./clarifications.md) CLA-007, resolved 2026-09-15)

**Acceptance Criteria**:
- AC-1: Given a valid, authorized request payload from an authorized integration or admin, when the endpoint is called, then a transaction is created per [credit-redemption.md](./credit-redemption.md) (for debit) or the applicable credit rules, and the response reports the resulting balance.
- AC-2: Given a request authenticated only as a customer session (no integration/admin authorization), when the endpoint is called, then the request is rejected with an authorization error.

### QCC-API-006 — Invalid amount rejected

**Statement (EARS)**: If the request amount is less than or equal to zero, the system shall reject the request with the `INVALID_AMOUNT` error.

**Source**: SRS §4.2

**Acceptance Criteria**:
- AC-1: Given amount ≤ 0, when submitted, then `INVALID_AMOUNT` is returned and no state change occurs.

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

### QCC-API-009 — Authenticated customer identity is authoritative for self-access endpoints

**Statement (EARS)**: For the Get Current Balance endpoint (the only endpoint operating under a customer-self-access authorization model), the system shall not allow a `customer_id` value supplied in an untrusted request payload or path to override the identity of the authenticated customer.

**Source**: Technical Architecture §12 ("customer_id must not be accepted from arbitrary browser input as an authority boundary"); scope clarified by resolution of CLA-007 (Create Transaction has no customer-self-access path)

**Acceptance Criteria**:
- AC-1: Given an authenticated customer session, when a balance request path/payload supplies a different `customer_id`, then the authenticated session's customer identity governs, not the supplied value.

### QCC-API-010 — Response includes previous and current balance

**Statement (EARS)**: When a create-transaction request succeeds, the system shall include the previous balance and the resulting current balance in the response.

**Source**: SRS §4.2; Technical Architecture §11.2

**Acceptance Criteria**:
- AC-1: Given a successful transaction, when the response is inspected, then it contains both `previous_balance` and `current_balance` (or equivalently named) fields with values consistent with the created ledger entry.

### QCC-API-011 — Distinct authorization models per consumer type

**Statement (EARS)**: The system shall distinguish, for every endpoint, between authentication (identity proof), authorization (permission to perform the requested action), customer ownership (self-access boundary), integration authorization (external system permission), and admin authorization (administrative permission).

**Source**: Technical Architecture §11.1, §11.2, §14

**Acceptance Criteria**:
- AC-1: Given a request from any consumer type, when authorization is evaluated, then the specific authorization category that granted or denied access is determinable from the system's authorization decision (not merely a generic "allowed"/"denied" outcome).

### QCC-API-012 — Standardized error payload

**Statement (EARS)**: When any API request is rejected due to a business validation failure, the system shall return a standardized error payload containing a `success` flag set to false, a defined `error_code`, and a human-readable `message`.

**Source**: SRS §7.2

**Acceptance Criteria**:
- AC-1: Given any rejected request covered by the [Error Contract](#error-contract), when the response is inspected, then it matches the standardized error payload shape.

### QCC-API-013 — Replay/idempotency behavior required, mechanism open

**Statement (EARS)**: The system shall prevent a replayed or retried create-transaction request from producing a duplicate state change, using a replay-detection mechanism to be defined during implementation planning.

**Source**: SRS §7.2; Derived clarification (see [clarifications.md](./clarifications.md) CLA-004)

**Acceptance Criteria**:
- AC-1: Given a request that already succeeded, when logically the same request is resubmitted, then no duplicate balance change or ledger entry results (see [credit-redemption.md](./credit-redemption.md) QCC-REDEEM-010).

### QCC-API-014 — Resource path conflict resolved

**Statement (EARS)**: The system shall implement the Architecture-variant resource paths (`/V1/quick-consult-credit/balance/:customerId` and `/V1/quick-consult-credit/transactions`) as the sole authoritative REST contract; the SRS path variant shall not be implemented.

**Source**: Resolved clarification (see [clarifications.md](./clarifications.md) CLA-005, resolved 2026-09-15 via /speckit-clarify session)

**Acceptance Criteria**:
- AC-1: Given the implementation plan, when the API path is defined, then it matches the Architecture-variant path exactly for both endpoints.

## Error Contract

See [Error Contract](#error-contract-detail) detail below and cross-reference in each consuming specification.

### Error Contract Detail

| Error code | Triggering condition | Externally observable | State change | Ledger entry created | Retryable |
|---|---|---|---|---|---|
| `CUSTOMER_NOT_FOUND` | Referenced customer does not exist | Business error response | No | No | Yes, after correcting customer identifier |
| `INSUFFICIENT_BALANCE` | Debit amount exceeds current balance | Business error response (e.g., HTTP 400) | No | No | Yes, after balance changes or amount is reduced |
| `INVALID_AMOUNT` | Amount ≤ 0, or otherwise fails validation | Validation error response | No | No | Yes, with a corrected amount |
| `UNAUTHORIZED` | Caller lacks authentication or authorization for the requested customer/action | HTTP 401/403 | No | No | Yes, after obtaining valid credentials/permission |
| Duplicate/idempotent request | Same logical request already processed successfully | Original successful result returned, or a defined duplicate-request indicator | No (no additional change) | No (no additional entry) | Not applicable — original result stands |

Any error code not explicitly defined above is not a mandatory part of the public contract of this specification unless documented as a specification decision (see [clarifications.md](./clarifications.md)).

## Related Specifications

- [credit-redemption.md](./credit-redemption.md) — business rules for the debit/redeem transaction type.
- [security-and-access-control.md](./security-and-access-control.md) — authentication/authorization/ACL requirements referenced by QCC-API-002, QCC-API-009, QCC-API-011.
- [clarifications.md](./clarifications.md) — CLA-004 (idempotency-key contract, open), CLA-005 (path conflict, resolved), CLA-007 (customer self-redemption, resolved).
