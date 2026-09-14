# Contract: REST API

Derived from [api.md](../api.md) (requirements QCC-API-001 through QCC-API-020, post-clarification) and [research.md](../research.md) Decision 6 (webapi.xml exposure & authorization). Schemas only — no implementation code.

## Endpoint 1: Get Credit Balance

```
GET /V1/quick-consult-credit/balance/:customerId
```

- **ACL resource**: `ICC_QuickConsultCredit::credit` (parent), self-access permitted for the owning customer (QCC-API-002).
- **Authorization** (QCC-API-005/007, resolved ordering): Authorization/authentication is evaluated **before** any existence check on `customerId`. An unauthenticated or unauthorized caller always receives `401`/`403` regardless of whether the customer exists.
  - Customer token: only valid when `customerId` equals the token's own customer ID (self-access).
  - Admin/integration token: valid for any `customerId`, subject to ACL grant.

### Request

| Parameter | Location | Type | Required | Notes |
|---|---|---|---|---|
| `customerId` | path | int | Yes | Target customer entity ID. |

### Response — 200 OK

```json
{
  "customer_id": 42,
  "currency_code": "USD",
  "balance": "125.00",
  "total_credited": "250.00",
  "total_debited": "125.00",
  "updated_at": "2026-09-12T10:15:00+00:00"
}
```

### Error Responses

| Status | Condition | Body (`message`) |
|---|---|---|
| 401 | No/invalid authentication token | `"The consumer isn't authorized to access %resources."` (standard Magento webapi message) |
| 403 | Authenticated but not authorized for this `customerId` (non-self, non-admin) | `"The consumer isn't authorized to access %resources."` |
| 404 | Authorized caller, but `customerId` does not reference any existing customer entity | `"No such entity with customerId = %1"` |
| 400 | `customerId` is not a valid positive integer | `"customerId is a required field and must be a positive integer."` |

**Note**: An authorized caller querying a customer who exists but has never had a credit event yet still receives `200` with `balance: "0.00"` (QCC-ACCOUNT-003) — the account row is materialized on first access, not treated as a 404 condition.

## Endpoint 2: Create Credit Transaction (Redeem)

```
POST /V1/quick-consult-credit/transactions
```

- **ACL resource**: `ICC_QuickConsultCredit::manage` (QCC-API-003).
- **Allowed caller types**: Admin/integration tokens only (no customer self-service redemption via this endpoint at this release, QCC-API-004).
- **Allowed `transaction_type` values for external callers**: `REDEEM` only (QCC-API-016, resolved — `PURCHASE`/`ADMIN_ADD`/`ADMIN_REMOVE` are never accepted through this public endpoint; they are posted internally by observers or the Admin UI).
- **Authorization ordering** (QCC-API-005/007): ACL check occurs before the `CUSTOMER_NOT_FOUND` existence check; an unauthorized caller receives `403` even if `customer_id` in the payload does not exist.
- **Idempotency** (QCC-API-009 through QCC-API-012; [research.md](../research.md) Decision 5): `idempotency_key` is required. A repeat request with the same key and identical payload returns the original stored result (200, not a duplicate ledger row). A repeat request with the same key and a **different** payload returns `409 IDEMPOTENCY_KEY_CONFLICT`.

### Request

```json
{
  "customer_id": 42,
  "transaction_type": "REDEEM",
  "amount": "25.00",
  "reference_type": "CONSULTATION",
  "reference_id": "CONSULT-90210",
  "idempotency_key": "b6f1e2b0-....",
  "message": "Redeemed for consultation booking #90210"
}
```

| Field | Type | Required | Validation |
|---|---|---|---|
| `customer_id` | int | Yes | Must reference an existing customer. |
| `transaction_type` | string | Yes | Must equal `"REDEEM"` (any other value → `400`). |
| `amount` | string (decimal) | Yes | Positive, exactly 2 decimal places. |
| `reference_type` | string | Yes | e.g., `"CONSULTATION"`. |
| `reference_id` | string | Yes | Caller-supplied external reference. |
| `idempotency_key` | string | Yes | Unique per logical operation; max 128 chars. |
| `message` | string | No | Free-text note. |

### Response — 200 OK (created or idempotent replay)

```json
{
  "transaction_id": 1057,
  "customer_id": 42,
  "transaction_type": "REDEEM",
  "amount": "25.00",
  "balance_before": "125.00",
  "balance_after": "100.00",
  "created_at": "2026-09-12T10:20:00+00:00"
}
```

### Error Responses

| Status | Code | Condition |
|---|---|---|
| 400 | `INVALID_TRANSACTION_TYPE` | `transaction_type` is not `REDEEM` (QCC-API-016). |
| 400 | `INVALID_AMOUNT` | `amount` non-positive or not 2-decimal. |
| 401/403 | — | Authentication/authorization failure (checked first, QCC-API-005/007). |
| 404 | `CUSTOMER_NOT_FOUND` | Authorized caller, but `customer_id` does not exist (checked after authorization). |
| 409 | `INSUFFICIENT_BALANCE` | Redeem amount exceeds current balance (QCC-REDEEM-003). |
| 409 | `IDEMPOTENCY_KEY_CONFLICT` | Same `idempotency_key` reused with a materially different payload ([research.md](../research.md) Decision 5). |
| 429 | `RATE_LIMITED` | Caller exceeds configured rate limit, if enabled (QCC-API-018, deferred/optional). |
