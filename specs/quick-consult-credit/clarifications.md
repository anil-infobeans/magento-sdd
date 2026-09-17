# Ambiguity and Decision Register: Quick Consult Credit

**Version**: 1.6
**Status**: Resolved (20 of 20 items resolved)
**Type**: Non-normative until an item is marked resolved

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial register created from cross-analysis of SRS v1.0 and Technical Architecture | Quick Consult Credit SRS v1.0; Quick Consult Credit Technical Architecture | Establishes decision-tracking baseline for all sub-specifications |
| 1.1 | Resolved CLA-001, CLA-002, CLA-003, CLA-005, CLA-007 via /speckit-clarify session | /speckit-clarify session 2026-09-15 | Unblocks credit-unit semantics, decimal precision, qualifying order state default, REST API path, and redemption authorization model for implementation planning |
| 1.2 | Updated every cross-reference link to the reorganized `functional/`/`non-functional/` sub-specification locations | Constitution v1.4.0 Principle XIII (Specification Organization), 2026-09-16 | Purely structural; no CLA item's status, resolution, or content changed |
| 1.3 | Resolved all 8 remaining open items (CLA-004, CLA-006, CLA-008, CLA-009, CLA-010, CLA-011, CLA-012, CLA-013); added and resolved two new items identified during the same session (CLA-014 API rate limiting, CLA-015 V1 API versioning policy) | /speckit-clarify session 2026-09-16 | Updated [credit-rest-api.md](./functional/credit-rest-api.md), [credit-redemption.md](./functional/credit-redemption.md), [credit-ledger.md](./functional/credit-ledger.md), [credit-purchase-posting.md](./functional/credit-purchase-posting.md), [customer-credit-account.md](./functional/customer-credit-account.md), [security-and-access-control.md](./non-functional/security-and-access-control.md), [audit-and-observability.md](./non-functional/audit-and-observability.md), [testing-and-acceptance.md](./non-functional/testing-and-acceptance.md), [data-model.md](./plan/data-model.md), and [contracts/rest-api.md](./contracts/rest-api.md); no requirement was silently changed without a corresponding requirement-text update |
| 1.4 | Added and resolved CLA-016: `POST /V1/quick-consult-credit/transactions` accepts `transaction_type = REDEEM` only (identified during `/speckit-analyze` as finding C2) | `/speckit-analyze` finding C2, followed by explicit user decision, 2026-09-16 | Updated [credit-rest-api.md](./functional/credit-rest-api.md), [contracts/rest-api.md](./contracts/rest-api.md), [checklists/api.md](./checklists/api.md), and [tasks/tasks.md](./tasks/tasks.md); no other requirement content changed |
| 1.5 | Added and resolved CLA-017 (401 vs. 403 error-payload scope), CLA-018 (malformed `customerId` behavior), CLA-019 (fixed create-transaction success status and field-name hedges), identified during `/speckit-analyze` as findings H2, H3, H4; also fixed the stale CLA-010 cross-reference in [product-configuration.md](./functional/product-configuration.md) (finding H1) | `/speckit-analyze` findings H1-H4, followed by explicit user decisions, 2026-09-16 | Updated [credit-rest-api.md](./functional/credit-rest-api.md), [contracts/rest-api.md](./contracts/rest-api.md), [product-configuration.md](./functional/product-configuration.md), and [checklists/api.md](./checklists/api.md); no other requirement content changed |
| 1.6 | Added and resolved CLA-020 (missing/malformed required create-transaction fields and non-integer `amount` values now rejected with a new `INVALID_REQUEST` validation error, distinct from `INVALID_AMOUNT`); split [spec.md](./spec.md) FR-000-e to correct a contradiction with the already-resolved redemption-replay exception (finding F1); refreshed stale resolved-item counts in [spec.md](./spec.md) and [checklists/requirements.md](./checklists/requirements.md) (findings F2, F3) | `/speckit-analyze` findings F1, F2, F3, I1, followed by explicit user decision, 2026-09-16 | Updated [spec.md](./spec.md), [credit-rest-api.md](./functional/credit-rest-api.md), [contracts/rest-api.md](./contracts/rest-api.md), [checklists/api.md](./checklists/api.md), [checklists/requirements.md](./checklists/requirements.md), and [tasks/tasks.md](./tasks/tasks.md); no other requirement content changed |

## Purpose

This register records every unresolved ambiguity or contradiction found between the Quick Consult Credit SRS v1.0 and the Quick Consult Credit Technical Architecture, and any material gap in either document. No sub-specification may silently resolve these items; each sub-specification instead links back to the relevant entry here. A "current interpretation" is provided only to allow planning/tasking to proceed, and MUST be reconfirmed by the decision owner before implementation sign-off where marked **Blocking implementation: Yes**.

---

### CLA-001

**Status**: Resolved (2026-09-15, /speckit-clarify session)

**Topic**: Credit unit semantics — currency amount vs. abstract "credit point"

**Source**: SRS §4.1 (`credit_balance: 75.00`, dollar-formatted examples), SRS §5.1 ("Available Quick Consult Credit: $75.00"); Technical Architecture §24 ("Purchase 100 qty ... Balance increases by 100 as credit point")

**Conflict / ambiguity**: The SRS presents balances and transaction amounts as currency (dollar-formatted). The Technical Architecture's acceptance scenarios describe the same purchase as producing "credit points" equal to quantity, not a monetary amount, with no currency symbol.

**Why it matters**: Determines whether credit amount is derived from product price × quantity (monetary) or purely from quantity (point-based), which changes purchase-posting calculation logic and all customer-facing display formatting.

**Current interpretation**: Credit amount posted equals purchased quantity (1 unit purchased = 1 credit unit), consistent with Technical Architecture §10 ("calculate credit amount = quantity"). Display formatting may use a currency-like symbol for user familiarity without implying a price-based calculation.

**Decision required**: Confirm whether credit amount is quantity-based or price-based.

**Owner**: Business/Product owner (Quick Consult Credit)

**Resolution**: Quantity-based. 1 unit purchased = 1 credit point, independent of product price. Display formatting does not imply a currency amount.

**Blocking implementation: No (resolved)**

---

### CLA-002

**Status**: Resolved (2026-09-15, /speckit-clarify session)

**Topic**: Ledger/amount decimal precision discrepancy

**Source**: SRS §6.1 (`amount DECIMAL(12,4)`, `previous_balance DECIMAL(12,4)`, `current_balance DECIMAL(12,4)`); Technical Architecture §7.1/§7.2 (`balance DECIMAL(14,2)`, `amount DECIMAL(14,2)`, etc.)

**Conflict / ambiguity**: The two documents specify different decimal precision/scale for monetary/credit fields.

**Why it matters**: Precision affects rounding behavior, display consistency, and cross-system reconciliation with any future point- or currency-based interpretation (see CLA-001).

**Current interpretation**: Treated as an open discrepancy; no default precision is assumed authoritative. Both precisions are recorded here rather than one being silently selected.

**Decision required**: Select a single authoritative decimal precision/scale for balance and transaction amount fields.

**Owner**: Technical architecture owner

**Resolution**: Neither DECIMAL(12,4) nor DECIMAL(14,2) applies. Because CLA-001 resolved credit amounts to whole-number "credit points" with no fractional value, balance and transaction amount fields are specified as integers (no decimal places), superseding both source documents' decimal schemas.

**Blocking implementation: No (resolved)**

---

### CLA-003

**Status**: Resolved (2026-09-15, /speckit-clarify session)

**Topic**: Exact qualifying order/payment state for purchase posting

**Source**: SRS §3.1 ("after successful order and payment completion (e.g., invoice generated and order marked complete)"); Technical Architecture §3, §10 ("configured successful order/payment condition", "should be selected based on the project order-processing strategy")

**Conflict / ambiguity**: The SRS gives only an illustrative example; the Architecture explicitly defers the exact state to configuration/deployment decision.

**Why it matters**: Choosing the wrong qualifying state risks posting credit too early (before payment is final) or too late (delaying legitimate customer access).

**Current interpretation**: The qualifying condition is treated as a required deployment-time configuration value (see [configuration.md](./non-functional/configuration.md) QCC-CONFIG-003) rather than a hardcoded order state. No specific Magento order/invoice status is asserted as correct by this specification set.

**Decision required**: Deployment/business owner must select and document the exact qualifying order/payment state before go-live.

**Owner**: Business owner + order-management architecture owner

**Resolution**: Default qualifying condition = invoice generated (payment captured), independent of order-complete/shipment status. This is the default value of the QCC-CONFIG-003 configuration setting; deployments may still override it.

**Blocking implementation: No (resolved; default established)**

---

### CLA-004

**Status**: Resolved (2026-09-16, /speckit-clarify session)

**Topic**: API idempotency-key contract

**Source**: SRS §7.2 ("Implement mechanisms or checks to ensure duplicate API requests ... do not result in duplicate credit/debit charges"); Technical Architecture §10, §28 (idempotent processor referenced for purchase posting only; no explicit API idempotency-key field defined for the transaction endpoint)

**Conflict / ambiguity**: Neither document defines a client-supplied idempotency key, header, or request-identifier field for the create-transaction API. It is unclear whether de-duplication is based on a client-supplied key, or solely on business-content matching (customer + amount + message + time window).

**Why it matters**: Without an explicit contract, retries after network timeout cannot be reliably distinguished from legitimate repeat requests by API consumers or by the service itself.

**Decision required**: Define whether the API requires a client-supplied idempotency key/reference and its format.

**Owner**: API/integration architecture owner

**Resolution**: No dedicated idempotency/deduplication mechanism is implemented for the create-transaction endpoint. Each request (including a replayed or retried one) is validated and processed independently against the current balance at the time it is received. This explicitly narrows SRS §7.2's general duplicate-prevention expectation for this specific interface: a network retry or other replay of the same logical redemption request MAY result in more than one debit if each individual attempt independently passes validation. Callers are responsible for their own request-uniqueness/retry discipline. Standard per-request validation (amount > 0, amount ≤ balance, customer exists, caller authorized) still applies without exception to every individual attempt, and the balance can still never go negative (see [data-integrity-and-concurrency.md](./non-functional/data-integrity-and-concurrency.md) QCC-DATA-001). This decision does not affect purchase-posting idempotency, which remains governed separately by [credit-purchase-posting.md](./functional/credit-purchase-posting.md) QCC-PURCHASE-001/009/010/011 (order-item deterministic reference).

**Blocking implementation: No (resolved)**

---

### CLA-005

**Status**: Resolved (2026-09-15, /speckit-clarify session)

**Topic**: REST API base resource path conflict

**Source**: SRS §4.1/§4.2 (`GET /V1/customers/:customerId/consult-credit-balance`, `POST /V1/customers/consult-credit-transaction`); Technical Architecture §11.1/§11.2 (`GET /V1/quick-consult-credit/balance/:customerId`, `POST /V1/quick-consult-credit/transactions`)

**Conflict / ambiguity**: The two documents define different, incompatible URL path structures for the same two capabilities.

**Why it matters**: External integration consumers (e.g., consultation platforms) need one authoritative contract; both paths cannot exist as "the" API without explicit versioning/aliasing decisions.

**Current interpretation**: [credit-rest-api.md](./functional/credit-rest-api.md) documents both path variants side by side and does not select one as final. Neither variant is presented to downstream planning as unambiguously correct.

**Decision required**: Select one authoritative path structure (or formally alias both) before REST API implementation.

**Owner**: API/integration architecture owner

**Resolution**: The Technical Architecture path convention is authoritative: `GET /V1/quick-consult-credit/balance/:customerId` and `POST /V1/quick-consult-credit/transactions`. The SRS path variant is not implemented.

**Blocking implementation: No (resolved)**

---

### CLA-006

**Status**: Resolved (2026-09-16, /speckit-clarify session)

**Topic**: Transaction type/direction naming and casing convention

**Source**: SRS §3.2, §6.1 (`transaction_type` enum: `purchase`, `redeem`, `admin_add`, `admin_remove`, lowercase, no separate direction field); Technical Architecture §7.2 (`transaction_type`: `PURCHASE`, `REDEEM`, `ADMIN_ADD`, `ADMIN_REMOVE`, uppercase, plus separate `direction`: `CREDIT`/`DEBIT` field)

**Conflict / ambiguity**: Casing convention differs, and the Architecture introduces a `direction` attribute not present in the SRS schema.

**Why it matters**: Affects the ledger data contract, any API serialization, and downstream reporting/reconciliation consumers.

**Decision required**: Confirm final casing convention and whether `direction` is a first-class stored attribute or derived from `transaction_type`.

**Owner**: Technical architecture owner

**Resolution**: The Technical Architecture convention is authoritative. `transaction_type` is UPPERCASE (`PURCHASE`, `REDEEM`, `ADMIN_ADD`, `ADMIN_REMOVE`), and `direction` (`CREDIT`/`DEBIT`) is a first-class stored attribute, not merely derived at read time. The SRS's lowercase, no-direction-field convention is superseded.

**Blocking implementation: No (resolved)**

---

### CLA-007

**Status**: Resolved (2026-09-15, /speckit-clarify session)

**Topic**: Authorization model for self-service customer redemption via API

**Source**: SRS §4.2 ("Security: Authorized external integration token or Magento admin authentication" — no customer-token path listed for the transaction endpoint); Technical Architecture §11.2 ("Integration/admin ACL as appropriate")

**Conflict / ambiguity**: Neither document explicitly states whether an authenticated customer may call the create-transaction (redeem) API directly on their own behalf, or whether redemption is exclusively initiated by authorized external consultation systems and admins.

**Why it matters**: Determines whether [credit-redemption.md](./functional/credit-redemption.md) and [credit-rest-api.md](./functional/credit-rest-api.md) must support a customer-authenticated redemption path, or only integration/admin-authenticated paths.

**Current interpretation**: Redemption is specified as accessible to authorized external integrations and administrators; a direct customer-self-redemption API path is treated as not-yet-authorized pending confirmation, and is not assumed to exist.

**Decision required**: Confirm whether customers may redeem their own credit directly via API/UI, or only through integrated consultation systems.

**Owner**: Business owner

**Resolution**: Customers may not redeem directly. Redemption is restricted to authorized external integration systems (consultation platforms) and Magento admins only.

**Blocking implementation: No (resolved)**

---

### CLA-008

**Status**: Resolved (2026-09-16, /speckit-clarify session)

**Topic**: Mandatory vs. optional Magento customer extension attribute

**Source**: SRS §6.2 ("Extension Attributes: Integrate customer balance info directly into the core Magento customer data object **where appropriate**"); Technical Architecture §5 (`extension_attributes.xml (only if needed)`)

**Conflict / ambiguity**: Both documents hedge on whether exposing the balance as a Magento customer extension attribute is required.

**Why it matters**: Affects whether other Magento customer-data consumers (e.g., GraphQL customer queries, third-party modules) are expected to see the balance without calling the dedicated API.

**Decision required**: Confirm whether extension-attribute exposure is required for this release.

**Owner**: Technical architecture owner

**Resolution**: Not required for this release. The Quick Consult Credit balance is accessible only through the dedicated REST API ([credit-rest-api.md](./functional/credit-rest-api.md)) and the customer/admin UI; no Magento customer extension attribute is mandated. This narrows scope and avoids exposing balance data through unrelated core-customer-data consumers (e.g., GraphQL) that have not been reviewed for this feature's authorization model.

**Blocking implementation: No (resolved)**

---

### CLA-009

**Topic**: Behavior when a qualifying order is cancelled/refunded after credit has already been posted

**Status**: Resolved (2026-09-16, /speckit-clarify session)

**Source**: SRS §8.1 (reversal explicitly out of scope); Technical Architecture §2, §26 (reversal listed as a future extension point)

**Conflict / ambiguity**: Both documents agree reversal is out of scope, but neither states what (if anything) happens operationally when this situation occurs in production (e.g., manual admin remove as a workaround).

**Why it matters**: Without an explicit statement, support/finance teams may assume the system self-corrects, which it will not.

**Decision required**: Confirm this manual-correction-only interpretation is acceptable to business/finance stakeholders.

**Owner**: Business/Finance owner

**Resolution**: Confirmed. The system performs no automatic reversal. Manually correcting a balance after a post-hoc order cancellation/refund is an out-of-scope operation performed, if at all, via the existing Admin Remove Credit capability with a recorded reason ([admin-credit-management.md](./functional/admin-credit-management.md) QCC-ADMIN-004). No dedicated reversal transaction type or automated workflow is introduced.

**Blocking implementation: No (resolved)**

---

### CLA-010

**Status**: Resolved (2026-09-16, /speckit-clarify session)

**Topic**: Purchase-reference uniqueness granularity

**Source**: SRS §3.3.1 (no explicit reference key defined); Technical Architecture §7.2, §10 (recommends `sales_order_item_id` as the deterministic reference for de-duplication)

**Conflict / ambiguity**: Neither document is fully explicit about whether the uniqueness/replay boundary is per order, per order item, or per some other reference.

**Why it matters**: Determines whether a single order containing multiple qualifying line items posts one ledger entry per order or one per order item, and how duplicate-processing detection is keyed.

**Decision required**: Confirm order-item-level granularity is correct for all qualifying scenarios (e.g., partial invoicing, multi-item orders).

**Owner**: Order-management architecture owner

**Resolution**: Confirmed. Purchase-posting duplicate-prevention is keyed at the order-item level (the qualifying `sales_order_item_id`), consistent with the Technical Architecture's recommendation. A single order with multiple qualifying line items posts one PURCHASE ledger entry per qualifying order item.

**Blocking implementation: No (resolved)**

---

### CLA-011

**Status**: Resolved (2026-09-16, /speckit-clarify session)

**Topic**: Whether admin adjustments affect lifetime purchased/redeemed accumulators

**Source**: SRS §3.2 (fields explicitly named "Total **purchased** credit" and "Total **redeemed** credit", implying purchase/redeem transactions only); Technical Architecture §7.1, §9.1/§9.2 (`total_credited`/`total_debited` updated generically for any CREDIT- or DEBIT-direction operation, which would include ADMIN_ADD/ADMIN_REMOVE)

**Conflict / ambiguity**: The SRS's field naming suggests admin actions should not count toward "purchased"/"redeemed" lifetime totals, while the Architecture's generic direction-based update rule would include them.

**Why it matters**: Affects reporting accuracy and any business metric derived from lifetime totals (e.g., customer lifetime purchase value).

**Decision required**: Confirm whether `total_credited`/`total_debited` should include admin adjustments or be purchase/redeem-only, with a possible additional admin-specific accumulator if they must be kept separate.

**Owner**: Business/Finance owner

**Resolution**: The Technical Architecture convention is authoritative. `total_credited` and `total_debited` increase for every CREDIT- or DEBIT-direction transaction respectively, regardless of transaction type — ADMIN_ADD and ADMIN_REMOVE are included in the same lifetime totals as PURCHASE and REDEEM. No separate admin-only accumulator is introduced. The SRS's narrower field-naming implication is superseded.

**Blocking implementation: No (resolved)**

---

### CLA-012

**Status**: Resolved (2026-09-16, /speckit-clarify session)

**Topic**: Performance/throughput targets

**Source**: Not defined in either SRS or Technical Architecture.

**Conflict / ambiguity**: No quantitative performance or concurrency-load target is provided by either source document.

**Why it matters**: [testing-and-acceptance.md](./non-functional/testing-and-acceptance.md) cannot certify a specific response-time or throughput SLA without one.

**Decision required**: Business/architecture owner must supply a target (e.g., API p95 latency, concurrent-redemption throughput) before performance test acceptance criteria can be finalized.

**Owner**: Business/architecture owner

**Resolution**: No formal performance/throughput SLA is required for this release. This is an explicit decision, not merely an absence: the balance and transaction APIs are accepted on a best-effort basis with no numeric latency, throughput, or concurrency-load target to certify against. This does not relax the correctness requirements (balance never negative, no double-spend) defined in [data-integrity-and-concurrency.md](./non-functional/data-integrity-and-concurrency.md).

**Blocking implementation: No (resolved)**

---

### CLA-013

**Status**: Resolved (2026-09-16, /speckit-clarify session)

**Topic**: Regulatory jurisdiction and data-retention requirements

**Source**: Not defined in either SRS or Technical Architecture.

**Conflict / ambiguity**: Neither document names an applicable jurisdiction, financial regulation, or mandated retention period for the transaction ledger.

**Why it matters**: Determines minimum ledger retention duration and whether additional compliance controls are required.

**Decision required**: Identify applicable jurisdiction(s) and retention requirements, if any, from legal/compliance stakeholders.

**Owner**: Legal/Compliance owner

**Resolution**: No specific regulatory jurisdiction or regulation applies to Quick Consult Credit transaction data. The transaction ledger is retained indefinitely as standard operational/audit data, with no invented compliance claim and no scheduled deletion policy. This specification set continues to require only that the system "preserve an auditable record of every successful credit balance movement sufficient to support operational reconciliation and applicable organizational compliance controls" (see [audit-and-observability.md](./non-functional/audit-and-observability.md) QCC-AUDIT-006).

**Blocking implementation: No (resolved)**

---

### CLA-014

**Status**: Resolved (2026-09-16, /speckit-clarify session)

**Topic**: API rate limiting / throttling

**Source**: Not defined in either SRS or Technical Architecture; identified as a gap during the 2026-09-16 clarification session.

**Conflict / ambiguity**: Neither document states whether the Quick Consult Credit REST endpoints require a feature-specific rate limit beyond whatever Magento's platform already enforces.

**Why it matters**: Determines whether a dedicated throttling configuration must be designed and tested for this feature.

**Decision required**: Confirm whether an additional, feature-specific rate limit is required.

**Owner**: API/integration architecture owner

**Resolution**: No feature-specific rate limit is required. The Quick Consult Credit REST endpoints rely exclusively on Magento's existing platform-level API throttling; no additional per-feature limit is introduced (see [credit-rest-api.md](./functional/credit-rest-api.md) QCC-API-015).

**Blocking implementation: No (resolved)**

---

### CLA-015

**Status**: Resolved (2026-09-16, /speckit-clarify session)

**Topic**: V1 REST contract versioning / backward-compatibility policy

**Source**: Not defined in either SRS or Technical Architecture; identified as a gap during the 2026-09-16 clarification session.

**Conflict / ambiguity**: Neither document states how future breaking changes to the V1 REST contract should be introduced.

**Why it matters**: Without an explicit policy, a future change could silently break existing external integration consumers.

**Decision required**: Confirm the versioning/backward-compatibility policy for the V1 contract.

**Owner**: API/integration architecture owner

**Resolution**: The V1 contract (paths, request/response fields, and error codes) is treated as stable. Any backward-incompatible change is introduced as a new API version rather than modifying V1 in place; backward-compatible additive changes (e.g., a new optional response field) may be added to V1 without a version bump (see [credit-rest-api.md](./functional/credit-rest-api.md) QCC-API-016).

**Blocking implementation: No (resolved)**

---

### CLA-016

**Status**: Resolved (2026-09-16, follow-up decision)

**Topic**: `POST /V1/quick-consult-credit/transactions` — accepted `transaction_type` scope

**Source**: [contracts/rest-api.md](./contracts/rest-api.md) Endpoint 2 (previously hedged: "`REDEEM` for this endpoint's primary use case; other types are governed by their own capability specs... not necessarily this endpoint"); identified as a gap during `/speckit-analyze` (finding C2).

**Conflict / ambiguity**: `CreditTransactionManagementInterface` exposes four operations (credit/debit-redeem/admin-add/admin-remove per [contracts/service-contracts.md](./contracts/service-contracts.md)), but no artifact stated which `transaction_type` values this specific REST endpoint accepts, leaving it unclear whether the endpoint dispatches to all four or only redemption.

**Why it matters**: Determines the request-validation surface, the authorization boundary (admin add/remove already have a dedicated Admin UI path per [admin-credit-management.md](./functional/admin-credit-management.md)), and the API contract test scope (T024).

**Decision required**: Confirm whether the endpoint accepts only `REDEEM`, or all four transaction types.

**Owner**: API/integration architecture owner

**Resolution**: The endpoint accepts **`REDEEM` only**. `PURCHASE` is system-originated exclusively via the purchase-posting observer/processor (never via this endpoint); `ADMIN_ADD`/`ADMIN_REMOVE` are performed exclusively via the existing Admin customer-edit tab (see [admin-credit-management.md](./functional/admin-credit-management.md)), not via this REST endpoint. A request to this endpoint specifying any `transaction_type` other than `REDEEM` is rejected with a validation error before any balance-changing logic executes.

**Blocking implementation: No (resolved)**

---

### CLA-017

**Status**: Resolved (2026-09-16, follow-up decision)

**Topic**: Whether the standardized `{success, error_code, message}` error payload (QCC-API-012) applies to HTTP 401 and/or HTTP 403 responses

**Source**: [credit-rest-api.md](./functional/credit-rest-api.md) QCC-API-012 ("business validation failure"); [contracts/rest-api.md](./contracts/rest-api.md) Endpoint 1 error table (401 documented as "no body disclosure", 403 documented with `UNAUTHORIZED` error_code); identified as a gap during `/speckit-analyze` (finding H2).

**Conflict / ambiguity**: QCC-API-012 did not state whether authentication (401) and authorization (403) failures are "business validation failures" subject to the standardized error payload, while the design contract already treated them differently (401 = no body, 403 = standard payload) without a normative basis.

**Why it matters**: Determines the exact response contract clients must parse for every rejected request, and whether 401 responses can be safely assumed bodyless.

**Decision required**: Confirm whether 401 and/or 403 responses use the standardized error payload.

**Owner**: API/integration architecture owner

**Resolution**: HTTP 403 (authenticated but unauthorized for the requested customer/action) uses the standardized error payload with `error_code = UNAUTHORIZED`. HTTP 401 (no valid authentication at all) does not require a standardized error payload body, consistent with QCC-API-002 (see [credit-rest-api.md](./functional/credit-rest-api.md) QCC-API-012, updated).

**Blocking implementation: No (resolved)**

---

### CLA-018

**Status**: Resolved (2026-09-16, follow-up decision)

**Topic**: Behavior for a malformed or out-of-range `customerId` path parameter on the Get Current Balance endpoint

**Source**: [checklists/api.md](./checklists/api.md) CHK003, CHK023; identified as a gap during `/speckit-analyze` (finding H3).

**Conflict / ambiguity**: No requirement defined behavior for a non-numeric, zero, negative, or out-of-range `customerId` path parameter, distinct from a well-formed but nonexistent customer identifier (QCC-API-003, HTTP 404).

**Why it matters**: Without an explicit rule, implementers might treat malformed input identically to a valid-but-nonexistent customer (404), leaking a distinction between "malformed" and "not found" that the business wants collapsed, or might invent an undocumented 400 response.

**Decision required**: Confirm the error code and HTTP status for malformed/out-of-range `customerId` values.

**Owner**: API/integration architecture owner

**Resolution**: A malformed or out-of-range `customerId` (non-numeric, zero, negative, or exceeding the maximum valid identifier range) is rejected with `error_code = CUSTOMER_NOT_FOUND` and HTTP status 401. This is distinct from a well-formed `customerId` that simply does not correspond to an existing customer, which continues to return HTTP 404 with `error_code = CUSTOMER_NOT_FOUND` per QCC-API-003 (see [credit-rest-api.md](./functional/credit-rest-api.md) QCC-API-018).

**Blocking implementation: No (resolved)**

---

### CLA-019

**Status**: Resolved (2026-09-16, follow-up decision)

**Topic**: Exact HTTP success status code for the create-transaction endpoint, and field-naming/status hedges in the Error Contract Detail

**Source**: [credit-rest-api.md](./functional/credit-rest-api.md) QCC-API-010 ("or equivalently named"), Error Contract Detail ("e.g., HTTP 400"); [contracts/rest-api.md](./contracts/rest-api.md) ("200/201 Success"); identified as a gap during `/speckit-analyze` (finding H4).

**Conflict / ambiguity**: The normative specification left the success status as "200/201" and hedged the response field names and the `INSUFFICIENT_BALANCE` status code, even though the design contract already used concrete values.

**Why it matters**: A dual-valued success status and hedged field names leave room for inconsistent client-facing implementations of the same contract.

**Decision required**: Fix a single success status code and remove the remaining hedges.

**Owner**: API/integration architecture owner

**Resolution**: The create-transaction endpoint returns HTTP 200 on success (never 201). The response field names are fixed as exactly `previous_balance` and `current_balance` (no alternate naming permitted). The `INSUFFICIENT_BALANCE` error is fixed to HTTP 400 (the "e.g." hedge is removed) (see [credit-rest-api.md](./functional/credit-rest-api.md) QCC-API-010, Error Contract Detail; [contracts/rest-api.md](./contracts/rest-api.md)).

**Blocking implementation: No (resolved)**

---

### CLA-020

**Status**: Resolved (2026-09-16, follow-up decision)

**Topic**: Missing/malformed required create-transaction fields and non-integer `amount` values, distinct from the `amount ≤ 0` case

**Source**: [checklists/api.md](./checklists/api.md) CHK001, CHK012; identified as a gap during `/speckit-analyze` (finding I1).

**Conflict / ambiguity**: QCC-API-006 only defined rejection of `amount ≤ 0`; no requirement addressed a request missing a required field entirely (`customer_id`, `transaction_type`, or `amount` absent), or an `amount` supplied as a non-integer/fractional value (e.g., `25.5`), even though CLA-001/CLA-002 already established that credit amounts are whole-number-only.

**Why it matters**: Without an explicit rule, implementers might reuse `INVALID_AMOUNT` for a structurally malformed request (conflating "present but invalid" with "absent" or "wrong type"), or invent an undocumented error code/status.

**Decision required**: Confirm the error code and HTTP status for missing required fields and non-integer `amount` values.

**Owner**: API/integration architecture owner

**Resolution**: A create-transaction request missing any required field (`customer_id`, `transaction_type`, or `amount`), or supplying a non-integer/fractional `amount`, is rejected with a new `error_code = INVALID_REQUEST` and HTTP status 400, before any `INVALID_AMOUNT`/`INVALID_TRANSACTION_TYPE`/business-rule validation is evaluated. This is distinct from `INVALID_AMOUNT` (a structurally valid integer `amount` that is `≤ 0`) (see [credit-rest-api.md](./functional/credit-rest-api.md) QCC-API-019).

**Blocking implementation: No (resolved)**

---

## Summary Table
|---|---|---|
| CLA-001 | Credit unit: currency vs. point | Resolved |
| CLA-002 | Decimal precision discrepancy | Resolved |
| CLA-003 | Exact qualifying order/payment state | Resolved |
| CLA-004 | API idempotency-key contract | Resolved |
| CLA-005 | REST API base path conflict | Resolved |
| CLA-006 | Transaction type casing / direction field | Resolved |
| CLA-007 | Customer self-redemption authorization | Resolved |
| CLA-008 | Extension attribute mandatory/optional | Resolved |
| CLA-009 | Reversal on post-hoc cancellation | Resolved |
| CLA-010 | Purchase-reference granularity | Resolved |
| CLA-011 | Admin adjustments vs. lifetime totals | Resolved |
| CLA-012 | Performance targets | Resolved |
| CLA-013 | Regulatory jurisdiction/retention | Resolved |
| CLA-014 | API rate limiting / throttling | Resolved |
| CLA-015 | V1 API versioning policy | Resolved |
| CLA-016 | Create-transaction endpoint transaction_type scope (REDEEM only) | Resolved |
| CLA-017 | 401 vs. 403 standardized error-payload scope | Resolved |
| CLA-018 | Malformed/out-of-range customerId behavior | Resolved |
| CLA-019 | Fixed create-transaction success status and field-name hedges | Resolved |
| CLA-020 | Missing/malformed required fields and non-integer amount (`INVALID_REQUEST`) | Resolved |
