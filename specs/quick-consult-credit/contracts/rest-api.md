# Contract: REST API

**Input**: [credit-rest-api.md](../credit-rest-api.md) (normative requirements); [research.md](../research.md) §6–7 (resolved path, recommended idempotency approach)

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
| 401 | — (no body disclosure) | No valid authentication | QCC-API-002 |
| 403 | `UNAUTHORIZED` | Authenticated but not authorized for the requested customer | QCC-API-009, QCC-SEC-007 |
| 404 | `CUSTOMER_NOT_FOUND` | Customer identifier does not exist | QCC-API-003 |
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
  "message": "Consultation completed",
  "request_reference": "optional-client-supplied-reference"
}
```

| Field | Type | Required | Notes |
|---|---|---|---|
| `customer_id` | integer | Yes | Target customer |
| `transaction_type` | string | Yes | `REDEEM` for this endpoint's primary use case; other types are governed by their own capability specs (e.g., admin add/remove use the Admin UI path per [admin-credit-management.md](../admin-credit-management.md), not necessarily this endpoint) |
| `amount` | integer (> 0) | Yes | Whole-number credit points |
| `message` | string | No (recommended) | Human-readable reason |
| `request_reference` | string | No | Recommended technical pattern for replay protection ([research.md](../research.md) §6); when supplied and previously seen, the original result is returned instead of creating a duplicate ledger entry. **Not a finalized mandatory contract field** — pending CLA-004 |

### Response — 200/201 Success

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
| 400 | `INVALID_AMOUNT` | `amount <= 0` | QCC-API-006, QCC-REDEEM-004/005 |
| 400 | `INSUFFICIENT_BALANCE` | `amount > current balance` (debit) | QCC-API-007, QCC-REDEEM-006 |
| 404 | `CUSTOMER_NOT_FOUND` | Customer does not exist | QCC-API-008, QCC-REDEEM-008 |
| 401/403 | `UNAUTHORIZED` | Caller is not an authorized integration/admin (including a customer session acting alone) | QCC-REDEEM-009 |
| 200/201 (replay) | — | `request_reference` matches a previously processed request | Original successful result is returned unchanged; no duplicate ledger entry (QCC-API-013, QCC-REDEEM-010) |

## Cross-Cutting Contract Rules

- All error responses use the standardized shape: `{ "success": false, "error_code": "...", "message": "..." }` (QCC-API-012).
- No response ever includes another customer's data (QCC-SEC-004).
- No response includes authentication tokens, credentials, or internal exception details (QCC-SEC-005/006).
