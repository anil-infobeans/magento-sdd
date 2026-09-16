# Phase 0 Research: Quick Consult Credit

**Input**: [plan.md](./plan.md) Technical Context; [spec.md](./spec.md) and sub-specifications; [clarifications.md](./clarifications.md)

**Purpose**: Resolve all `NEEDS CLARIFICATION` markers from the Technical Context and establish concrete technical decisions for Phase 1 design. Business-level ambiguities already tracked in [clarifications.md](./clarifications.md) are not re-litigated here; where a technical implementation decision is still required to proceed with design (CLA-004, CLA-012), a recommended approach is documented below without overriding the spec's decision to avoid inventing a mandatory business contract.

## 1. Module skeleton and registration

**Decision**: Create `ICC_QuickConsultCredit` as a standalone `app/code` module using standard `registration.php` + `etc/module.xml` conventions, with no dependency on any other custom (non-Magento-core) module.

**Rationale**: Matches Technical Architecture §5 exactly; keeps the module extractable as a Composer package later without rework (Constitution Principle I, VIII).

**Alternatives considered**: Extending an existing customer or sales module directly — rejected because it would require modifying Magento core/vendor modules (violates Principle VIII) and couples an unrelated concern to core modules.

## 2. Persistence and schema strategy

**Decision**: Use Magento declarative schema (`db_schema.xml`) to define `qcc_customer_credit` and `qcc_credit_transaction`. Balance and amount fields are stored as unsigned integers (whole-number "credit points"), per resolved [clarifications.md](./clarifications.md) CLA-001/CLA-002. Resource models extend `Magento\Framework\Model\ResourceModel\Db\AbstractDb`; collections extend `AbstractCollection`.

**Rationale**: Declarative schema is the Magento-supported, upgrade-safe mechanism for schema changes (Constitution Principle IX); integers avoid rounding/precision ambiguity now that credit points are confirmed non-fractional.

**Alternatives considered**: `install`/`upgrade` scripts (deprecated pattern, rejected — Magento 2.4.8 standard is declarative schema); `DECIMAL` columns retained from the original source documents (rejected — superseded by the resolved clarification).

## 3. Atomicity and row-locking strategy

**Decision**: Every balance-changing operation (`CreditTransactionManagement::credit()` / `::debit()`) begins a database transaction, performs a locking read of the customer's `qcc_customer_credit` row (`SELECT ... FOR UPDATE`-equivalent via the resource model's locking read API), validates, writes the ledger row, updates the balance row, and commits; any exception triggers rollback.

**Rationale**: Directly satisfies [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) QCC-DATA-001/003/006 and Technical Architecture §9's requirement that "two parallel redeems must not both succeed against the same pre-transaction balance."

**Alternatives considered**: Optimistic concurrency (version column + retry) — rejected as a first-pass design because it adds client-visible retry complexity without a demonstrated throughput need (no performance target exists per CLA-012); pessimistic row locking is simpler to reason about for correctness and can be revisited if CLA-012 later defines a throughput target that requires it.

## 4. Purchase posting trigger

**Decision**: Implement `OrderCreditPost` as an observer on the qualifying event corresponding to the configured qualifying condition. For the resolved default ("invoice generated / payment captured" — [clarifications.md](./clarifications.md) CLA-003), the natural Magento extension point is the invoice-creation event (fired when an invoice is saved for an order). The observer resolves qualifying Quick Consult Credit order items from the invoice and calls `CreditPurchaseProcessor::postPurchase()` for each.

**Rationale**: Matches Technical Architecture §10 ("prefer that over custom polling") and the resolved default qualifying condition; using a native Magento event avoids polling and keeps the module decoupled from checkout/payment internals (Constitution Principle I).

**Alternatives considered**: Cron-based polling of order status — rejected as unnecessary complexity and latency when a native event exists for the resolved default condition; queue/consumer-based processing — noted as a valid future alternative per Technical Architecture §20 if the project's order-processing strategy already uses queues, but not required for the default synchronous event-driven approach.

## 5. Purchase-posting idempotency mechanism

**Decision**: Use the qualifying order item (`sales_order_item_id`) as the deterministic purchase reference, enforced by a unique constraint/index at the persistence layer (e.g., a unique key on a `source_reference` column in `qcc_credit_transaction`, or an equivalent uniqueness check performed inside the same locked transaction as the credit write) so that a second attempt to post the same order item cannot create a second ledger row.

**Rationale**: Directly implements [credit-purchase-posting.md](./credit-purchase-posting.md) QCC-PURCHASE-001/009/010/011 and Technical Architecture §7.2's recommendation; order-item granularity matches [clarifications.md](./clarifications.md) CLA-010's default interpretation.

**Alternatives considered**: Order-level (not order-item-level) reference — rejected as the default because it cannot cleanly represent multiple qualifying line items in one order; an external deduplication cache (e.g., separate "processed events" table keyed by message ID) — viable but adds an extra moving part beyond what the append-only ledger already provides, so the resource-level unique constraint is preferred as the simpler mechanism.

## 6. REST API idempotency (CLA-004 — recommended technical approach, not a finalized business contract)

**Decision (for planning purposes only)**: Recommend that the create-transaction endpoint accept an optional client-supplied reference (e.g., a `request_reference` field) that, when present, is enforced as unique at the persistence layer in the same manner as purchase posting; when absent, the endpoint falls back to normal single-attempt processing without a de-duplication guarantee beyond the standard validation rules. This recommendation does not modify [credit-rest-api.md](./credit-rest-api.md) or invent a mandatory field in the specification — it is a candidate implementation approach pending the API/integration architecture owner's decision recorded in [clarifications.md](./clarifications.md) CLA-004.

**Rationale**: Provides a technically sound path to satisfy QCC-API-013/QCC-REDEEM-010 without prematurely asserting a mandatory contract field in the normative specification.

**Alternatives considered**: Mandatory idempotency-key header (e.g., `Idempotency-Key`) — a common REST convention, but asserting it as mandatory now would preempt the CLA-004 decision; content-hash-based deduplication (customer + amount + message + short time window) — rejected as a primary mechanism because it is probabilistic and could reject legitimate rapid repeat redemptions.

## 7. REST API surface

**Decision**: Implement the resolved authoritative paths ([clarifications.md](./clarifications.md) CLA-005, resolved) via `etc/webapi.xml`: `GET /V1/quick-consult-credit/balance/:customerId` mapped to `CreditBalanceManagementInterface::getBalance`, and `POST /V1/quick-consult-credit/transactions` mapped to `CreditTransactionManagementInterface`. ACL resources distinguish customer self-access (balance only) from integration/admin-only access (transactions).

**Rationale**: Matches the resolved clarification and Technical Architecture §11; keeps REST controllers as thin translation layers with no business logic (Constitution Principle I; [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) QCC-DATA-008).

**Alternatives considered**: SRS-variant paths (`/V1/customers/.../consult-credit-*`) — explicitly superseded by the resolved clarification; retained in [credit-rest-api.md](./credit-rest-api.md) only for traceability.

## 8. Customer dashboard integration

**Decision**: Add a new My Account section ("Quick Consult Credit") via `Controller/Account/Index.php` + `Block/Account/Credit.php` + frontend layout XML, following the same pattern as existing Magento My Account sections (e.g., orders, addresses). The controller/block reads data exclusively through `CreditBalanceManagementInterface`/`CreditLedgerInterface`, using the authenticated customer session as the sole identity source (never a request parameter).

**Rationale**: Satisfies [customer-dashboard.md](./customer-dashboard.md) QCC-CUSTOMER-001/005/006 and Constitution Principle V (UX consistency via pattern reuse).

**Alternatives considered**: A standalone route outside My Account — rejected as inconsistent with existing Magento UX patterns and the SRS's explicit requirement for a My Account tab.

## 9. Admin integration

**Decision**: Integrate as a new tab on the existing Magento Admin customer edit page (`Block/Adminhtml/Customer/Edit/Tab/Credit.php`), with Add/Remove actions posting to `Controller/Adminhtml/Customer/Credit/Save.php`, which delegates entirely to `CreditTransactionManagementInterface`. No standalone cross-customer admin grid is introduced.

**Rationale**: Matches Technical Architecture §13's explicit recommendation and [admin-credit-management.md](./admin-credit-management.md) QCC-ADMIN-001; avoids unnecessary scope expansion (Constitution Principle XII).

**Alternatives considered**: A dedicated top-level Admin menu item — explicitly deferred by the Technical Architecture ("optional and should not be introduced unless business operations later require cross-customer reporting"); not pursued.

## 10. ACL design

**Decision**: Define `ICC_QuickConsultCredit::credit` as the parent ACL resource with `ICC_QuickConsultCredit::manage` (and, if role granularity requires it during task breakdown, separate `view`/`add`/`remove` child resources) matching Technical Architecture §14.

**Rationale**: Satisfies [security-and-access-control.md](./security-and-access-control.md) QCC-SEC-002/003 for least-privilege administrative access.

**Alternatives considered**: A single coarse-grained ACL resource covering all actions — rejected because it cannot satisfy QCC-SEC-003's requirement for view/add/remove granularity.

## 11. Logging and observability

**Decision**: Log operational failures and key lifecycle events (purchase posted, redemption accepted/rejected, admin adjustment) using Magento's standard logger (`Psr\Log\LoggerInterface`) with structured context fields (customer_id, transaction_type, amount, reference, source), excluding credentials/tokens, per Technical Architecture §18 and [audit-and-observability.md](./audit-and-observability.md) QCC-AUDIT-005.

**Rationale**: Reuses Magento's supported logging mechanism (Constitution Principle III); avoids introducing a custom logging framework.

**Alternatives considered**: Dedicated log file via a custom Monolog handler (`var/log/quick_consult_credit.log`) as suggested in Technical Architecture §18 — retained as an optional enhancement, not a blocking requirement, since the standard logger already satisfies the normative requirement.

## 12. Testing strategy

**Decision**: 
- Unit tests (PHPUnit, `Test/Unit`): validators, amount/precision handling, transaction-type rules.
- Integration tests (PHPUnit, `Test/Integration`, Magento integration test framework): DB transaction atomicity, row locking, ledger/balance consistency, repository/collection behavior.
- API tests (`Test/Api`, Magento API-functional framework conventions): authentication, authorization, payload validation, error contract, replay behavior.
- Functional/regression (MFTF): purchase-to-balance flow, customer dashboard, Admin add/remove, checkout/customer-account/order-processing regression.

**Rationale**: Directly matches [testing-and-acceptance.md](./testing-and-acceptance.md) test-layer coverage table and Constitution Principle IV.

**Alternatives considered**: Relying solely on integration tests for business-rule validation — rejected as insufficient isolation for validator/calculation edge cases (zero/negative amount, exact-balance redemption).

## 13. Performance target (CLA-012 — recommended design-time approach)

**Decision (for planning purposes only)**: No numeric SLA is asserted. The design instead ensures O(1)-style balance reads (materialized balance row, no ledger replay), indexed, paginated ledger queries (composite `customer_id, created_at, entity_id` index per Technical Architecture §7.2), and single-transaction writes, so that whatever numeric target is eventually supplied by the business/architecture owner is more likely to be achievable without redesign.

**Rationale**: Satisfies Constitution Principle VI's guidance to avoid both premature optimization and unnecessary inefficiency, without inventing an arbitrary number (Principle VI explicit prohibition; [testing-and-acceptance.md](./testing-and-acceptance.md) QCC-NFR-001).

**Alternatives considered**: Asserting a placeholder SLA (e.g., "200ms p95") — rejected per the spec's explicit instruction not to invent arbitrary SLA values.

## Outcome

All Technical Context `NEEDS CLARIFICATION` markers are resolved for design purposes:

| Marker | Resolution |
|---|---|
| Performance Goals | No SLA invented; efficient design adopted; formal target remains pending business input (CLA-012, tracked, non-blocking for design) |
| API idempotency-key mechanism | Recommended technical pattern (optional client-supplied reference) documented; final contract field pending API/architecture owner decision (CLA-004, tracked, non-blocking for design) |

Phase 1 design may proceed.
