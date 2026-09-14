# Specification: External REST API Contracts

## Metadata

- **Specification name**: External REST API Contracts
- **Specification identifier**: `QCC-API`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §4.1, §4.2, §7.2
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §11
- **Dependencies**: [account.md](./account.md), [redemption.md](./redemption.md), [security.md](./security.md), [idempotency-concurrency.md](./idempotency-concurrency.md)
- **Impacted existing specifications**: None found for existing Magento REST API conventions in `/specs`; this specification aligns with Magento Web API security conventions rather than introducing a new authentication mechanism

## Purpose

Specify the externally observable behavior of the two Quick Consult Credit REST endpoints — balance retrieval and transaction creation — without prescribing implementation classes.

## Scope

**In scope**: Endpoint semantics, request/response fields, authentication/authorization requirements, validation rules, error contract, idempotency and duplicate-request behavior, concurrency-safe response behavior.

**Out of scope**: Redemption business rules themselves (see [redemption.md](./redemption.md)); purchase posting (system-initiated, not exposed as a caller-invoked API — see [purchase.md](./purchase.md)); admin UI behavior (see [admin.md](./admin.md)).

**Actors**: Authorized external integration, authorized administrator, authenticated customer (self-service balance retrieval), Magento Web API framework.

**System boundaries**: All API operations delegate to the service contracts defined in the domain specifications; the API layer performs authentication, authorization, payload translation, and error mapping only (Architecture §11, §21 "Web API classes... no business logic").

**External dependencies**: Magento Web API security framework (customer token, admin token, integration token).

## Definitions

- **Idempotency key**: A caller-supplied unique value used to detect and safely respond to a replayed state-changing request.
- **Business exception**: A deterministic, named error condition (e.g., `INSUFFICIENT_BALANCE`) distinct from a transport/protocol failure.

## Actors

- **Authorized external integration**: Primary caller of the transaction-creation API for redemption events.
- **Authenticated customer**: Caller of the balance-retrieval API for their own balance (self-access).
- **Authorized administrator**: May call either endpoint under admin authentication where organizationally permitted.

## Functional Requirements

### Balance Retrieval

- **QCC-API-001**: THE SYSTEM SHALL expose an HTTP `GET` endpoint that returns a specified customer's current Quick Consult Credit balance and currency. *(Source: SRS §4.1; Architecture §11.1)*
- **QCC-API-002**: THE SYSTEM SHALL require the requested customer identifier as a URL path parameter. *(Source: SRS §4.1)*
- **QCC-API-003**: THE SYSTEM SHALL require the caller to be authenticated via Magento customer, admin, or integration token authentication to invoke the balance-retrieval endpoint. *(Source: SRS §4.1 "Magento Customer/Admin Token Authentication")*
- **QCC-API-004**: WHEN the request succeeds, THE SYSTEM SHALL return the customer identifier, current balance, and currency code in the response payload. *(Source: SRS §4.1 response payload)*
- **QCC-API-005**: THE SYSTEM SHALL check the caller's authorization for the requested customer before checking whether that customer exists. IF the caller is authorized for the requested customer and that customer does not exist, THEN THE SYSTEM SHALL return a `CUSTOMER_NOT_FOUND` error. *(Source: SRS §4.1; ordering Clarified 2026-09-12, resolves AMB-005)*
- **QCC-API-006**: IF the requested customer exists but has no credit account, THEN THE SYSTEM SHALL return a balance of 0.00 rather than an error. *(Source: SRS §4.1)*
- **QCC-API-007**: IF the caller is not authorized to view the requested customer's balance, THEN THE SYSTEM SHALL return an unauthorized response (HTTP 401) regardless of whether the requested customer exists, and SHALL NOT return `CUSTOMER_NOT_FOUND` or disclose any balance information in that case. *(Source: SRS §4.1; Clarified 2026-09-12, resolves AMB-005)*

### Credit Transaction Creation

- **QCC-API-008**: THE SYSTEM SHALL expose an HTTP `POST` endpoint that accepts an authorized transaction request containing, at minimum: customer identifier, transaction type, amount, message, and reference. *(Source: SRS §4.2; Architecture §11.2)*
- **QCC-API-009**: THE SYSTEM SHALL require `customer_id`, `transaction_type`, and `amount` as mandatory request fields; `message`, `reference_type`/`reference`, and `idempotency_key` are optional except where required by QCC-API-013. *(Source: SRS §4.2 request payload; Architecture §11.2 "Input" list)*
- **QCC-API-010**: THE SYSTEM SHALL require the caller to be authenticated via an authorized external integration token or Magento admin authentication to invoke the transaction-creation endpoint. *(Source: SRS §4.2 "Security")*
- **QCC-API-011**: THE SYSTEM SHALL validate that `amount` is strictly greater than zero, rejecting with `INVALID_AMOUNT` otherwise. *(Source: SRS §4.2, §7.2)*
- **QCC-API-012**: THE SYSTEM SHALL validate that `customer_id` resolves to an existing Magento customer, rejecting with `CUSTOMER_NOT_FOUND` otherwise. *(Source: SRS §4.2)*
- **QCC-API-013**: WHERE the module configuration requires idempotency keys for state-changing external requests (default: enabled, per Architecture §19), THE SYSTEM SHALL require a caller-supplied `idempotency_key` on every transaction-creation request and SHALL reject requests missing one. *(Source: Architecture §15, §19 "API idempotency required — Enabled")*
- **QCC-API-014**: WHEN a transaction-creation request succeeds, THE SYSTEM SHALL return the transaction identifier, transaction type, amount, previous balance, current (resulting) balance, and reference in the response payload. *(Source: SRS §4.2 response payload; Architecture §11.2)*
- **QCC-API-015**: IF the requested `amount` exceeds the customer's available balance for a debit-type transaction, THEN THE SYSTEM SHALL reject the request with `INSUFFICIENT_BALANCE` (mapped to a distinguishable business-error response, e.g., HTTP 400). *(Source: SRS §4.2; Architecture §11.3)*
- **QCC-API-016**: THE SYSTEM SHALL restrict external (non-admin) integration callers to submitting `transaction_type: REDEEM` only via the transaction-creation endpoint. THE SYSTEM SHALL reject a `PURCHASE`, `ADMIN_ADD`, or `ADMIN_REMOVE` transaction-type submission from an external integration caller with an authorization/validation error, regardless of the caller's other granted ACL/integration scope. `PURCHASE` transactions SHALL only ever be created by the system-initiated purchase-posting process (see [purchase.md](./purchase.md)), and `ADMIN_ADD`/`ADMIN_REMOVE` SHALL only ever be created through the admin-authenticated flow (see [admin.md](./admin.md)). *(Source: SRS §4.2 example payload restricted to `redeem`; Clarified 2026-09-12)*
- **QCC-API-017**: WHEN a transaction-creation request is submitted with an `idempotency_key` that matches a previously completed request, THE SYSTEM SHALL NOT create a second balance movement and SHALL return a deterministic response reflecting the original transaction's outcome (see [idempotency-concurrency.md](./idempotency-concurrency.md) QCC-IDEMP-004). *(Source: Architecture §11.3 "Duplicate idempotency key -> Return original transaction or a deterministic duplicate response")*
- **QCC-API-018**: WHEN two transaction-creation requests for the same customer are processed concurrently, THE SYSTEM SHALL ensure the response behavior is retry-safe and SHALL NOT allow a partial update to be observed by either caller (see [idempotency-concurrency.md](./idempotency-concurrency.md) QCC-CONC-003/004). *(Source: Architecture §11.3 "Concurrent conflict -> Retry-safe business response; no partial update")*

### Error Contract

- **QCC-API-019**: THE SYSTEM SHALL return a standardized error payload for all rejected transaction-creation requests containing `success: false`, a deterministic `error_code` (e.g., `INSUFFICIENT_BALANCE`, `INVALID_AMOUNT`, `CUSTOMER_NOT_FOUND`), and a human-readable `message`. *(Source: SRS §7.2 "Standardized Error Payload")*
- **QCC-API-020**: THE SYSTEM SHALL NOT include authentication tokens, credentials, or other secrets in any API response body, including error responses. *(Source: SRS §7.1; see [security.md](./security.md) QCC-SEC-006)*

## Acceptance Criteria

1. **Given** an authenticated caller with authorization for customer 12345, **When** they call the balance-retrieval endpoint, **Then** the response contains `customer_id`, `credit_balance`/`balance`, and `currency`. *(Validates QCC-API-001, QCC-API-004)*
2. **Given** an authorized caller and a non-existent customer identifier, **When** the balance-retrieval endpoint is called, **Then** the response indicates `CUSTOMER_NOT_FOUND`. *(Validates QCC-API-005)*
3. **Given** an existing customer with no credit account, **When** the balance-retrieval endpoint is called, **Then** the response indicates a balance of 0.00. *(Validates QCC-API-006)*
4. **Given** an unauthenticated or unauthorized caller, **When** the balance-retrieval endpoint is called for another customer, **Then** the response is HTTP 401 with no balance data disclosed, whether or not that customer actually exists. *(Validates QCC-API-007)*
5. **Given** a valid authorized redemption request for $25.00 against a $100.00 balance, **When** the transaction-creation endpoint is called, **Then** the response contains `previous_balance: 100.00`, `current_balance: 75.00`, and the supplied reference. *(Validates QCC-API-008, QCC-API-014)*
6. **Given** a redemption request for more than the available balance, **When** the transaction-creation endpoint is called, **Then** the response is a standardized error payload with `error_code: INSUFFICIENT_BALANCE`. *(Validates QCC-API-015, QCC-API-019)*
7. **Given** a transaction-creation request with a previously used `idempotency_key`, **When** the request is replayed, **Then** no new balance movement occurs and the response reflects the original transaction. *(Validates QCC-API-017)*
8. **Given** module configuration requiring idempotency keys, **When** a transaction-creation request omits `idempotency_key`, **Then** the request is rejected. *(Validates QCC-API-013)*
9. **Given** an external integration caller (non-admin), **When** it submits a transaction-creation request with `transaction_type: PURCHASE`, `ADMIN_ADD`, or `ADMIN_REMOVE`, **Then** the request is rejected regardless of the caller's other granted scope. *(Validates QCC-API-016)*

## Traceability

See [traceability.md](./traceability.md). Contributes `QCC-API-001`…`QCC-API-020`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability) |
