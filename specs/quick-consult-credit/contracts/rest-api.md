# Contract: REST API

**Input**: [credit-rest-api.md](../functional/credit-rest-api.md) (normative requirements); [research.md](../plan/research.md) §6–7 (resolved path; note that the previously-researched idempotency approach was not adopted — see CLA-004)

**Purpose**: Concrete, implementation-ready request/response contract for the two Quick Consult Credit REST endpoints, using the resolved authoritative path convention ([clarifications.md](../clarifications.md) CLA-005) and the resolved whole-number credit-point value type (CLA-001/CLA-002). This document does not define PHP classes or webapi.xml XML — only the wire contract.

## Endpoint 1: Get Current Balance

| Property | Value |
|---|---|
| HTTP method | `GET` |
| Path | `/V1/quick-consult-credit/balance/:customerId` |
| Authentication | Magento customer token (self-access) or Magento admin/integration token |
| Authorization | Customer self-access (own `customerId` only) OR admin/integration ACL resource `ICC_QuickConsultCredit::credit` |

### Request

| Field | Location | Type | Required | Notes |
|---|---|---|---|---|
| `customerId` | path | integer | Yes | Ignored as an authority boundary for a customer-authenticated caller; the authenticated session's own customer ID governs (QCC-API-009) |

### Response — 200 OK

```json
{
  "customer_id": 12345,
  "balance": 75
}
```

| Field | Type | Notes |
|---|---|---|
| `customer_id` | integer | Echoes the resolved customer identity |
| `balance` | integer | Whole-number credit points (never fractional — CLA-001/CLA-002 resolved) |

### Error responses

| HTTP status | `error_code` | Condition | Requirement |
|---|---|---|---|
| 401 | `CUSTOMER_NOT_FOUND` | `customerId` path parameter is malformed (non-numeric, zero, negative, or out of range) | QCC-API-018 |
| 401 | — (no body disclosure) | No valid authentication | QCC-API-002 |
| 403 | `UNAUTHORIZED` | Authenticated but not authorized for the requested customer | QCC-API-009, QCC-SEC-007 |
| 404 | `CUSTOMER_NOT_FOUND` | Well-formed `customerId` does not correspond to an existing customer | QCC-API-003, QCC-API-018 |
| 200 (not an error) | — | Customer exists with no credit account → `balance: 0` | QCC-API-004 |

## Endpoint 2: Create Transaction

| Property | Value |
|---|---|
| HTTP method | `POST` |
| Path | `/V1/quick-consult-credit/transactions` |
| Authentication | Authorized external integration token or Magento admin token only — **no customer self-access** (CLA-007 resolved) |
| Authorization | Integration ACL or Admin ACL resource `ICC_QuickConsultCredit::manage` |

### Request

```json
{
  "customer_id": 12345,
  "transaction_type": "REDEEM",
  "amount": 25,
  "message": "Consultation completed"
}
```

| Field | Type | Required | Notes |
|---|---|---|---|
| `customer_id` | integer | Yes | Target customer. Missing/absent → `INVALID_REQUEST` (QCC-API-019) |
| `transaction_type` | string | Yes | Must be `REDEEM` (see [credit-rest-api.md](../functional/credit-rest-api.md) QCC-API-017, [clarifications.md](../clarifications.md) CLA-016, resolved). `PURCHASE` is system-triggered only (never via this endpoint); `ADMIN_ADD`/`ADMIN_REMOVE` are performed exclusively via the Admin customer-edit Credit tab, never via this endpoint. Any other value is rejected with `INVALID_TRANSACTION_TYPE`. Missing/absent → `INVALID_REQUEST` (QCC-API-019) |
| `amount` | integer (> 0) | Yes | Whole-number credit points. Missing, non-integer, or fractional (e.g., `25.5`) → `INVALID_REQUEST` (QCC-API-019); structurally valid integer ≤ 0 → `INVALID_AMOUNT` (QCC-API-006) |
| `message` | string | No (recommended) | Human-readable reason. No maximum length constraint (QCC-API-020, resolved) |

**No idempotency-key field**: This contract does not include a client-supplied idempotency/replay-detection field (e.g., `request_reference`). Resolved via [clarifications.md](../clarifications.md) CLA-004 (resolved 2026-09-16 via /speckit-clarify session): no dedicated request-deduplication mechanism is implemented; each request is validated independently (see [credit-rest-api.md](../functional/credit-rest-api.md) QCC-API-013).

### Response — 200 Success

```json
{
  "transaction_id": 98765,
  "type": "REDEEM",
  "amount": 25,
  "previous_balance": 100,
  "current_balance": 75
}
```

| Field | Type | Notes |
|---|---|---|
| `transaction_id` | integer | Ledger entry identifier |
| `type` | string | Echoes `transaction_type` |
| `amount` | integer | Whole-number credit points |
| `previous_balance` | integer | Balance before this transaction (QCC-API-010) |
| `current_balance` | integer | Balance after this transaction (QCC-API-010) |

### Error responses (standardized error payload)

```json
{
  "success": false,
  "error_code": "INSUFFICIENT_BALANCE",
  "message": "Requested amount exceeds available balance."
}
```

| HTTP status | `error_code` | Condition | Requirement |
|---|---|---|---|
| 400 | `INVALID_REQUEST` | Required field missing (`customer_id`, `transaction_type`, `amount`), or `amount` is non-integer/fractional | QCC-API-019 |
| 400 | `INVALID_AMOUNT` | `amount <= 0` | QCC-API-006, QCC-REDEEM-004/005 |
| 400 | `INVALID_TRANSACTION_TYPE` | `transaction_type` is not `REDEEM` | QCC-API-017 |
| 400 | `INSUFFICIENT_BALANCE` | `amount > current balance` (debit) | QCC-API-007, QCC-REDEEM-006 |
| 404 | `CUSTOMER_NOT_FOUND` | Customer does not exist | QCC-API-008, QCC-REDEEM-008 |
| 401 | — (no body disclosure) | No valid authentication | QCC-API-002 |
| 403 | `UNAUTHORIZED` | Authenticated but unauthorized caller (customer session or unaffiliated caller) | QCC-API-005, QCC-SEC-007, QCC-REDEEM-009 |

*Note*: There is no distinct "replay" response case. A resubmitted request is processed exactly like any other request against the balance at that time — it succeeds (producing a new transaction) or fails, independently of whether an identical request previously succeeded (CLA-004, resolved).

## Cross-Cutting Contract Rules

- All error responses use the standardized shape: `{ "success": false, "error_code": "...", "message": "..." }` (QCC-API-012).
- No response ever includes another customer's data (QCC-SEC-004).
- No response includes authentication tokens, credentials, or internal exception details (QCC-SEC-005/006).
- No feature-specific rate limit is applied to these endpoints beyond Magento's standard platform-level API throttling (QCC-API-015, [clarifications.md](../clarifications.md) CLA-014, resolved).
- This V1 contract (paths, fields, error codes) is stable; breaking changes require a new API version, while additive backward-compatible changes may be added to V1 without a version bump (QCC-API-016, [clarifications.md](../clarifications.md) CLA-015, resolved).
