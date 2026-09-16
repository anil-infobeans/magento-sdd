# Ambiguity and Decision Register: Quick Consult Credit

**Version**: 1.1
**Status**: Partially resolved (5 of 13 items resolved; 8 remain open, see per-item status)
**Type**: Non-normative until an item is marked resolved

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial register created from cross-analysis of SRS v1.0 and Technical Architecture | Quick Consult Credit SRS v1.0; Quick Consult Credit Technical Architecture | Establishes decision-tracking baseline for all sub-specifications |
| 1.1 | Resolved CLA-001, CLA-002, CLA-003, CLA-005, CLA-007 via /speckit-clarify session | /speckit-clarify session 2026-09-15 | Unblocks credit-unit semantics, decimal precision, qualifying order state default, REST API path, and redemption authorization model for implementation planning |

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

**Current interpretation**: The qualifying condition is treated as a required deployment-time configuration value (see [configuration.md](./configuration.md) QCC-CONFIG-003) rather than a hardcoded order state. No specific Magento order/invoice status is asserted as correct by this specification set.

**Decision required**: Deployment/business owner must select and document the exact qualifying order/payment state before go-live.

**Owner**: Business owner + order-management architecture owner

**Resolution**: Default qualifying condition = invoice generated (payment captured), independent of order-complete/shipment status. This is the default value of the QCC-CONFIG-003 configuration setting; deployments may still override it.

**Blocking implementation: No (resolved; default established)**

---

### CLA-004

**Topic**: API idempotency-key contract

**Source**: SRS §7.2 ("Implement mechanisms or checks to ensure duplicate API requests ... do not result in duplicate credit/debit charges"); Technical Architecture §10, §28 (idempotent processor referenced for purchase posting only; no explicit API idempotency-key field defined for the transaction endpoint)

**Conflict / ambiguity**: Neither document defines a client-supplied idempotency key, header, or request-identifier field for the create-transaction API. It is unclear whether de-duplication is based on a client-supplied key, or solely on business-content matching (customer + amount + message + time window).

**Why it matters**: Without an explicit contract, retries after network timeout cannot be reliably distinguished from legitimate repeat requests by API consumers or by the service itself.

**Current interpretation**: This specification set does not invent a mandatory idempotency-key field. [credit-rest-api.md](./credit-rest-api.md) and [credit-redemption.md](./credit-redemption.md) describe required idempotency **behavior** (duplicate requests must not double-debit) without prescribing the exact key mechanism.

**Decision required**: Define whether the API requires a client-supplied idempotency key/reference and its format.

**Owner**: API/integration architecture owner

**Blocking implementation: Yes**

---

### CLA-005

**Status**: Resolved (2026-09-15, /speckit-clarify session)

**Topic**: REST API base resource path conflict

**Source**: SRS §4.1/§4.2 (`GET /V1/customers/:customerId/consult-credit-balance`, `POST /V1/customers/consult-credit-transaction`); Technical Architecture §11.1/§11.2 (`GET /V1/quick-consult-credit/balance/:customerId`, `POST /V1/quick-consult-credit/transactions`)

**Conflict / ambiguity**: The two documents define different, incompatible URL path structures for the same two capabilities.

**Why it matters**: External integration consumers (e.g., consultation platforms) need one authoritative contract; both paths cannot exist as "the" API without explicit versioning/aliasing decisions.

**Current interpretation**: [credit-rest-api.md](./credit-rest-api.md) documents both path variants side by side and does not select one as final. Neither variant is presented to downstream planning as unambiguously correct.

**Decision required**: Select one authoritative path structure (or formally alias both) before REST API implementation.

**Owner**: API/integration architecture owner

**Resolution**: The Technical Architecture path convention is authoritative: `GET /V1/quick-consult-credit/balance/:customerId` and `POST /V1/quick-consult-credit/transactions`. The SRS path variant is not implemented.

**Blocking implementation: No (resolved)**

---

### CLA-006

**Topic**: Transaction type/direction naming and casing convention

**Source**: SRS §3.2, §6.1 (`transaction_type` enum: `purchase`, `redeem`, `admin_add`, `admin_remove`, lowercase, no separate direction field); Technical Architecture §7.2 (`transaction_type`: `PURCHASE`, `REDEEM`, `ADMIN_ADD`, `ADMIN_REMOVE`, uppercase, plus separate `direction`: `CREDIT`/`DEBIT` field)

**Conflict / ambiguity**: Casing convention differs, and the Architecture introduces a `direction` attribute not present in the SRS schema.

**Why it matters**: Affects the ledger data contract, any API serialization, and downstream reporting/reconciliation consumers.

**Current interpretation**: [credit-ledger.md](./credit-ledger.md) specifies the logical attributes (transaction type, direction, amount, balances) required by both documents' intent, without asserting a single casing convention as final.

**Decision required**: Confirm final casing convention and whether `direction` is a first-class stored attribute or derived from `transaction_type`.

**Owner**: Technical architecture owner

**Blocking implementation: No** (does not block behavioral specification; blocks final data-contract implementation)

---

### CLA-007

**Status**: Resolved (2026-09-15, /speckit-clarify session)

**Topic**: Authorization model for self-service customer redemption via API

**Source**: SRS §4.2 ("Security: Authorized external integration token or Magento admin authentication" — no customer-token path listed for the transaction endpoint); Technical Architecture §11.2 ("Integration/admin ACL as appropriate")

**Conflict / ambiguity**: Neither document explicitly states whether an authenticated customer may call the create-transaction (redeem) API directly on their own behalf, or whether redemption is exclusively initiated by authorized external consultation systems and admins.

**Why it matters**: Determines whether [credit-redemption.md](./credit-redemption.md) and [credit-rest-api.md](./credit-rest-api.md) must support a customer-authenticated redemption path, or only integration/admin-authenticated paths.

**Current interpretation**: Redemption is specified as accessible to authorized external integrations and administrators; a direct customer-self-redemption API path is treated as not-yet-authorized pending confirmation, and is not assumed to exist.

**Decision required**: Confirm whether customers may redeem their own credit directly via API/UI, or only through integrated consultation systems.

**Owner**: Business owner

**Resolution**: Customers may not redeem directly. Redemption is restricted to authorized external integration systems (consultation platforms) and Magento admins only.

**Blocking implementation: No (resolved)**

---

### CLA-008

**Topic**: Mandatory vs. optional Magento customer extension attribute

**Source**: SRS §6.2 ("Extension Attributes: Integrate customer balance info directly into the core Magento customer data object **where appropriate**"); Technical Architecture §5 (`extension_attributes.xml (only if needed)`)

**Conflict / ambiguity**: Both documents hedge on whether exposing the balance as a Magento customer extension attribute is required.

**Why it matters**: Affects whether other Magento customer-data consumers (e.g., GraphQL customer queries, third-party modules) are expected to see the balance without calling the dedicated API.

**Current interpretation**: Extension-attribute exposure is treated as optional/architectural convenience, not a mandatory normative requirement of this specification set.

**Decision required**: Confirm whether extension-attribute exposure is required for this release.

**Owner**: Technical architecture owner

**Blocking implementation: No**

---

### CLA-009

**Topic**: Behavior when a qualifying order is cancelled/refunded after credit has already been posted

**Source**: SRS §8.1 (reversal explicitly out of scope); Technical Architecture §2, §26 (reversal listed as a future extension point)

**Conflict / ambiguity**: Both documents agree reversal is out of scope, but neither states what (if anything) happens operationally when this situation occurs in production (e.g., manual admin remove as a workaround).

**Why it matters**: Without an explicit statement, support/finance teams may assume the system self-corrects, which it will not.

**Current interpretation**: The system performs no automatic reversal. Correcting a balance after a post-hoc order cancellation/refund is an out-of-scope manual operation, performed (if at all) via the existing Admin Remove Credit capability with a recorded reason.

**Decision required**: Confirm this manual-correction-only interpretation is acceptable to business/finance stakeholders.

**Owner**: Business/Finance owner

**Blocking implementation: No** (already resolved as "out of scope" by both sources; recorded for stakeholder awareness only)

---

### CLA-010

**Topic**: Purchase-reference uniqueness granularity

**Source**: SRS §3.3.1 (no explicit reference key defined); Technical Architecture §7.2, §10 (recommends `sales_order_item_id` as the deterministic reference for de-duplication)

**Conflict / ambiguity**: Neither document is fully explicit about whether the uniqueness/replay boundary is per order, per order item, or per some other reference.

**Why it matters**: Determines whether a single order containing multiple qualifying line items posts one ledger entry per order or one per order item, and how duplicate-processing detection is keyed.

**Current interpretation**: [credit-purchase-posting.md](./credit-purchase-posting.md) treats the qualifying order **item** as the deterministic purchase reference, consistent with the Architecture's recommendation, as a reasonable default rather than an invented requirement.

**Decision required**: Confirm order-item-level granularity is correct for all qualifying scenarios (e.g., partial invoicing, multi-item orders).

**Owner**: Order-management architecture owner

**Blocking implementation: No** (reasonable default available; confirm before implementation sign-off)

---

### CLA-011

**Topic**: Whether admin adjustments affect lifetime purchased/redeemed accumulators

**Source**: SRS §3.2 (fields explicitly named "Total **purchased** credit" and "Total **redeemed** credit", implying purchase/redeem transactions only); Technical Architecture §7.1, §9.1/§9.2 (`total_credited`/`total_debited` updated generically for any CREDIT- or DEBIT-direction operation, which would include ADMIN_ADD/ADMIN_REMOVE)

**Conflict / ambiguity**: The SRS's field naming suggests admin actions should not count toward "purchased"/"redeemed" lifetime totals, while the Architecture's generic direction-based update rule would include them.

**Why it matters**: Affects reporting accuracy and any business metric derived from lifetime totals (e.g., customer lifetime purchase value).

**Current interpretation**: [customer-credit-account.md](./customer-credit-account.md) specifies that lifetime accumulators change for every CREDIT- or DEBIT-direction transaction regardless of type (Architecture-aligned default), while flagging that this may not match the SRS's narrower field naming.

**Decision required**: Confirm whether `total_credited`/`total_debited` should include admin adjustments or be purchase/redeem-only, with a possible additional admin-specific accumulator if they must be kept separate.

**Owner**: Business/Finance owner

**Blocking implementation: No** (default available; confirm before implementation sign-off)

---

### CLA-012

**Topic**: Performance/throughput targets

**Source**: Not defined in either SRS or Technical Architecture.

**Conflict / ambiguity**: No quantitative performance or concurrency-load target is provided by either source document.

**Why it matters**: [testing-and-acceptance.md](./testing-and-acceptance.md) cannot certify a specific response-time or throughput SLA without one.

**Current interpretation**: No arbitrary SLA value is invented by this specification set.

**Decision required**: Business/architecture owner must supply a target (e.g., API p95 latency, concurrent-redemption throughput) before performance test acceptance criteria can be finalized.

**Owner**: Business/architecture owner

**Blocking implementation: Yes** (blocking only for performance-acceptance sign-off, not for functional implementation)

---

### CLA-013

**Topic**: Regulatory jurisdiction and data-retention requirements

**Source**: Not defined in either SRS or Technical Architecture.

**Conflict / ambiguity**: Neither document names an applicable jurisdiction, financial regulation, or mandated retention period for the transaction ledger.

**Why it matters**: Determines minimum ledger retention duration and whether additional compliance controls are required.

**Current interpretation**: This specification set does not claim compliance with any specific regulation. It requires only that the system "preserve an auditable record of every successful credit balance movement sufficient to support operational reconciliation and applicable organizational compliance controls" (see [audit-and-observability.md](./audit-and-observability.md) QCC-AUDIT-006).

**Decision required**: Identify applicable jurisdiction(s) and retention requirements, if any, from legal/compliance stakeholders.

**Owner**: Legal/Compliance owner

**Blocking implementation: No** (functional implementation may proceed; retention policy should be confirmed before production data accumulates materially)

---

## Summary Table

| ID | Topic | Blocking |
|---|---|---|
| CLA-001 | Credit unit: currency vs. point | Resolved |
| CLA-002 | Decimal precision discrepancy | Resolved |
| CLA-003 | Exact qualifying order/payment state | Resolved |
| CLA-004 | API idempotency-key contract | Yes |
| CLA-005 | REST API base path conflict | Resolved |
| CLA-006 | Transaction type casing / direction field | No |
| CLA-007 | Customer self-redemption authorization | Resolved |
| CLA-008 | Extension attribute mandatory/optional | No |
| CLA-009 | Reversal on post-hoc cancellation | No |
| CLA-010 | Purchase-reference granularity | No |
| CLA-011 | Admin adjustments vs. lifetime totals | No |
| CLA-012 | Performance targets | Yes (perf sign-off only) |
| CLA-013 | Regulatory jurisdiction/retention | No |
