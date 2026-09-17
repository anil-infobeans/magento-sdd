# Implementation Plan: Quick Consult Credit

**Branch**: `quick-consult-credit` | **Date**: 2026-09-15 | **Spec**: [spec.md](../spec.md)

**Input**: Feature specification from `/specs/quick-consult-credit/spec.md` and its 13 sub-specifications, informed by the Quick Consult Credit SRS v1.0 and Quick Consult Credit Technical Architecture (see [docs](../../../docs)).

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Quick Consult Credit introduces a prepaid, customer-owned credit balance sold as a Magento product and consumed against consultation services. The primary requirement is a self-contained Magento module (`ICC_QuickConsultCredit`) that maintains one current-balance account per customer, an immutable append-only transaction ledger, atomic credit/debit operations behind a single service-contract boundary, a REST API for balance retrieval and transaction creation, a customer "Quick Consult Credit" dashboard, and an Admin customer-edit integration for manual Add/Remove Credit. The technical approach follows the Technical Architecture baseline: two database tables (materialized balance + ledger) managed via Magento declarative schema, `CreditTransactionManagement` as the single business entry point enforcing atomicity/row-locking/idempotency, an order-invoice observer for purchase posting, and `webapi.xml`-declared REST endpoints backed by the same service contracts consumed by the customer/admin UI.

## Technical Context

**Language/Version**: PHP 8.3 (project supports ~8.2/8.3/8.4 per `vendor/magento/module-customer` constraints)

**Primary Dependencies**: Magento / Adobe Commerce 2.4.8-p3 framework and core modules only (`magento/framework`, `magento/module-customer`, `magento/module-sales`, `magento/module-webapi`, `magento/module-backend`, `magento/module-authorization`, `magento/module-quote`, `magento/module-checkout`); no new third-party libraries required. `module-quote`/`module-checkout` were added during architecture review (§A15/A16) solely for the T056 guest-checkout-prevention plugin, which must observe `Magento\Checkout\Api\GuestPaymentInformationManagementInterface`.

**Storage**: MySQL/MariaDB via Magento declarative schema (`db_schema.xml`); two new tables — `qcc_customer_credit` (materialized balance) and `qcc_credit_transaction` (append-only ledger)

**Testing**: PHPUnit 10.5 for unit and integration tests (`dev/tests/integration` conventions, module-local `Test/Unit` and `Test/Integration`); Magento API-functional test framework (`dev/tests/api-functional` conventions) for REST contract tests; Magento Functional Testing Framework (MFTF) for storefront dashboard and Admin adjustment scenarios

**Target Platform**: Existing Linux-hosted Magento 2.4.8 (Adobe Commerce) deployment

**Project Type**: Single Magento module (`app/code/ICC/QuickConsultCredit`) — not a frontend/backend split; storefront, Admin, and API surfaces are layers within the same module

**Performance Goals**: None. Resolved via [clarifications.md](../clarifications.md) CLA-012 (resolved 2026-09-16): no formal performance/throughput SLA is required for this release. This is an explicit decision, not an unresolved unknown (see [testing-and-acceptance.md](../non-functional/testing-and-acceptance.md) QCC-NFR-001).

**Constraints**: Every balance-changing operation must be atomic (single DB transaction covering balance + ledger write); balance must never go negative; purchase posting must be idempotent per qualifying order item (`sales_order_item_id`, per CLA-010 resolved). The create-transaction REST API does **not** implement a replay/idempotency-detection mechanism — resolved via [clarifications.md](../clarifications.md) CLA-004 (resolved 2026-09-16): each request is validated and processed independently, and a replayed/retried request MAY produce an additional accepted transaction if it individually passes validation. This is an explicit, accepted narrowing of SRS §7.2's general duplicate-prevention expectation for this interface only; it does not weaken the balance-never-negative or concurrent-redemption-safety invariants (QCC-DATA-001/006), which still apply to every individual request, including replays.

**Scale/Scope**: Standard Magento B2C/B2B customer-account scale (no explicit volume target in source documents); ledger table expected to grow unbounded per customer over the account lifetime, so pagination and composite indexing are required from day one

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| # | Principle | Gate status | Basis |
|---|---|---|---|
| I | Code Quality and Architecture | PASS | Module follows Magento module structure; all balance changes route through service contracts (`CreditBalanceManagementInterface`, `CreditTransactionManagementInterface`); purchase posting uses an observer (least-intrusive extension point) rather than core modification |
| II | Code Maintainability | PASS | Single service layer shared by Admin UI, customer UI, and REST API prevents duplicated business rules (see [data-integrity-and-concurrency.md](../non-functional/data-integrity-and-concurrency.md) QCC-DATA-007) |
| III | PHP and PSR Compliance | PASS | Standard Magento/PSR-4 module autoloading; no deprecated APIs anticipated for Magento 2.4.8 |
| IV | Testing Standards | PASS (planned) | Unit, integration, API, and functional/regression test layers planned per [testing-and-acceptance.md](../non-functional/testing-and-acceptance.md); enforced at task level, not yet executed |
| V | User Experience Consistency | PASS | Customer dashboard reuses the standard My Account section pattern; Admin experience reuses the standard customer-edit tab pattern (no new UX paradigm introduced) |
| VI | Performance | PASS | No numeric SLA is required for this release, per an explicit resolved decision (CLA-012), not an open gap. Design still avoids N+1 queries and full-ledger replay (materialized balance, indexed pagination) as sound engineering practice independent of any SLA |
| VII | Security and Data Handling | PASS | ACL-gated Admin actions, Magento token/session authentication for API and storefront, escaped output in UI templates, no sensitive data in logs (see [security-and-access-control.md](../non-functional/security-and-access-control.md)) |
| VIII | Magento Core Compatibility | PASS | New module at `app/code/ICC/QuickConsultCredit`; no `vendor/` or core file modification |
| IX | Configuration and Deployment Safety | PASS | Declarative schema (`db_schema.xml`) for all persistence changes; `system.xml` for configuration; safe defaults defined in [configuration.md](../non-functional/configuration.md) |
| X | Version Control and Feature Branches | PASS | Work performed on the `quick-consult-credit` feature branch |
| XI | Quality Gates | PASS | Both previously tracked exceptions are now resolved decisions rather than open gaps (CLA-004: no dedicated API idempotency mechanism is implemented, by design; CLA-012: no formal performance SLA is required, by design) — see Notes below; all other gates pass |
| XII | Principle of Least Change | PASS | Entirely new, isolated module; no refactoring of existing Magento modules |

**Overall**: PASS. The two items previously tracked as non-blocking exceptions (CLA-004, CLA-012) were resolved by the 2026-09-16 `/speckit-clarify` session as explicit specification decisions (see [clarifications.md](../clarifications.md)) rather than open gaps requiring future business sign-off. No Complexity Tracking exceptions remain.

## Post-Design Constitution Re-check

*Performed after Phase 1 design ([data-model.md](./data-model.md), [contracts/](../contracts/), [quickstart.md](./quickstart.md)).*

No new violations were introduced during design. The service-contract boundary (single `CreditTransactionManagement` entry point), declarative schema, ACL matrix, and REST contract all reinforce rather than weaken the Constitution Check gates above. The two previously tracked items (CLA-004, CLA-012) are resolved specification decisions, not design violations; [research.md](./research.md) and [tasks/tasks.md](../tasks/tasks.md) have been updated to remove the now-superseded "recommended pattern pending sign-off" language. **Re-check result: PASS (unchanged).**

## Project Structure

### Documentation (this feature)

```text
specs/quick-consult-credit/
├── spec.md                          # Master feature specification (index + cross-cutting requirements)
├── clarifications.md                # Ambiguity/decision register
├── functional/                      # Functional sub-specifications (Constitution Principle XIII)
│   ├── product-configuration.md         # QCC-PROD
│   ├── customer-credit-account.md       # QCC-ACCOUNT
│   ├── credit-ledger.md                 # QCC-LEDGER
│   ├── credit-purchase-posting.md       # QCC-PURCHASE
│   ├── credit-redemption.md             # QCC-REDEEM
│   ├── credit-rest-api.md               # QCC-API
│   ├── customer-dashboard.md            # QCC-CUSTOMER
│   └── admin-credit-management.md       # QCC-ADMIN
├── non-functional/                  # Non-functional sub-specifications (Constitution Principle XIII)
│   ├── security-and-access-control.md   # QCC-SEC
│   ├── audit-and-observability.md       # QCC-AUDIT
│   ├── data-integrity-and-concurrency.md # QCC-DATA
│   ├── configuration.md                 # QCC-CONFIG
│   └── testing-and-acceptance.md        # QCC-NFR / test strategy
├── plan/                            # Planning artifacts (/speckit-plan command outputs)
│   ├── plan.md                          # This file
│   ├── research.md                      # Phase 0 output
│   ├── data-model.md                    # Phase 1 output
│   └── quickstart.md                    # Phase 1 output
├── contracts/                       # Phase 1 design contracts (dedicated subfolder)
│   ├── rest-api.md
│   └── service-contracts.md
├── checklists/                      # Quality checklists (dedicated subfolder)
│   ├── requirements.md
│   └── api.md
└── tasks/                           # Execution artifacts (/speckit-tasks command output)
    └── tasks.md
```

### Source Code (repository root)

```text
app/code/ICC/QuickConsultCredit/
├── registration.php
├── composer.json                          # optional, for future package extraction
├── README.md
├── etc/
│   ├── module.xml
│   ├── di.xml
│   ├── acl.xml
│   ├── db_schema.xml
│   ├── webapi.xml
│   ├── events.xml                         # sales_order_invoice_save_after observer registration
│   └── adminhtml/
│       ├── system.xml                     # module enable/disable, qualifying condition, page size config
│       └── routes.xml
├── Api/
│   ├── Data/
│   │   ├── CreditBalanceInterface.php
│   │   ├── CreditTransactionInterface.php
│   │   └── CreditTransactionSearchResultsInterface.php
│   ├── CreditBalanceManagementInterface.php
│   ├── CreditTransactionManagementInterface.php
│   └── CreditLedgerInterface.php
├── Model/
│   ├── CreditBalance.php
│   ├── CreditTransaction.php
│   ├── ResourceModel/
│   │   ├── CreditBalance.php
│   │   ├── CreditBalance/Collection.php
│   │   ├── CreditTransaction.php
│   │   └── CreditTransaction/Collection.php
│   └── Service/
│       ├── CreditBalanceManagement.php
│       ├── CreditTransactionManagement.php
│       ├── CreditLedger.php
│       ├── CreditPurchaseProcessor.php
│       └── Validator/
│           └── TransactionValidator.php
├── Observer/
│   └── OrderCreditPost.php
├── Controller/
│   ├── Account/
│   │   ├── Index.php
│   │   └── Transactions.php
│   └── Adminhtml/
│       └── Customer/
│           └── Credit/
│               └── Save.php
├── Block/
│   ├── Account/Credit.php
│   └── Adminhtml/Customer/Edit/Tab/Credit.php
├── view/
│   ├── frontend/
│   │   ├── layout/
│   │   ├── templates/
│   │   └── web/css/
│   └── adminhtml/
│       ├── layout/
│       └── templates/
└── Test/
    ├── Unit/
    ├── Integration/
    └── Api/
```

**Structure Decision**: Single self-contained Magento module at `app/code/ICC/QuickConsultCredit`, matching the Technical Architecture baseline (§5 Module Structure). No frontend/backend project split is used — storefront (`Controller/Account`, `Block/Account`), Admin (`Controller/Adminhtml`, `Block/Adminhtml`), and API (`etc/webapi.xml` + `Api/`) are layers within one module, all routed through the `Model/Service/*` service-contract implementations so no business logic is duplicated across layers (Constitution Principle II; [data-integrity-and-concurrency.md](../non-functional/data-integrity-and-concurrency.md) QCC-DATA-007/008).

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No violations require justification. The two items previously tracked here (no numeric performance SLA; API replay/idempotency-key contract) were resolved as explicit specification decisions by the 2026-09-16 `/speckit-clarify` session and are no longer Constitution Check exceptions:

- **Performance (CLA-012)**: [testing-and-acceptance.md](../non-functional/testing-and-acceptance.md) QCC-NFR-001 now states no formal SLA is required for this release. The design still applies efficient-by-default practices (materialized balance, indexed pagination, single-transaction writes) as good engineering practice, independent of this decision.
- **API replay/idempotency (CLA-004)**: [credit-rest-api.md](../functional/credit-rest-api.md) QCC-API-013 now states no dedicated request-idempotency/deduplication mechanism is implemented; each request is validated independently, and a replay MAY be accepted as an additional transaction if it individually passes validation. This is a resolved scope decision, not a deferred design gap.

---

## Architect Review Addendum (Senior Magento Solution Architect)

*This addendum consolidates the architecture, reuse, risk, and traceability analysis requested for implementation readiness. It supplements, and does not contradict, the Summary/Technical Context/Constitution Check/Project Structure sections above. It introduces no new requirements — every statement below traces to an existing requirement ID in the sub-specifications or to an existing Magento capability.*

### A1. Architecture and Affected Magento Modules/Components

Quick Consult Credit is delivered as **one new, self-contained module**, `app/code/ICC/QuickConsultCredit`, with **no modification to any existing Magento module** (Constitution Principle VIII, XII). It integrates with the following existing Magento subsystems purely through supported extension points:

| Existing Magento subsystem | Integration mechanism | Why this mechanism (least-intrusive per Principle I) |
|---|---|---|
| `Magento_Sales` (order/invoice lifecycle) | Observer on the invoice-save event (`sales_order_invoice_save_after`) | Native event hook; no override of `Magento\Sales\Model\Order` or its resource models |
| `Magento_Customer` (My Account, customer entity) | New My Account section (`Controller/Account`, `Block/Account`, frontend layout XML) added via the standard `customer_account` layout handle | Matches the exact extension pattern used by every existing My Account section (orders, addresses); no core template override |
| `Magento_Backend` / Admin customer edit page | New tab added via adminhtml layout XML targeting the existing `customer_form` UI component / edit page | Matches Technical Architecture §13's explicit recommendation; no core Admin controller modification |
| `Magento_Webapi` | New `etc/webapi.xml` routes only | Declarative REST routing; no core API framework changes |
| `Magento_Authorization` (ACL) | New `etc/acl.xml` resource tree under a dedicated `ICC_QuickConsultCredit::*` namespace | Standard ACL extension; does not alter any existing resource |
| `Magento_Catalog` (product/attribute set) | Consumed only via configuration reference (QCC-CONFIG-002); no catalog schema/entity changes | Product and attribute set already exist as first-class Magento entities; feature only reads them |
| Declarative schema (`Magento\Framework\Setup\Declaration`) | New `db_schema.xml` tables only, no changes to any existing table | Additive-only schema footprint |

No B2B, Multi-Source Inventory, Page Builder, or Payment subsystem is touched. The module has **zero dependency on any other custom module** in this workspace (app/code is currently empty).

### A2. Existing Components — Reuse Analysis (evaluated and rejected alternatives)

Per the instruction to inspect existing capabilities before proposing new components, the following **existing Adobe Commerce capabilities were evaluated as potential reuse candidates and explicitly rejected**, with rationale:

| Candidate for reuse | Why it looks similar | Why it was rejected |
|---|---|---|
| `Magento_CustomerBalance` (Store Credit, Adobe Commerce core) | Also models a per-customer monetary balance with a history/comment log and Admin add/subtract UI | (1) Its persistence model (`magento_customerbalance`, `magento_customerbalance_history`) has no `transaction_type` taxonomy (`PURCHASE`/`REDEEM`/`ADMIN_ADD`/`ADMIN_REMOVE`), no `direction`, and no `source_reference` uniqueness column needed for purchase-posting idempotency (QCC-PURCHASE-011) — the data model would need to be extended, which risks modifying a core EE module (violates Principle VIII). (2) Store Credit is deeply wired into checkout/quote totals as a payment/discount mechanism; the spec's Out of Scope explicitly excludes "split payments combining credit with standard payment methods during checkout," so reusing Store Credit would either require disabling most of its behavior or risk unintended checkout interaction. (3) Store Credit's semantics (refund-originated, checkout-redeemed) do not match Quick Consult Credit's semantics (purchase-earned, API/Admin-redeemed only, per CLA-007). Reuse would be a much larger and riskier change than a small additive module. |
| `Magento_Reward` (Reward Points, Adobe Commerce core) | Also models point-based accrual and redemption tied to purchases | Rejected for the same structural reasons as Store Credit: points accrue as a percentage/rate of order totals (not quantity-based, conflicting with QCC-PURCHASE-002), redemption is checkout-integrated, and there is no order-item-level idempotent posting reference matching QCC-PURCHASE-011. |
| `Magento_GiftCardAccount` | Also models a redeemable stored-value balance | Rejected: gift cards are code-based, transferable, and checkout-redeemed by design — none of which match this feature's account-per-customer, non-transferable, non-checkout-redeemed model, and Out of Scope explicitly excludes "peer-to-peer credit transfers." |
| A new admin grid/menu (`Magento_Ui` grid) for cross-customer credit management | Would give merchants a familiar grid UX | Rejected per Technical Architecture §13 and [admin-credit-management.md](../functional/admin-credit-management.md) QCC-ADMIN-001, which scope Admin management to the existing customer-edit page only; a standalone grid is explicitly deferred, not required by any requirement ID, and would be unnecessary scope expansion (Principle XII). |

**What IS reused** (standard Magento mechanisms, not specific modules): declarative schema, service contracts/repository pattern, `webapi.xml` REST declaration, `acl.xml`, `system.xml` configuration, the observer pattern, the My Account section layout pattern, and the Admin customer-edit tab pattern. These are Magento *framework* conventions, not another module's business logic, so their reuse carries no coupling risk.

**Conclusion**: No existing Magento or Adobe Commerce module satisfies Quick Consult Credit's requirements without extensive, risky modification. A new, additive, self-contained module is the correct — and least-change — architecture. This confirms (does not change) the architecture already recorded in Project Structure above.

### A3. New Components Required

All new components are additive under `app/code/ICC/QuickConsultCredit` exactly as enumerated in the Project Structure section above (Api/, Model/, Observer/, Controller/, Block/, view/, etc/, Test/). No component list is duplicated here; see Project Structure and the Task Traceability Matrix (§A15) for the authoritative, per-file breakdown.

### A4. Data Model / Persistence Changes

Two new, additive tables only (declarative schema, Constitution Principle IX): `qcc_customer_credit` and `qcc_credit_transaction`. Full field-level definitions, validation rules, uniqueness constraints, and indexing are already specified in [data-model.md](./data-model.md) and MUST NOT be duplicated or re-derived here — this plan references that document as authoritative. No existing Magento table is altered. No entity is deleted as part of normal operation (append-only ledger).

### A5. Service Contracts and API Changes

The full interface-level contract (four `Api/*Interface` boundaries) and the two REST endpoints are already specified in [contracts/service-contracts.md](../contracts/service-contracts.md) and [contracts/rest-api.md](../contracts/rest-api.md) — both are treated as authoritative and referenced, not restated. Net-new public API surface: `GET /V1/quick-consult-credit/balance/:customerId`, `POST /V1/quick-consult-credit/transactions`. No existing Magento REST endpoint is modified. The V1 contract is frozen per QCC-API-016 (CLA-015 resolved): any future breaking change ships as a new version rather than mutating V1.

### A6. External Integrations and Failure/Retry/Idempotency Behavior

There is exactly one external-facing integration surface: the `POST /V1/quick-consult-credit/transactions` REST endpoint, consumed by "authorized external integration systems" (per SRS and QCC-API-005). Its failure/retry/idempotency contract is now fully resolved and MUST be communicated to any integrating system as follows:

- **No request-level idempotency/deduplication mechanism exists** (CLA-004, resolved). A network-level retry of a request that already succeeded is validated independently and MAY be accepted as an additional transaction if it individually passes validation (QCC-API-013). Integrating systems that require exactly-once semantics MUST implement their own client-side deduplication (e.g., checking the resulting ledger via `GET` before retrying) — this is an integration-owner responsibility, not a platform guarantee, and MUST be called out in integration onboarding documentation.
- **Purchase posting (system-internal, not an external integration) IS idempotent** at the order-item level (`sales_order_item_id`, QCC-PURCHASE-011, CLA-010 resolved), enforced by a persistence-layer uniqueness constraint — an invoice-save event retry/redelivery cannot double-post.
- **Rate limiting**: no feature-specific limit; relies entirely on Magento's platform-level API throttling (QCC-API-015, CLA-014 resolved). No additional infrastructure (e.g., a dedicated API gateway) is introduced.
- **Failure mode for validation rejections**: deterministic, typed business errors (`INVALID_AMOUNT`, `INSUFFICIENT_BALANCE`, `CUSTOMER_NOT_FOUND`, `UNAUTHORIZED`) with no partial state change (QCC-DATA-003) — safe to retry after correcting the underlying condition.
- **Failure mode for persistence/infrastructure errors**: full transaction rollback (balance and ledger both unaffected), structured operational logging without sensitive data (QCC-AUDIT-005), safe to retry.

### A7. Frontend / Admin Impact

- **Storefront (Frontend)**: One new My Account section ("Quick Consult Credit") — net-new navigation entry, balance display, paginated history. No change to checkout, cart, or any existing My Account section. No new customer-facing payment method is introduced (explicitly excluded by Out of Scope).
- **Admin**: One new tab on the existing customer edit page — net-new balance/lifetime-totals/history display plus Add/Remove Credit forms. No change to any existing Admin grid, menu structure, or customer-edit tab. No new top-level Admin menu item (explicitly deferred per Technical Architecture §13 and confirmed non-required by any requirement ID).
- Both surfaces reuse existing Magento UI patterns (Constitution Principle V) and read/write exclusively through the shared service contracts (§A5) — no duplicated business logic in either layer (QCC-DATA-007/008).

### A8. Security, Privacy, Audit, and Regulatory Controls

- **AuthN/AuthZ**: Magento-native session/token authentication only (QCC-SEC-001); dedicated ACL resource tree `ICC_QuickConsultCredit::credit` / `::manage` for least-privilege Admin/integration access (QCC-SEC-002/003); authorization checked before any balance-changing logic executes (QCC-SEC-007); negative-balance and authorization enforcement live at the shared service boundary, not only in the UI/API layer (QCC-SEC-009, QCC-DATA-007/008).
- **Customer isolation**: no customer can ever access another customer's balance/history, enforced identically across dashboard, Admin, and API (QCC-SEC-004, QCC-CUSTOMER-005/006).
- **Data handling**: no credentials, tokens, or internal exception detail in logs or user-facing errors (QCC-SEC-005/006, QCC-AUDIT-005); no PII beyond the existing Magento customer identity is introduced.
- **Audit**: every successful balance-changing operation produces exactly one immutable, attributable ledger entry (who/what/when/why) sufficient for reconciliation (QCC-AUDIT-001–004).
- **Regulatory posture**: the specification makes **no compliance claim** to any named regulation; the ledger is retained indefinitely as standard operational/audit data with no defined purge/retention job (QCC-SEC-010, QCC-AUDIT-006, CLA-013 resolved). **Open risk, not a defect**: if a future legal/compliance review determines a specific regulation *does* apply (e.g., financial services record-keeping rules in a specific jurisdiction), retention/erasure behavior would need re-specification — flagged in Open Questions (§A14).
- **Replay/idempotency security implication**: because no dedicated idempotency mechanism exists on the create-transaction endpoint (CLA-004), integration credential compromise or client-side retry bugs carry a higher risk of duplicate legitimate-looking debits than a typical idempotent financial API. This is an accepted, explicitly resolved risk (not an oversight) — mitigated only by standard authentication/authorization controls and integration-owner-side deduplication, not by the platform. Flagged again in Risks (§A14).

### A9. Performance, Caching, Scalability, and Reliability

- No formal SLA is required (QCC-NFR-001, CLA-012 resolved) — this is a scope decision, not a gap to be filled during implementation.
- Reliability is enforced structurally, independent of any SLA: every balance-changing operation is atomic (single DB transaction, full rollback on failure — QCC-DATA-003), balance is always non-negative under concurrency (QCC-DATA-001/006), and reads use a materialized balance (no ledger replay) plus indexed, paginated ledger queries (composite `customer_id, created_at, entity_id` index).
- No new caching layer is introduced; the materialized balance row itself serves the role a cache would otherwise play, avoiding cache-invalidation complexity for a value that changes on every transaction.
- Scalability constraint: the ledger table grows unbounded per customer over the account lifetime (no archival/expiry policy is in scope) — pagination and indexing are mandatory from day one (already reflected in the data model), and this should be revisited operationally if/when retention policy changes (see A8).

### A10. Logging, Monitoring, and Observability

- Uses Magento's standard `Psr\Log\LoggerInterface` exclusively (Constitution Principle III) — no custom logging framework introduced.
- Structured context on every operational failure: customer identifier, transaction type, amount, reference, source, exception summary; credentials/tokens are always excluded (QCC-AUDIT-005, QCC-NFR-003).
- The immutable ledger itself is the primary observability/reconciliation source (QCC-AUDIT-004) — no separate metrics store is required by any requirement ID. A dedicated log file/channel (Technical Architecture §18's suggestion) remains an optional enhancement, not a blocking requirement, consistent with [research.md](./research.md) §11.
- No monitoring/alerting requirement (e.g., paging on threshold breach) exists in any sub-specification; none is introduced here to avoid inventing an unrequested requirement (Principle of Least Change).

### A11. Migration / Configuration / Deployment Impact

- **Migration**: none required for existing data — both tables are net-new; no existing Magento entity is altered or backfilled.
- **Deployment sequence**: `bin/magento module:enable ICC_QuickConsultCredit` → `setup:upgrade` (creates schema) → `setup:di:compile` → `cache:flush`, exactly as documented in [quickstart.md](./quickstart.md). Standard Magento deployment pipeline (Composer install → compile → deploy) applies; no new deployment infrastructure is introduced.
- **Configuration required before go-live** (merchant/deployment-time, not code): create the Quick Consult Credit product and assign it to the Consultation Services attribute set (QCC-PROD-001/002 — **identified as a coverage gap and now added as T055**, see §A14); confirm the qualifying-condition default or override it (QCC-CONFIG-003); confirm/adjust the customer-history page size (QCC-CONFIG-004); grant the `ICC_QuickConsultCredit::manage` ACL resource to the appropriate Admin roles.
- **Rollback**: disabling the module (QCC-CONFIG-001) stops all posting/redemption/dashboard/admin activity without data loss; declarative schema changes are additive-only, so no destructive rollback script is required to fall back to a pre-feature state at the database level (tables simply become unused, not corrupting other data).

### A12. CI/CD and Quality Gates

The workspace already provides the necessary static-analysis tooling as Composer dependencies — no new tooling needs to be introduced, only wired into a pipeline and this module's test suites:

| Gate | Tooling already present in `vendor/` | Enforcement point |
|---|---|---|
| Magento coding standard | `magento/magento-coding-standard` (PHP_CodeSniffer ruleset) | Pre-commit / CI, per Constitution Principle XI |
| Static analysis | `phpstan/phpstan` | CI, per Constitution Principle XI ("static analysis without unresolved critical errors") |
| Mess/complexity analysis | `phpmd/phpmd` | CI (recommended; not separately mandated by the constitution but already available) |
| SonarQube (Blocker/Critical + Security Hotspots) | SonarQube for IDE / Connected Mode (per Constitution Principle XI) | **Before every commit**, not merely before merge — mandatory per Principle XI |
| Unit tests | `phpunit/phpunit` via `dev/tests/unit` | CI |
| Integration tests | `phpunit/phpunit` via `dev/tests/integration` | CI (requires a configured integration DB) |
| API-functional tests | `dev/tests/api-functional` framework | CI |
| Functional/regression tests | `magento/magento2-functional-testing-framework` (MFTF) | CI (or a scheduled pipeline stage, given MFTF's runtime cost) |

No `.github/workflows` pipeline currently exists in this repository; **a new CI workflow definition is required** (out of scope for this plan's file list but flagged as a dependency — see §A14) to run the above gates automatically per Constitution Principle X ("BEFORE integration, THE feature branch SHALL pass applicable static analysis, coding-standard checks, unit tests, integration tests, and relevant functional tests"). Per Constitution Principle X, each `tasks.md` phase (Setup, Foundational, US1, US2, US3, Polish) is implemented on its own phase branch and merged via PR to the owning EPIC branch or `dev`, with this quality-gate set enforced before each such PR.

### A13. Testing Strategy Summary (traceability to test layers)

Full test-layer coverage is already defined in [testing-and-acceptance.md](../non-functional/testing-and-acceptance.md) (Unit, Integration, API, Magento functional, Regression, Security, Performance-N/A) and is realized task-by-task in the Task Traceability Matrix (§A15) via `Test/Unit`, `Test/Integration`, `Test/Api`, and `Test/Mftf` suites. No additional test layer is introduced beyond what that specification already mandates; this section exists only to confirm the mapping is complete (verified in §A16).

### A14. Dependencies, Risks, and Open Questions

**Dependencies**:
- Magento 2.4.8 core modules only: `Magento_Customer`, `Magento_Sales`, `Magento_Webapi`, `Magento_Backend`, `Magento_Authorization` (already present in this workspace's `vendor/magento`).
- Existing Consultation Services attribute set and Quick Consult Credit product must be created as deployment/catalog data (see A11) — not a code dependency, but a go-live blocker if omitted.
- No dependency on any other in-flight feature/spec in this workspace (`app/code` is currently empty).

**Risks** (none block planning; all are accepted/documented per the resolved clarifications):
1. **Duplicate-debit risk on the create-transaction API** (CLA-004 resolved as "no mechanism") — an integration bug or aggressive client retry logic can cause a real, financially-observable duplicate redemption. Accepted as an explicit, documented scope decision; mitigation is client-side (integration owner), not platform-side. *Recommend* this risk be called out in integration-partner-facing API documentation, though no requirement ID mandates authoring that document.
2. **Unbounded ledger growth** with no retention/archival policy (consequence of CLA-013's "retain indefinitely" resolution) — long-term storage growth is an accepted operational cost, not a defect.
3. **No performance SLA** (CLA-012 resolved) means there is no objective, testable performance gate for this release; "no avoidable performance regression" (Constitution Principle XI) will be judged qualitatively (query plans, index usage) rather than against a numeric target.
4. **No existing CI pipeline** in this repository (§A12) — quality gates exist as tooling but are not yet automated to run on every push/PR; this is a process dependency, not a code risk, but should be resourced before Phase 1 (Setup) work begins.

**Open Questions** (flagged per the explicit instruction to surface ambiguity rather than assume; none of these are unresolved *specification* clarifications — all 15 CLA items are resolved — they are downstream *operational/process* questions the specification does not and should not answer):
1. Who owns creating the Quick Consult Credit product/attribute-set assignment in each environment (dev/staging/prod), and is that captured in an existing deployment runbook, or does this feature need to add one?
2. Should integration-partner-facing documentation about the "no idempotency mechanism" behavior (§A6, Risk 1) be authored as part of this feature's delivery, or is it out of scope for the Magento codebase itself (e.g., owned by a separate API-partner-docs process)?
3. Is a CI pipeline definition (§A12) considered in-scope deliverable for this feature's task list, or is it a separate, pre-existing organizational responsibility this feature should simply assume exists by the time of merge?

### A15. Task Traceability Matrix

**Single source of truth**: The authoritative Task ID / Requirement ID(s) / Component / Objective / Dependencies / Validation mapping for every task (T001–T059, including T055/T056, which close the QCC-PROD-001–004 coverage gap identified during this review — §A14, Dependency 2 — and T057–T059, which close the CI/CD gap noted in §A12/§A14) is maintained exclusively in [tasks/tasks.md](../tasks/tasks.md)'s own "## Task Traceability Matrix" section, since `tasks.md` is the execution artifact (Constitution Principle XIII) and is kept current as tasks are added, resequenced, or reconciled.

This plan previously duplicated that table with its own copy of the "Dependencies" column, which drifted out of sync with `tasks.md` for several tasks (T014, T026, T037, T038, T043, T054 — identified as `/speckit-analyze` finding W2, 2026-09-16). Rather than maintain two competing copies, the duplicate table has been removed from this plan; refer to [tasks/tasks.md](../tasks/tasks.md) for the current, authoritative task-dependency data.

### A16. Requirement → Task → Test/Acceptance Criterion Coverage Confirmation

*Regenerated 2026-09-16 against the current spec/tasks versions (`/speckit-analyze` finding W7 — the prior version of this table was last verified before [credit-rest-api.md](../functional/credit-rest-api.md) reached v1.9 (QCC-API-017–020 added) and [audit-and-observability.md](../non-functional/audit-and-observability.md) reached v1.3 (QCC-AUDIT-007 added), and understated QCC-API's range as "001–016"). Cross-checking every requirement ID prefix and its current upper bound in [spec.md](../spec.md#specification-index) against the authoritative Task Traceability Matrix in [tasks/tasks.md](../tasks/tasks.md):*

| Prefix | Requirements (current) | Coverage |
|---|---|---|
| QCC-PROD | 001–008 | 001–003 → T055; 004 → T056; 005–008 → T028 |
| QCC-ACCOUNT | 001–008 | T004, T012, T015 |
| QCC-LEDGER | 001–009 | T005, T007, T008, T013 |
| QCC-PURCHASE | 001–012 | T025, T027, T028 |
| QCC-REDEEM | 001–012 | T014, T016, T024, T031 |
| QCC-API | 001–020 *(was 001–016; credit-rest-api.md now v1.9)* | 001–004/009 → T023, T030; 005–008/010/012/013/017/019 → T024, T031, T032, T033; 011 → T032, T054; 014 → T030/T031 (path-conflict resolution verified by route definition, no separate task); 015 → verified by inspection, no feature-specific rate-limit task exists (deliberate absence per CLA-014); 016 → verified by inspection of the V1 contract's stability (no versioning-policy task; policy statement only); 018 → T023, T030; 020 → verified by inspection — no length-validation task exists, consistent with the "no maximum length constraint" decision (deliberate absence, not a gap) |
| QCC-CUSTOMER | 001–006 | T035, T037–T042 |
| QCC-ADMIN | 001–009 | T043–T049 |
| QCC-SEC | 001–010 | T016, T019, T030–T032, T036, T046, T054 |
| QCC-AUDIT | 001–007 *(was 001–006; audit-and-observability.md now v1.3)* | 001–006 → T034, T044, T049, T054; 007 (rejected/replay audit logging, new) → T034, T054 |
| QCC-DATA | 001–008 | 001–006 → T016, T021; 007/008 (single-service-boundary rule) → validated by T060 (architecture/layering test), structurally enforced by T030, T031, T037–T039, T045, T046 (customer, admin, and API layers routing exclusively through `Api/*Interface`) |
| QCC-CONFIG | 001–004 | T020, T042 |
| QCC-NFR | 001–005 | T021 (NFR-002), T034 (NFR-003), T060 (NFR-004, architecture/layering test), T050 (NFR-005); NFR-001 resolved as "not applicable" (CLA-012) |

**Success Criteria note**: [spec.md](../spec.md) Success Criterion SC-004 (customers locate their current balance and most recent transaction within two navigation actions from account login) is a UI-navigation criterion, not independently unit/integration-testable. Consistent with the inspection-verified pattern already applied to QCC-API-015/016/020 above, SC-004 is inspection-verified via T053's execution of [quickstart.md](../plan/quickstart.md) Scenario 4.

**Result**: Every requirement ID across all 13 sub-specifications, at their current versions, has at least one implementation task, one validation task, or (for QCC-API-014/015/016/020, each an explicit "no additional mechanism/constraint" specification decision rather than buildable behavior) an inspectable, verified-by-text absence consistent with its own resolved acceptance criteria. No requirement is silently dropped or modified. This table must be regenerated again if any sub-specification's requirement range changes (e.g., a future `/speckit-clarify` or `/speckit-analyze` follow-up adds a new requirement ID) before being relied upon as a completion gate.

### A17. Final Validation (pre-completion self-check)

- [x] No requirement is missing or silently modified — verified in §A16; the one gap found (QCC-PROD-001–004) is closed by adding T055/T056, not by altering any requirement text.
- [x] No unnecessary duplicate Magento component is proposed — §A2 documents three rejected reuse candidates (Store Credit, Reward Points, Gift Card Account) and the rationale for building additively instead.
- [x] All cross-spec dependencies are covered — Foundational phase (T004–T022) is a shared prerequisite for all three user-story phases; no story silently depends on another story's output (confirmed in tasks.md "User Story Dependencies").
- [x] Security/compliance/NFR requirements are testable — §A8/A9 map each to an existing or newly added test task; QCC-NFR-001 (performance) is explicitly "not applicable" per its own resolved acceptance criterion, which is itself a testable (verifiable-by-inspection) state.
- [x] Existing architecture and the Constitution are respected — Constitution Check (PASS, no open Complexity Tracking items) and §A1/A2 confirm no core/vendor modification and consistent extension-point usage.
- [x] CI/CD validation is included — §A12 maps every Constitution Principle XI gate to already-available tooling in `vendor/`; absence of an automated pipeline definition is flagged as a dependency (§A14), not silently ignored.
- [x] No implementation code was produced anywhere in this plan or its referenced artifacts.

