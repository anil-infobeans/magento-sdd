# Implementation Plan: Quick Consult Credit

**Branch**: `001-quick-consult-credit` | **Date**: 2026-09-12 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-quick-consult-credit/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Quick Consult Credit is a Magento-native prepaid credit capability: customers purchase fixed-denomination credit through standard checkout, the credit is posted to a per-customer account exactly once after a qualifying order/invoice condition, and the credit is later redeemed via an authorized external REST API call from a consultation system. The technical approach — derived from the approved Technical Architecture and the resolved specification set — is a single new Magento 2 module (`ICC_QuickConsultCredit`) built entirely on Magento service contracts, with a two-table persistence model (materialized current balance + immutable append-only ledger), database-transaction-protected atomic state changes with row-level locking as the concurrency control, database-level uniqueness constraints for purchase and API idempotency, Magento Web API (`webapi.xml`) REST endpoints secured by dedicated ACL resources, a customer "My Account" dashboard section, and an Admin customer-edit tab for manual adjustments.

## Technical Context

**Language/Version**: PHP 8.1–8.3 (Magento 2.4.8-p3 supported range; installed CLI is PHP 8.3.26)

**Primary Dependencies**: Magento 2.4.8 core framework only — `magento/framework` (DI, service contracts, declarative schema), `magento/module-customer` (customer entity, extension attributes, My Account integration), `magento/module-sales` (order/invoice lifecycle observer trigger), `magento/module-webapi` (`webapi.xml` REST exposure, ACL-gated authentication), `magento/module-catalog` (product/attribute-set integration). No third-party libraries are required.

**Storage**: MySQL/MariaDB via Magento declarative schema (`db_schema.xml`) — two new tables: `qcc_customer_credit` (materialized balance) and `qcc_credit_transaction` (immutable ledger), per Technical Architecture §7.

**Testing**: PHPUnit for unit tests (`Test/Unit`) and Magento integration tests (`dev/tests/integration/testsuite/ICC/QuickConsultCredit`); Magento API-functional tests (`dev/tests/api-functional/testsuite/ICC/QuickConsultCredit`, extending `Magento\TestFramework\TestCase\WebapiAbstract`) for REST contract/authorization/idempotency coverage; MFTF (`Test/Mftf`) for storefront dashboard and Admin customer-tab functional/regression coverage; dedicated concurrency test(s) using parallel PHPUnit/integration-test processes or a database-level race simulation to validate QCC-CONC-007.

**Target Platform**: Linux server, existing Magento 2.4.8 (Adobe Commerce) deployment at this workspace root

**Project Type**: Magento 2 module (single monolithic application — storefront, Admin, and REST API surfaces are all delivered within one `app/code` module; no separate frontend/backend split applies)

**Performance Goals**: Balance retrieval and transaction-history retrieval ≤ 500ms p95 under nominal load; redemption transaction and Admin balance/history view ≤ 1s p95 under nominal load (per approved QCC-PERF-001–004)

**Constraints**: Two-decimal monetary precision with exact decimal comparison (QCC-CURR-002/003); every state-changing operation (balance + cumulative statistic + ledger + idempotency key) committed as one atomic DB transaction with account-row locking (QCC-CONC-002/005); purchase posting and external API writes protected by database-level uniqueness constraints, not application-level check-then-insert (QCC-IDEMP-001/005); ledger rows are never mutated or deleted, only appended (QCC-LEDGER-007/008); no direct modification of Magento core/vendor code (Constitution Principle VIII)

**Scale/Scope**: Single Magento instance; one credit account per customer per currency; 4 initial configurable denominations ($25/$50/$100/$250); default transaction-history page size 20 (QCC-PERF-006); ledger table expected to grow unbounded over the store's lifetime and must remain paginated/indexed for acceptable query performance at scale

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Evaluated against `.specify/memory/constitution.md` v1.0.0 (Magento 2.4.8 Constitution, 12 Core Principles):

| # | Principle | Gate Status | Notes |
|---|---|---|---|
| I | Code Quality and Architecture | ✅ PASS | Module follows standard Magento structure (registration.php, etc/, Api/, Model/, Observer/, Controller/, Block/, view/); business logic lives exclusively behind service contracts (QCC-CROSS-001); DI used throughout; purchase posting uses an observer (least-intrusive extension mechanism) rather than core modification. |
| II | Code Maintainability | ✅ PASS | Responsibilities separated per Architecture §21 Class Responsibility Matrix (Balance mgmt, Transaction mgmt, Ledger, Purchase processor, Validator each isolated); no speculative abstraction beyond what the two-table + service-contract design requires. |
| III | PHP and PSR Compliance | ✅ PASS | Standard Composer/PSR-4 autoloading under `ICC\QuickConsultCredit`; no deprecated APIs anticipated for Magento 2.4.8. |
| IV | Testing Standards | ✅ PASS (design intent) | Unit, integration, API-functional, MFTF, and concurrency test layers all explicitly planned (see Testing above and QCC-TEST-001–008); enforced again at `/speckit-tasks`/implementation time. |
| V | User Experience Consistency | ✅ PASS | Customer dashboard and Admin tab reuse existing Magento My Account navigation and Admin customer-edit tab patterns (QCC-CUSTOMER-001, QCC-ADMIN-001) rather than introducing new UI paradigms. |
| VI | Performance | ✅ PASS | Materialized balance avoids ledger replay on every read; pagination mandatory for history (QCC-CUSTOMER-005, QCC-LEDGER-012); approved numeric targets exist (QCC-PERF-001–004) to validate against. |
| VII | Security and Data Handling | ✅ PASS | Authentication via Magento-native token mechanisms only (QCC-SEC-001); output/logging secret-exclusion required (QCC-SEC-006, QCC-AUDIT-004); SQL access via Magento resource models/declarative schema (no raw SQL injection surface). |
| VIII | Magento Core Compatibility | ✅ PASS | Entirely new module under `app/code/ICC/QuickConsultCredit`; no vendor/core file modification; extension via observer + service contracts + webapi.xml only. |
| IX | Configuration and Deployment Safety | ✅ PASS | New tables via declarative `db_schema.xml` (no manual SQL, per QCC-CROSS-003); denomination configuration exposed via Magento System Configuration (see research.md Decision 1) with safe defaults. |
| X | Version Control and Feature Branches | ✅ PASS | Work proceeds on `001-quick-consult-credit` feature branch per repository convention. |
| XI | Quality Gates | ⚠ DEFERRED TO IMPLEMENTATION | Static analysis, coding-standard, and full test-pass gates are enforced at implementation/PR time, not at planning time; no violation anticipated. |
| XII | Principle of Least Change | ✅ PASS | No existing Magento capability provides prepaid credit ledger semantics; a new, isolated module is the minimal-blast-radius approach — no existing code is refactored. |

**Initial gate result: PASS.** No violations require justification in Complexity Tracking.

**Post-Design Re-check (after Phase 1 — data-model.md, contracts/, quickstart.md completed):** All 12 principles re-evaluated against the finalized data model and contracts; no new violations introduced. Notable confirmations:
- Principle VII (Security): [contracts/rest-api.md](./contracts/rest-api.md) explicitly encodes authorization-before-existence-check ordering (401/403 before 404) for both endpoints, matching QCC-SEC-008.
- Principle VI (Performance): [data-model.md](./data-model.md) confirms the materialized-balance design (no ledger replay on read) and required indexes `(customer_id, created_at, entity_id)` and unique `(idempotency_key)`/`(reference_type, reference_id)` support the approved p95 targets without additional schema changes.
- Principle IX (Config/Deployment Safety): denomination configuration remains a System Configuration value only (no new table), per [research.md](./research.md) Decision 1, confirmed unchanged in [data-model.md](./data-model.md).
- Principle XII (Least Change): [contracts/service-contracts.md](./contracts/service-contracts.md) introduces only the services already anticipated in the Architecture (no additional interfaces).

**Post-design gate result: PASS.** No Complexity Tracking entries required.

## Project Structure

### Documentation (this feature)

```text
specs/001-quick-consult-credit/
├── spec.md                      # Feature specification (overview + index)
├── product.md                   # QCC-PROD-* specification
├── account.md                   # QCC-ACCOUNT-*/QCC-CURR-* specification
├── ledger.md                    # QCC-LEDGER-* specification
├── purchase.md                  # QCC-PURCHASE-* specification
├── redemption.md                # QCC-REDEEM-* specification
├── api.md                       # QCC-API-* specification
├── customer-dashboard.md        # QCC-CUSTOMER-* specification
├── admin.md                     # QCC-ADMIN-* specification
├── security.md                  # QCC-SEC-* specification
├── idempotency-concurrency.md   # QCC-IDEMP-*/QCC-CONC-* specification
├── audit-observability.md       # QCC-AUDIT-* specification
├── non-functional.md            # QCC-PERF-*/QCC-COMPLY-*/QCC-TEST-*/QCC-SCOPE-*
├── traceability.md              # Requirement traceability matrix
├── ambiguity-register.md        # Resolved/open ambiguity register
├── change-summary.md            # Specification change summary & validation report
├── checklists/requirements.md   # Spec quality checklist
├── plan.md                      # This file (/speckit-plan command output)
├── research.md                  # Phase 0 output (/speckit-plan command)
├── data-model.md                # Phase 1 output (/speckit-plan command)
├── quickstart.md                # Phase 1 output (/speckit-plan command)
├── contracts/                   # Phase 1 output (/speckit-plan command)
│   ├── rest-api.md              # REST endpoint contract (balance, transaction)
│   └── service-contracts.md     # Magento service-contract interface signatures
└── tasks.md                     # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

This is a Magento 2 module delivered as a single `app/code` package (Magento's standard monolithic-module structure — there is no separate frontend/backend split; storefront, Admin, and REST API surfaces are all part of the same module).

```text
app/code/ICC/QuickConsultCredit/
├── registration.php
├── composer.json                       # optional, for future package extraction
├── etc/
│   ├── module.xml
│   ├── di.xml
│   ├── acl.xml                         # ICC_QuickConsultCredit::credit / ::manage ACL resources
│   ├── db_schema.xml                   # qcc_customer_credit, qcc_credit_transaction
│   ├── webapi.xml                      # GET balance, POST transaction endpoints
│   ├── events.xml                      # order/invoice observer registration
│   ├── system.xml                      # denomination + posting-condition + idempotency config
│   └── adminhtml/
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
│           ├── AmountValidator.php
│           ├── CustomerValidator.php
│           └── CurrencyValidator.php
├── Observer/
│   └── OrderCreditPost.php             # qualifying-condition check + purchase posting trigger
├── Controller/
│   ├── Account/
│   │   ├── Index.php                   # customer dashboard balance view
│   │   └── Transactions.php            # customer dashboard history (paginated)
│   └── Adminhtml/Customer/Credit/
│       ├── AddCredit.php
│       └── RemoveCredit.php
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
├── Test/
│   ├── Unit/                           # validators, transaction-type rules, idempotency decisions
│   ├── Integration/                    # DB transaction/locking/atomicity behavior
│   ├── Api/                            # REST auth/authz/validation/idempotency
│   └── Mftf/                           # storefront + admin functional/regression flows
└── README.md

dev/tests/integration/testsuite/ICC/QuickConsultCredit/   # Magento integration test harness location
dev/tests/api-functional/testsuite/ICC/QuickConsultCredit/ # Magento API-functional test harness location
```

**Structure Decision**: Single Magento 2 module (`ICC_QuickConsultCredit`) at `app/code/ICC/QuickConsultCredit`, exactly matching the approved Technical Architecture §5 module structure. No separate frontend/backend/mobile split applies — Magento's module system already separates storefront (`view/frontend`), Admin (`view/adminhtml`), and API (`etc/webapi.xml`) concerns within one package. This directory does not yet exist in the repository (`app/code` is currently empty) and will be created during implementation (`/speckit-tasks` → `/speckit-implement`), not during this planning phase.

## Complexity Tracking

> No Constitution Check violations were identified; this section is intentionally empty.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| _None_ | — | — |
