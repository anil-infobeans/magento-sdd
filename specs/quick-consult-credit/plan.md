# Implementation Plan: Quick Consult Credit

**Branch**: `quick-consult-credit` | **Date**: 2026-09-15 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/quick-consult-credit/spec.md` and its 13 sub-specifications, informed by the Quick Consult Credit SRS v1.0 and Quick Consult Credit Technical Architecture (see [docs](../../docs)).

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Quick Consult Credit introduces a prepaid, customer-owned credit balance sold as a Magento product and consumed against consultation services. The primary requirement is a self-contained Magento module (`ICC_QuickConsultCredit`) that maintains one current-balance account per customer, an immutable append-only transaction ledger, atomic credit/debit operations behind a single service-contract boundary, a REST API for balance retrieval and transaction creation, a customer "Quick Consult Credit" dashboard, and an Admin customer-edit integration for manual Add/Remove Credit. The technical approach follows the Technical Architecture baseline: two database tables (materialized balance + ledger) managed via Magento declarative schema, `CreditTransactionManagement` as the single business entry point enforcing atomicity/row-locking/idempotency, an order-invoice observer for purchase posting, and `webapi.xml`-declared REST endpoints backed by the same service contracts consumed by the customer/admin UI.

## Technical Context

**Language/Version**: PHP 8.3 (project supports ~8.2/8.3/8.4 per `vendor/magento/module-customer` constraints)

**Primary Dependencies**: Magento / Adobe Commerce 2.4.8-p3 framework and core modules only (`magento/framework`, `magento/module-customer`, `magento/module-sales`, `magento/module-webapi`, `magento/module-backend`, `magento/module-authorization`); no new third-party libraries required

**Storage**: MySQL/MariaDB via Magento declarative schema (`db_schema.xml`); two new tables — `qcc_customer_credit` (materialized balance) and `qcc_credit_transaction` (append-only ledger)

**Testing**: PHPUnit 10.5 for unit and integration tests (`dev/tests/integration` conventions, module-local `Test/Unit` and `Test/Integration`); Magento API-functional test framework (`dev/tests/api-functional` conventions) for REST contract tests; Magento Functional Testing Framework (MFTF) for storefront dashboard and Admin adjustment scenarios

**Target Platform**: Existing Linux-hosted Magento 2.4.8 (Adobe Commerce) deployment

**Project Type**: Single Magento module (`app/code/ICC/QuickConsultCredit`) — not a frontend/backend split; storefront, Admin, and API surfaces are layers within the same module

**Performance Goals**: NEEDS CLARIFICATION — no quantitative target exists in the SRS or Technical Architecture (see [clarifications.md](./clarifications.md) CLA-012 and [testing-and-acceptance.md](./testing-and-acceptance.md) QCC-NFR-001). Resolved for planning purposes in [research.md](./research.md) with a design-time approach that does not invent an arbitrary SLA.

**Constraints**: Every balance-changing operation must be atomic (single DB transaction covering balance + ledger write); balance must never go negative; purchase posting must be idempotent per qualifying order item; concurrent redemptions must not double-spend; REST API replay behavior must not double-debit (exact idempotency-key mechanism is NEEDS CLARIFICATION — see [clarifications.md](./clarifications.md) CLA-004, resolved for planning purposes in [research.md](./research.md))

**Scale/Scope**: Standard Magento B2C/B2B customer-account scale (no explicit volume target in source documents); ledger table expected to grow unbounded per customer over the account lifetime, so pagination and composite indexing are required from day one

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| # | Principle | Gate status | Basis |
|---|---|---|---|
| I | Code Quality and Architecture | PASS | Module follows Magento module structure; all balance changes route through service contracts (`CreditBalanceManagementInterface`, `CreditTransactionManagementInterface`); purchase posting uses an observer (least-intrusive extension point) rather than core modification |
| II | Code Maintainability | PASS | Single service layer shared by Admin UI, customer UI, and REST API prevents duplicated business rules (see [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) QCC-DATA-007) |
| III | PHP and PSR Compliance | PASS | Standard Magento/PSR-4 module autoloading; no deprecated APIs anticipated for Magento 2.4.8 |
| IV | Testing Standards | PASS (planned) | Unit, integration, API, and functional/regression test layers planned per [testing-and-acceptance.md](./testing-and-acceptance.md); enforced at task level, not yet executed |
| V | User Experience Consistency | PASS | Customer dashboard reuses the standard My Account section pattern; Admin experience reuses the standard customer-edit tab pattern (no new UX paradigm introduced) |
| VI | Performance | CONDITIONAL PASS | No numeric SLA exists in source documents (CLA-012). Design avoids N+1 queries and full-ledger replay (materialized balance, indexed pagination), but formal performance sign-off is deferred pending a business-supplied target — tracked as a documented exception, not a design blocker |
| VII | Security and Data Handling | PASS | ACL-gated Admin actions, Magento token/session authentication for API and storefront, escaped output in UI templates, no sensitive data in logs (see [security-and-access-control.md](./security-and-access-control.md)) |
| VIII | Magento Core Compatibility | PASS | New module at `app/code/ICC/QuickConsultCredit`; no `vendor/` or core file modification |
| IX | Configuration and Deployment Safety | PASS | Declarative schema (`db_schema.xml`) for all persistence changes; `system.xml` for configuration; safe defaults defined in [configuration.md](./configuration.md) |
| X | Version Control and Feature Branches | PASS | Work performed on the `quick-consult-credit` feature branch |
| XI | Quality Gates | CONDITIONAL PASS | Two documented, tracked exceptions remain open (CLA-004 API idempotency-key contract, CLA-012 performance target) — see Complexity Tracking below; all other gates pass |
| XII | Principle of Least Change | PASS | Entirely new, isolated module; no refactoring of existing Magento modules |

**Overall**: PASS with two documented, non-blocking exceptions (CLA-004, CLA-012) carried forward from [clarifications.md](./clarifications.md). Both are resolved for planning/design purposes in [research.md](./research.md) with recommended defaults, pending final business/architecture sign-off before production release.

## Post-Design Constitution Re-check

*Performed after Phase 1 design ([data-model.md](./data-model.md), [contracts/](./contracts/), [quickstart.md](./quickstart.md)).*

No new violations were introduced during design. The service-contract boundary (single `CreditTransactionManagement` entry point), declarative schema, ACL matrix, and REST contract all reinforce rather than weaken the Constitution Check gates above. The two documented exceptions (CLA-004, CLA-012) remain unchanged in scope and are still non-blocking for design; they are carried into [tasks.md](./tasks.md) (once generated) as tracked follow-up items rather than being silently resolved. **Re-check result: PASS (unchanged).**

## Project Structure

### Documentation (this feature)

```text
specs/quick-consult-credit/
├── spec.md                          # Master feature specification
├── plan.md                          # This file (/speckit-plan command output)
├── research.md                      # Phase 0 output (/speckit-plan command)
├── data-model.md                    # Phase 1 output (/speckit-plan command)
├── quickstart.md                    # Phase 1 output (/speckit-plan command)
├── contracts/                       # Phase 1 output (/speckit-plan command)
│   ├── rest-api.md
│   └── service-contracts.md
├── clarifications.md                # Ambiguity/decision register
├── checklists/
│   └── requirements.md
├── product-configuration.md         # Sub-specification (QCC-PROD)
├── customer-credit-account.md       # Sub-specification (QCC-ACCOUNT)
├── credit-ledger.md                 # Sub-specification (QCC-LEDGER)
├── credit-purchase-posting.md       # Sub-specification (QCC-PURCHASE)
├── credit-redemption.md             # Sub-specification (QCC-REDEEM)
├── credit-rest-api.md               # Sub-specification (QCC-API)
├── customer-dashboard.md            # Sub-specification (QCC-CUSTOMER)
├── admin-credit-management.md       # Sub-specification (QCC-ADMIN)
├── security-and-access-control.md   # Sub-specification (QCC-SEC)
├── audit-and-observability.md       # Sub-specification (QCC-AUDIT)
├── data-integrity-and-concurrency.md # Sub-specification (QCC-DATA)
├── configuration.md                 # Sub-specification (QCC-CONFIG)
├── testing-and-acceptance.md        # Sub-specification (QCC-NFR / test strategy)
└── tasks.md                         # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
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

**Structure Decision**: Single self-contained Magento module at `app/code/ICC/QuickConsultCredit`, matching the Technical Architecture baseline (§5 Module Structure). No frontend/backend project split is used — storefront (`Controller/Account`, `Block/Account`), Admin (`Controller/Adminhtml`, `Block/Adminhtml`), and API (`etc/webapi.xml` + `Api/`) are layers within one module, all routed through the `Model/Service/*` service-contract implementations so no business logic is duplicated across layers (Constitution Principle II; [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) QCC-DATA-007/008).

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| No numeric performance SLA defined (Principle VI, XI) | Neither the SRS nor the Technical Architecture define a target, and inventing one would violate the constitution's prohibition on premature optimization/arbitrary SLAs (Principle VI) and the spec's explicit rule against inventing SLAs ([testing-and-acceptance.md](./testing-and-acceptance.md) QCC-NFR-001) | Asserting an arbitrary number (e.g., "200ms p95") without business/architecture sign-off would create a false quality gate that could pass or fail incorrectly; the correct alternative is to design for efficiency (materialized balance, indexed pagination, no N+1 queries) and defer formal SLA validation to [clarifications.md](./clarifications.md) CLA-012 |
| API replay/idempotency-key contract not finalized (Principle XI) | Neither source document defines a client-supplied idempotency key/header format for the create-transaction endpoint ([clarifications.md](./clarifications.md) CLA-004) | Inventing a mandatory field name/format now risks a breaking contract change once the API/integration architecture owner decides the real contract; [research.md](./research.md) instead documents a recommended technical pattern that satisfies the required behavior (no duplicate debits) without asserting a final field name |

