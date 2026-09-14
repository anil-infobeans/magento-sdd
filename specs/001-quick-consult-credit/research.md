# Phase 0 Research: Quick Consult Credit

This document resolves every engineering decision needed before Phase 1 design. All Technical Context fields in [plan.md](./plan.md) are fully determined (Magento 2.4.8 module on the existing workspace) — there are no outstanding `NEEDS CLARIFICATION` markers. The decisions below primarily address design choices left open by the specification set's ambiguity register (items not resolved during `/speckit-clarify` because they were assessed as low-impact/engineering-detail) plus standard Magento implementation-pattern choices required to move from specification to design.

## Decision 1: Denomination Configuration Mechanism (resolves AMB-001)

- **Decision**: Store configurable denominations as a Magento System Configuration field (`etc/system.xml`, serialized/array-type field under a dedicated "Quick Consult Credit" section) rather than as product custom options or a separate denomination entity/table.
- **Rationale**: Matches QCC-PROD-004/005 ("dynamically configurable... without requiring a code deployment") with the least implementation complexity; Magento System Configuration is the standard, admin-editable, scope-aware (default/website/store) mechanism for store-wide business parameters, and requires no new database table or admin grid. It also keeps denomination changes independent of the product catalog's own versioning/indexing lifecycle.
- **Alternatives considered**:
  - *Product custom options per denomination*: Rejected — ties denomination management to catalog/product-save workflows and product indexing, adding unnecessary coupling for a value that is really a business/configuration parameter, not a catalog attribute.
  - *Dedicated denomination database entity (CRUD grid)*: Rejected as over-engineered for four (initially) simple decimal values; introduces an unnecessary admin grid, repository, and UI component for what System Configuration already solves.

## Decision 2: Purchase-Posting Trigger Mechanism

- **Decision**: Use a Magento event observer (`sales_order_invoice_save_after` and/or `sales_order_save_after`, per QCC-PURCHASE-001 qualifying condition: invoice generated AND status `complete`/`processing` depending on virtual/non-virtual) that calls `CreditPurchaseProcessor::postPurchase()`, which itself re-verifies the qualifying condition and idempotency before posting.
- **Rationale**: Matches Architecture §10/§22.1 sequence diagram and Constitution Principle I (prefer plugins/observers over core modification); observers are the standard Magento extension point for "react to order/invoice lifecycle event," and re-verifying the condition inside the processor (rather than trusting the observer alone) protects against future additional observer registrations or event-firing changes.
- **Alternatives considered**:
  - *Cron-based polling of order status*: Rejected — introduces posting latency and unnecessary complexity versus an event-driven approach; Architecture §20 explicitly prefers event/observer hooks over custom polling.
  - *Synchronous polling inside checkout success controller*: Rejected — purchase posting must react to the *actual* qualifying state (invoice + order status), which is not guaranteed to be reached at the moment checkout completes (e.g., asynchronous payment capture).

## Decision 3: Purchase Idempotency Key Derivation

- **Decision**: Use the Magento `sales_order_item_id` of the Quick Consult Credit line item as the deterministic purchase reference, enforced by a database unique index on `qcc_credit_transaction(reference_type, reference_id)` scoped to `reference_type = 'ORDER_ITEM'`.
- **Rationale**: `sales_order_item_id` is already unique per Magento order line item and is stable across retries/requeues of the same qualifying event (QCC-PURCHASE-007/009, QCC-IDEMP-001), satisfying "exactly once per qualifying purchase reference" without inventing a new identifier scheme.
- **Alternatives considered**:
  - *Order ID alone*: Rejected — an order can contain multiple Quick Consult Credit line items (different denominations/quantities in one cart); order-level granularity would either under- or over-post credit.
  - *Application-generated UUID per posting attempt*: Rejected — does not prevent duplicate posting across independent observer firings for the same order item, which is exactly the case QCC-PURCHASE-009 requires the constraint to catch.

## Decision 4: Concurrency Control Mechanism

- **Decision**: Use a pessimistic row lock (`SELECT ... FOR UPDATE` via Magento's resource-model connection, inside `$connection->beginTransaction()`) on the `qcc_customer_credit` row for the target customer, for every credit/debit/admin-adjust operation, per Architecture §9/§16.
- **Rationale**: Directly satisfies QCC-CONC-001/005/006/007 ("account row is the synchronization point... lock before read... no non-locked read followed by later update"); pessimistic locking is the standard, Magento-supported approach for this exact "read-modify-write with correctness guarantee" pattern and requires no additional infrastructure (e.g., distributed lock service).
- **Alternatives considered**:
  - *Optimistic concurrency (version column + retry-on-conflict)*: Rejected — while viable, it requires client-visible retry semantics that aren't described anywhere in the SRS/Architecture, and would complicate the "at most one succeeds, no partial state" acceptance criterion (QCC-CONC-007) with retry-loop edge cases not currently in scope.

## Decision 5: External API Idempotency-Key Storage & Conflict Handling (touches AMB-006)

- **Decision**: Add a nullable `idempotency_key VARCHAR(128)` column with a database unique index on `qcc_credit_transaction(idempotency_key)` (index applies only to non-null values, per standard MySQL/MariaDB unique-index null semantics). On a request whose `idempotency_key` already exists, return the previously persisted transaction's result rather than re-executing the operation (QCC-IDEMP-004). Payload-conflict detection (AMB-006) is treated as a design-level engineering decision for this plan: THE SYSTEM SHALL compare the incoming request's `customer_id`, `transaction_type`, and `amount` against the stored transaction for that key; if any differ, return a `IDEMPOTENCY_KEY_CONFLICT` business error rather than either silently returning the old result or creating a new transaction.
- **Rationale**: A unique index is required per QCC-IDEMP-005 ("database-level uniqueness constraint... not application-level check-then-insert"); explicit conflict detection prevents a caller from unintentionally masking a materially different request behind a reused key, closing the AMB-006 gap with a safe, conservative default. This design decision does not resolve AMB-006 at the specification/business level (it remains listed as open in the ambiguity register for product/business sign-off) — it only ensures the implementation has deterministic, safe behavior in the interim.
- **Alternatives considered**:
  - *Silently return original result regardless of payload difference*: Rejected as a permanent behavior — could mask a caller-side bug where a different amount is silently ignored; acceptable only as an interim conservative default alongside the explicit conflict error above, not instead of it.

## Decision 6: REST API Exposure & Authorization

- **Decision**: Expose both endpoints via `etc/webapi.xml` with `resources` bound to the ACL structure from QCC-SEC-002 (`ICC_QuickConsultCredit::credit`, `ICC_QuickConsultCredit::manage`), balance retrieval permitting `self` (authenticated customer) access via Magento's built-in customer-token self-authorization plus admin/integration ACL, and transaction-creation restricted to admin/integration ACL only (never plain customer token), consistent with QCC-API-010/QCC-API-016.
- **Rationale**: `webapi.xml` is Magento's standard, framework-enforced mechanism for REST authentication/authorization (Constitution Principle VII, QCC-SEC-001); using its built-in `self` resource semantics for balance retrieval satisfies QCC-CUSTOMER-006/007 (authenticated identity only) without custom token-parsing code.
- **Alternatives considered**:
  - *Custom controller-based REST endpoint outside webapi.xml*: Rejected — bypasses Magento's authentication/ACL framework entirely, violating QCC-SEC-001's explicit prohibition on bespoke authentication mechanisms.

## Decision 7: Admin & Customer UI Integration Points

- **Decision**: Customer dashboard is a new controller + block + layout under `Controller/Account`, `Block/Account/Credit.php`, added to the customer account navigation via `etc/frontend/routes.xml` + navigation layout XML (standard "add a My Account tab" pattern). Admin integration is a new tab on the existing Adminhtml customer edit form via `Block/Adminhtml/Customer/Edit/Tab/Credit.php`, per Architecture §12/§13.
- **Rationale**: Both are the documented, standard Magento extension points for "add a My Account section" and "add a customer-edit tab" respectively (Constitution Principle I/V); no new UI framework or pattern is introduced, preserving UX consistency (Principle V) and avoiding a standalone Admin menu item, which Architecture §13 explicitly discourages unless cross-customer reporting is later required (out of scope here).
- **Alternatives considered**:
  - *Standalone Admin grid/menu item*: Rejected per Architecture §13's explicit guidance and because no cross-customer reporting requirement exists in the current specification set (QCC-SCOPE-008 excludes advanced merchant analytics).

## Decision 8: Testing Strategy Mapping

- **Decision**: Map QCC-TEST-001–007 to concrete Magento test layers:
  - Unit (`Test/Unit`): `Validator/*`, `CreditTransactionManagement` balance-arithmetic and type-mapping logic, idempotency-key conflict-decision logic.
  - Integration (`dev/tests/integration`): DB transaction/row-locking/atomicity (QCC-CONC-002–006), declarative schema creation, resource-model/collection behavior.
  - API-functional (`dev/tests/api-functional`, `WebapiAbstract`): authentication/authorization matrix, payload validation, success/error responses, idempotency replay (QCC-API-*, QCC-SEC-*).
  - MFTF (`Test/Mftf`): customer dashboard rendering/pagination/isolation (QCC-CUSTOMER-*), Admin add/remove flows (QCC-ADMIN-*), end-to-end purchase-to-balance flow (QCC-PURCHASE-*, User Story 1).
  - Concurrency: a dedicated integration/API test that issues two parallel PHP processes (or two overlapping DB transactions coordinated via a test-only synchronization barrier) executing `$80` redemptions against a `$100` seeded balance, asserting the QCC-CONC-007 outcome set.
- **Rationale**: Directly satisfies Constitution Principle IV (testing standards) and QCC-TEST-001–007, using only Magento's existing, supported test frameworks — no new testing tooling is introduced.
- **Alternatives considered**:
  - *Third-party concurrency/load-testing tool (e.g., k6, JMeter) for the concurrency scenario*: Rejected for the initial test suite — adds an external tool dependency for a scenario that two coordinated PHP-level parallel requests can already validate deterministically; may be reconsidered later for broader load testing (out of scope for this plan).

## Summary

All eight decisions above are final for planning purposes. No `NEEDS CLARIFICATION` markers remain in [plan.md](./plan.md) Technical Context. Decision 5's conflict-handling default is explicitly flagged as an interim engineering safeguard, not a resolution of ambiguity-register item AMB-006, which remains open for business/product sign-off per [ambiguity-register.md](./ambiguity-register.md).
