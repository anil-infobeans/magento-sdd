---

description: "Task list template for feature implementation"
---

# Tasks: Quick Consult Credit

**Input**: Design documents from `/specs/quick-consult-credit/`
**Prerequisites**: [plan.md](../plan/plan.md), [spec.md](../spec.md), [research.md](../plan/research.md), [data-model.md](../plan/data-model.md), [contracts/rest-api.md](../contracts/rest-api.md), [contracts/service-contracts.md](../contracts/service-contracts.md), [quickstart.md](../plan/quickstart.md), 13 sub-specifications, [clarifications.md](../clarifications.md)

**Tests**: Test tasks are INCLUDED. [testing-and-acceptance.md](../non-functional/testing-and-acceptance.md) explicitly mandates Unit/Integration/API/Functional/Regression/Security test-layer coverage, and Constitution Principle IV (Testing Standards) requires this at task level.

**Organization**: Tasks are grouped by user story (from [spec.md](../spec.md)) to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Exact file paths are included in each description; all paths are relative to the repository root

## Path Conventions

Single Magento module project: `app/code/ICC/QuickConsultCredit/` (per [plan.md](../plan/plan.md) Project Structure). Tests live under the module's own `Test/Unit`, `Test/Integration`, `Test/Api`, and `Test/Mftf` directories, discovered by the corresponding suites in `dev/tests/unit`, `dev/tests/integration`, `dev/tests/api-functional`, and MFTF.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Module initialization and registration

- [X] T001 Create module registration and manifest: `app/code/ICC/QuickConsultCredit/registration.php` and `app/code/ICC/QuickConsultCredit/etc/module.xml` declaring module `ICC_QuickConsultCredit` with dependencies on `Magento_Customer`, `Magento_Sales`, `Magento_Webapi`, `Magento_Backend`, `Magento_Authorization` only (no third-party libraries, per [plan.md](../plan/plan.md) Primary Dependencies)
- [X] T002 [P] Create `app/code/ICC/QuickConsultCredit/composer.json` (optional package metadata for future extraction, per [plan.md](../plan/plan.md) Project Structure)
- [X] T003 [P] Create `app/code/ICC/QuickConsultCredit/README.md` summarizing the module's purpose and linking to [spec.md](../spec.md) and [configuration.md](../non-functional/configuration.md)

**Checkpoint**: Module skeleton registers cleanly with `bin/magento module:status`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Persistence schema, service-contract boundary, and cross-cutting infrastructure that every user story depends on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T004 Define the `qcc_customer_credit` table in `app/code/ICC/QuickConsultCredit/etc/db_schema.xml`: `entity_id` (identity PK), `customer_id` (integer, **unique** — exactly one account per customer per QCC-ACCOUNT-001), `balance` (integer, not nullable, default 0, must never go negative per QCC-ACCOUNT-003/QCC-DATA-001), `total_credited` (integer, not nullable, default 0, QCC-ACCOUNT-006), `total_debited` (integer, not nullable, default 0, QCC-ACCOUNT-007), `created_at`, `updated_at` — per [data-model.md](../plan/data-model.md) Customer Credit Account
- [X] T005 Extend `app/code/ICC/QuickConsultCredit/etc/db_schema.xml` with the `qcc_credit_transaction` table: `entity_id` (identity PK), `customer_id` (integer, not nullable), `transaction_type` (enumerated string, not nullable, one of `PURCHASE`/`REDEEM`/`ADMIN_ADD`/`ADMIN_REMOVE` per QCC-LEDGER-002), `direction` (enumerated string, not nullable, `CREDIT`/`DEBIT` per QCC-LEDGER-003), `amount` (integer, positive, not nullable), `balance_before` (integer, not nullable), `balance_after` (integer, not nullable, must equal `balance_before ± amount` per direction), `message` (text, nullable, **required** when `transaction_type` is `ADMIN_ADD`/`ADMIN_REMOVE` per QCC-LEDGER-004), `source` (enumerated string, not nullable, `CUSTOMER`/`API`/`ADMIN`/`SYSTEM` per QCC-LEDGER-005), `created_by` (string, nullable), `source_reference` (string, nullable, **unique when present**, required for `PURCHASE` rows per QCC-PURCHASE-011), `created_at` (server-assigned, not nullable per QCC-LEDGER-006); add a composite index on `(customer_id, created_at, entity_id)` for paginated ordered retrieval (QCC-LEDGER-009) — per [data-model.md](../plan/data-model.md) Credit Transaction
- [X] T006 [P] Define `app/code/ICC/QuickConsultCredit/Api/Data/CreditBalanceInterface.php` (customer identity + current integer balance) per [data-model.md](../plan/data-model.md) and [contracts/service-contracts.md](../contracts/service-contracts.md) Credit Balance data contract
- [X] T007 [P] Define `app/code/ICC/QuickConsultCredit/Api/Data/CreditTransactionInterface.php` with getters for every field listed in [data-model.md](../plan/data-model.md) Credit Transaction (transaction_type, direction, amount, balance_before, balance_after, message, source, created_by, source_reference, created_at)
- [X] T008 [P] Define `app/code/ICC/QuickConsultCredit/Api/Data/CreditTransactionSearchResultsInterface.php` extending `Magento\Framework\Api\SearchResultsInterface` for paginated ledger queries (QCC-LEDGER-009)
- [X] T009 [P] Define `app/code/ICC/QuickConsultCredit/Api/CreditBalanceManagementInterface.php`: get balance (create lazily with balance 0 if absent, QCC-ACCOUNT-004) — per [contracts/service-contracts.md](../contracts/service-contracts.md) Balance Read/Create interface
- [X] T010 [P] Define `app/code/ICC/QuickConsultCredit/Api/CreditTransactionManagementInterface.php`: credit, debit/redeem, admin-add, admin-remove operations, each requiring amount > 0 and returning a deterministic error on failure (`INVALID_AMOUNT`, `INSUFFICIENT_BALANCE`, `CUSTOMER_NOT_FOUND`, authorization error) — per [contracts/service-contracts.md](../contracts/service-contracts.md) Transaction Management interface (the sole entry point per QCC-DATA-007/008)
- [X] T011 [P] Define `app/code/ICC/QuickConsultCredit/Api/CreditLedgerInterface.php`: paginated `getList` by customer identity + search criteria, and `getById`, never returning another customer's entries (QCC-SEC-004) — per [contracts/service-contracts.md](../contracts/service-contracts.md) Ledger Read interface
- [X] T012 [P] Implement `app/code/ICC/QuickConsultCredit/Model/CreditBalance.php`, `Model/ResourceModel/CreditBalance.php`, and `Model/ResourceModel/CreditBalance/Collection.php` mapped to `qcc_customer_credit`
- [X] T013 [P] Implement `app/code/ICC/QuickConsultCredit/Model/CreditTransaction.php`, `Model/ResourceModel/CreditTransaction.php`, and `Model/ResourceModel/CreditTransaction/Collection.php` mapped to `qcc_credit_transaction`, with no update/delete save path exposed (immutable/append-only per QCC-LEDGER-001/008)
- [X] T014 [P] Implement `app/code/ICC/QuickConsultCredit/Model/Service/Validator/TransactionValidator.php` enforcing: amount must be a positive integer (rejects zero per QCC-REDEEM-004 and negative per QCC-REDEEM-005/QCC-API-006), `message` non-empty for `ADMIN_ADD`/`ADMIN_REMOVE` (QCC-LEDGER-004/QCC-ADMIN-005), debit amount ≤ current balance (QCC-DATA-001/QCC-REDEEM-006)
- [X] T015 Implement `app/code/ICC/QuickConsultCredit/Model/Service/CreditBalanceManagement.php` implementing `CreditBalanceManagementInterface`: lazily creates the account with `balance = 0` on first access if absent (QCC-ACCOUNT-004), read-only with no side effects (QCC-API-004) — depends on T006, T009, T012
- [X] T016 Implement `app/code/ICC/QuickConsultCredit/Model/Service/CreditTransactionManagement.php` implementing `CreditTransactionManagementInterface` as the single atomic entry point: begins a DB transaction, performs a locking read of the customer's balance row, invokes `TransactionValidator`, writes exactly one ledger row (QCC-DATA-002), updates the materialized balance, commits, and fully rolls back both balance and ledger on any failure (QCC-DATA-001/003/006) — per [research.md](../plan/research.md) §3; depends on T007, T010, T013, T014
- [X] T017 Implement `app/code/ICC/QuickConsultCredit/Model/Service/CreditLedger.php` implementing `CreditLedgerInterface`: paginated, ordered per-customer transaction listing scoped strictly to the requesting identity, never disclosing another customer's rows (QCC-LEDGER-009, QCC-SEC-004) — depends on T008, T011, T013
- [X] T018 Wire preferences for all four `Api/*ManagementInterface`/`Api/*Interface` contracts to their `Model/Service/*` implementations in `app/code/ICC/QuickConsultCredit/etc/di.xml` — depends on T015, T016, T017
- [X] T019 [P] Define the ACL resource tree in `app/code/ICC/QuickConsultCredit/etc/acl.xml`: parent resource `ICC_QuickConsultCredit::credit` with child resource `ICC_QuickConsultCredit::manage` for least-privilege administrative/integration access (QCC-SEC-002/003) — per [research.md](../plan/research.md) §10
- [X] T020 [P] Define configuration fields in `app/code/ICC/QuickConsultCredit/etc/adminhtml/system.xml`: module enable/disable (QCC-CONFIG-001), credit product/attribute-set reference (QCC-CONFIG-002), qualifying order/payment condition defaulting to "invoice generated / payment captured" (QCC-CONFIG-003, resolved default per [clarifications.md](../clarifications.md) CLA-003), customer transaction history page size defaulting to 20 (QCC-CONFIG-004)
- [X] T021 [P] Integration test for atomicity and row-locking in `app/code/ICC/QuickConsultCredit/Test/Integration/Model/Service/CreditTransactionManagementTest.php`: verify balance never goes negative, exactly one ledger row per successful operation, and full rollback (no partial state) on a simulated failure (QCC-DATA-001/002/003/006, QCC-NFR-002) — depends on T016
- [X] T022 [P] Unit tests for `TransactionValidator` in `app/code/ICC/QuickConsultCredit/Test/Unit/Model/Service/Validator/TransactionValidatorTest.php` covering zero amount, negative amount, missing admin reason, and debit exceeding balance (QCC-REDEEM-004/005, QCC-LEDGER-004, QCC-DATA-001) — depends on T014

**Checkpoint**: Foundation ready — user story implementation can now begin

---

## Phase 3: User Story 1 - Customer purchases and later redeems consultation credit (Priority: P1) 🎯 MVP

**Goal**: A qualifying purchase posts credit to the customer's account exactly once; an authorized external system or admin redeems part of that balance via the REST API, with the balance and ledger updated atomically.

**Independent Test**: Complete a qualifying order for the Quick Consult Credit product and confirm the balance/ledger reflect the purchase (quickstart Scenario 1); then submit a valid `POST /V1/quick-consult-credit/transactions` redemption request and confirm the balance/ledger reflect the debit (quickstart Scenario 2).

### Tests for User Story 1

- [X] T023 [P] [US1] API contract test for `GET /V1/quick-consult-credit/balance/:customerId` in `app/code/ICC/QuickConsultCredit/Test/Api/BalanceGetTest.php` covering: authenticated success, 401 unauthenticated, 403 cross-customer, 404 unknown customer, 200 zero-balance for a customer with no account (QCC-API-001/002/003/004/009), and a malformed/zero/negative/out-of-range `customerId` path parameter rejected with HTTP 401 and `error_code = CUSTOMER_NOT_FOUND`, distinct from the 404 well-formed-but-nonexistent case (QCC-API-018)
- [X] T024 [P] [US1] API contract test for `POST /V1/quick-consult-credit/transactions` in `app/code/ICC/QuickConsultCredit/Test/Api/TransactionCreateTest.php` covering: authorized success response shape (`transaction_id`, `type`, `amount`, `previous_balance`, `current_balance`) for `transaction_type = REDEEM`, `INVALID_REQUEST` rejection for a missing required field or a non-integer/fractional `amount` (QCC-API-019, CLA-020 resolved), `INVALID_TRANSACTION_TYPE` rejection for `PURCHASE`/`ADMIN_ADD`/`ADMIN_REMOVE` (QCC-API-017, CLA-016 resolved), `INVALID_AMOUNT` (structurally valid integer, zero/negative), `INSUFFICIENT_BALANCE`, `CUSTOMER_NOT_FOUND`, `UNAUTHORIZED` for a customer-session caller, an unauthorized caller (customer-session or unaffiliated caller) submitting a simultaneously malformed/value-invalid payload (missing required field, non-integer `amount`, or invalid `transaction_type`) asserting the response is `UNAUTHORIZED` and never `INVALID_REQUEST`/`INVALID_TRANSACTION_TYPE`, confirming the fixed authentication → authorization → `INVALID_REQUEST` → `INVALID_AMOUNT`/`INVALID_TRANSACTION_TYPE` → business-rule precedence order (QCC-API-012 AC-4, QCC-SEC-007 AC-2), and a resubmitted (replayed) request being validated independently — confirming it MAY produce an additional accepted transaction if it individually passes validation, since no dedicated idempotency mechanism exists (QCC-API-005/006/007/008/010/011/012/013/019, CLA-004, CLA-020 resolved)
- [X] T025 [P] [US1] Integration test for purchase-posting idempotency in `app/code/ICC/QuickConsultCredit/Test/Integration/Model/Service/CreditPurchaseProcessorTest.php`: re-processing the same qualifying order item must not create a second `PURCHASE` ledger row or double-credit the balance (QCC-PURCHASE-001/009/010/011, QCC-DATA-005)
- [X] T026 [P] [US1] MFTF functional test for the full purchase-to-balance flow (add credit product to cart with quantity, checkout, generate invoice, verify balance via API) in `app/code/ICC/QuickConsultCredit/Test/Mftf/Test/PurchasePostsCreditTest.xml` (QCC-PURCHASE-001/002, quickstart Scenario 1)

### Implementation for User Story 1

- [X] T027 [US1] Implement `app/code/ICC/QuickConsultCredit/Model/Service/CreditPurchaseProcessor.php`: derive the credit amount from the qualifying order item's purchased quantity (QCC-PURCHASE-002), use the order item identifier as the deterministic `source_reference` (QCC-PURCHASE-011), invoke `CreditTransactionManagementInterface` credit operation with `source = SYSTEM`, and no-op (idempotent skip) if that reference was already posted (QCC-PURCHASE-001/009/010) — depends on T016
- [X] T028 [US1] Implement `app/code/ICC/QuickConsultCredit/Observer/OrderCreditPost.php`: observes the invoice-save event, resolves qualifying Quick Consult Credit order items from the invoice per the configured qualifying condition, and calls `CreditPurchaseProcessor::postPurchase()` for each; must NOT post on cart addition, quote creation, pending order, failed payment, or cancelled/non-qualifying order (QCC-PROD-005/006/007/008, QCC-PURCHASE-003/004/005/006/007/008) — depends on T020, T027
- [X] T029 [US1] Register the observer on the `sales_order_invoice_save_after` event in `app/code/ICC/QuickConsultCredit/etc/events.xml` — depends on T028
- [X] T030 [US1] Define the REST route `GET /V1/quick-consult-credit/balance/:customerId` in `app/code/ICC/QuickConsultCredit/etc/webapi.xml`, mapped to `CreditBalanceManagementInterface`, permitting customer self-access (own ID only, authenticated session is authoritative regardless of the path parameter per QCC-API-009) or admin/integration ACL resource `ICC_QuickConsultCredit::credit`; reject a malformed/zero/negative/out-of-range `customerId` path parameter with HTTP 401 and `error_code = CUSTOMER_NOT_FOUND` before resolving a well-formed but nonexistent customer to the 404 case (QCC-API-018) — depends on T009, T015, T019
- [X] T031 [US1] Extend `app/code/ICC/QuickConsultCredit/etc/webapi.xml` with the REST route `POST /V1/quick-consult-credit/transactions`, mapped to `CreditTransactionManagementInterface`'s redeem/debit operation only, restricted to integration/admin ACL resource `ICC_QuickConsultCredit::manage` only — **no customer self-access** (CLA-007 resolved, QCC-API-005), and accepting `transaction_type = REDEEM` only, rejecting `PURCHASE`/`ADMIN_ADD`/`ADMIN_REMOVE` with `INVALID_TRANSACTION_TYPE` before any balance-changing logic executes (QCC-API-017, CLA-016 resolved) — depends on T010, T016, T019, T030
- [X] T032 [US1] Implement the standardized error-response shape `{ "success": false, "error_code": "...", "message": "..." }` for `INVALID_REQUEST`, `INVALID_AMOUNT`, `INVALID_TRANSACTION_TYPE`, `INSUFFICIENT_BALANCE`, `CUSTOMER_NOT_FOUND`, and `UNAUTHORIZED` across both endpoints, evaluating `INVALID_REQUEST` (missing field or non-integer `amount`) before any other validation, with no internal exception details or credentials disclosed (QCC-API-012, QCC-API-017, QCC-API-019, QCC-SEC-005/006); for every authorization decision on both endpoints, emit a structured log entry with `authorization_category` (`AUTHENTICATION`/`CUSTOMER_OWNERSHIP`/`INTEGRATION_ACL`/`ADMIN_ACL`) and `decision` (`GRANTED`/`DENIED`), excluding credentials/tokens (QCC-API-011) — depends on T031
- [X] T033 [US1] Document and verify the resolved no-idempotency behavior for the create-transaction endpoint: ensure no request-identity/deduplication field or persistence-layer uniqueness check is introduced on this path (distinct from the purchase-posting `source_reference` uniqueness in T005/T027, which remains required), and that a resubmitted request is validated independently against the current balance at that time, per [credit-rest-api.md](../functional/credit-rest-api.md) QCC-API-013 and [clarifications.md](../clarifications.md) CLA-004 (resolved 2026-09-16: no dedicated mechanism) — depends on T031
- [X] T034 [US1] Add structured failure logging (customer_id, transaction_type, amount, reference, exception summary — no credentials) via `Psr\Log\LoggerInterface` in `CreditPurchaseProcessor` and `CreditTransactionManagement` (QCC-AUDIT-005, QCC-NFR-003); additionally emit a distinct rejected-request audit log entry for every business-validation/authorization rejection and a distinct resubmission-accepted audit log entry for every independently-validated replayed request that is accepted, both separate from the operational-failure log entries above and tagged with `authorization_category`/`decision` where applicable (QCC-AUDIT-007) — depends on T016, T027
- [X] T055 [US1] Deployment/catalog configuration task (no code): create the Quick Consult Credit product in Admin Catalog and assign it to the Consultation Services attribute set, and verify quantity-based purchasing works as expected (QCC-PROD-001/002/003) — gap identified during architecture review (see [plan.md](../plan/plan.md) Architect Review Addendum §A15/A16); validated via [quickstart.md](../plan/quickstart.md) Prerequisites checklist — depends on T020 — completed manually via Admin Catalog by the user
- [X] T056 [US1] Implement guest-checkout prevention for the Quick Consult Credit product via a plugin/observer on cart/checkout validation (least-intrusive extension mechanism per Constitution Principle I), rejecting or forcing authentication before checkout completes when the cart contains this product (QCC-PROD-004) — gap identified during architecture review (see [plan.md](../plan/plan.md) Architect Review Addendum §A15/A16) — depends on T020, T027

**Checkpoint**: User Story 1 (MVP) is fully functional and independently testable — purchase posts credit, redemption debits credit, both via the governed service boundary and REST API

---

## Phase 4: User Story 2 - Customer reviews balance and history (Priority: P2)

**Goal**: A logged-in customer sees their current balance and paginated transaction history in My Account, isolated from all other customers' data.

**Independent Test**: Log in as a customer, view the Quick Consult Credit dashboard, and confirm balance/history display correctly; attempt to retrieve another customer's balance/history and confirm it is denied (quickstart Scenario 4).

### Tests for User Story 2

- [X] T035 [P] [US2] MFTF functional test for the customer dashboard in `app/code/ICC/QuickConsultCredit/Test/Mftf/Test/CustomerDashboardTest.xml`: verify current balance and paginated, correctly ordered transaction history display, and that cross-customer access is denied (QCC-CUSTOMER-002/003/004/006, quickstart Scenario 4)
- [X] T036 [P] [US2] Integration test in `app/code/ICC/QuickConsultCredit/Test/Integration/Model/Service/CreditLedgerTest.php` verifying `CreditLedger::getList` never returns another customer's ledger entries (QCC-SEC-004)

### Implementation for User Story 2

- [X] T037 [P] [US2] Implement `app/code/ICC/QuickConsultCredit/Controller/Account/Index.php`: My Account "Quick Consult Credit" landing controller, resolving customer identity solely from the authenticated customer session (never a request parameter) (QCC-CUSTOMER-001/005)
- [X] T038 [P] [US2] Implement `app/code/ICC/QuickConsultCredit/Controller/Account/Transactions.php`: paginated transaction history controller, session-scoped identity only (QCC-CUSTOMER-004/005)
- [X] T039 [US2] Implement `app/code/ICC/QuickConsultCredit/Block/Account/Credit.php` reading balance via `CreditBalanceManagementInterface` and history via `CreditLedgerInterface`, scoped strictly to the session customer, denying any attempt to reference another customer's ID (QCC-CUSTOMER-005/006) — depends on T009, T011, T037, T038
- [X] T040 [P] [US2] Add frontend layout XML for the My Account navigation entry and Quick Consult Credit section in `app/code/ICC/QuickConsultCredit/view/frontend/layout/` (QCC-CUSTOMER-001) — depends on T039
- [X] T041 [P] [US2] Add frontend templates in `app/code/ICC/QuickConsultCredit/view/frontend/templates/` displaying current balance and paginated transaction history with columns date, type, amount, balance-after, and message (QCC-CUSTOMER-002/003) — depends on T039
- [X] T042 [US2] Apply the configured customer history page size (QCC-CONFIG-004) to transaction history pagination in `Block/Account/Credit.php` (QCC-CUSTOMER-004) — depends on T020, T039

**Checkpoint**: User Stories 1 AND 2 both work independently

---

## Phase 5: User Story 3 - Administrator manually adjusts a customer's credit (Priority: P3)

**Goal**: An authorized administrator adds or removes credit from a customer's account via the Admin customer-edit page, with a mandatory reason recorded against the administrator's identity.

**Independent Test**: Perform an authorized Add Credit and Remove Credit action and confirm the resulting balance, ledger entries, and recorded administrator identity/reason (quickstart Scenario 5).

### Tests for User Story 3

- [X] T043 [P] [US3] MFTF functional test in `app/code/ICC/QuickConsultCredit/Test/Mftf/Test/AdminCreditAdjustmentTest.xml` covering authorized Add Credit, authorized Remove Credit, insufficient-balance removal rejection, missing-reason rejection, and unauthorized-administrator rejection (QCC-ADMIN-003/004/005/009, quickstart Scenario 5)
- [X] T044 [P] [US3] Integration test in `app/code/ICC/QuickConsultCredit/Test/Integration/Model/Service/CreditTransactionManagementAdminTest.php` verifying admin add/remove atomicity and that administrator identity + reason are recorded on the ledger entry (QCC-ADMIN-006/007, QCC-AUDIT-002/003)

### Implementation for User Story 3

- [X] T045 [US3] Implement `app/code/ICC/QuickConsultCredit/Block/Adminhtml/Customer/Edit/Tab/Credit.php` displaying current balance, lifetime totals, and full transaction history on the customer edit page (QCC-ADMIN-001/002) — depends on T009, T011
- [X] T046 [P] [US3] Implement `app/code/ICC/QuickConsultCredit/Controller/Adminhtml/Customer/Credit/Save.php` handling Add/Remove Credit form submission: requires a non-empty reason (QCC-ADMIN-005, QCC-LEDGER-004), delegates entirely to `CreditTransactionManagementInterface` (no direct persistence access, QCC-DATA-008), and enforces the `ICC_QuickConsultCredit::manage` ACL resource (QCC-ADMIN-008/009) — depends on T010, T016, T019
- [X] T047 [P] [US3] Add adminhtml layout XML wiring the Credit tab into the customer edit page in `app/code/ICC/QuickConsultCredit/view/adminhtml/layout/` (QCC-ADMIN-001) — depends on T045
- [X] T048 [P] [US3] Add adminhtml templates for the Credit tab (balance, lifetime totals, history grid, Add/Remove Credit forms) in `app/code/ICC/QuickConsultCredit/view/adminhtml/templates/` — depends on T045
- [X] T049 [US3] Display administrator identity and reason for every `ADMIN_ADD`/`ADMIN_REMOVE` history row in the Credit tab's history view (QCC-AUDIT-002/003) — depends on T045, T046

**Checkpoint**: All user stories (US1, US2, US3) are now independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Regression safety, remaining NFR coverage, and end-to-end validation across all stories

- [X] T050 [P] Add regression test coverage in `app/code/ICC/QuickConsultCredit/Test/Integration/Regression/ExistingCheckoutAndCustomerFlowsTest.php` (or MFTF equivalent) confirming existing checkout, customer account, order-processing, and admin customer-management behavior is unaffected for products/customers not involved with Quick Consult Credit (QCC-NFR-005)
- [X] T051 [P] Add unit tests for whole-number-only amount handling (rejecting fractional/decimal input) in `app/code/ICC/QuickConsultCredit/Test/Unit/Model/Service/Validator/TransactionValidatorTest.php` (CLA-001/CLA-002 resolved precision rule)
- [X] T052 [P] Expand `app/code/ICC/QuickConsultCredit/README.md` with configuration and usage documentation cross-referencing [configuration.md](../non-functional/configuration.md)
- [X] T053 Execute all six [quickstart.md](../plan/quickstart.md) validation scenarios end-to-end against a deployed instance and record pass/fail results, including the concurrency scenario (two simultaneous redemptions where only one may succeed, QCC-REDEEM-011/012, QCC-DATA-006) and Scenario 4 (SC-004 — a UI navigation-count criterion inspection-verified by this task, not by an automated assertion) — results recorded in [quickstart-results.md](../plan/quickstart-results.md)
- [X] T054 Security review pass across all layers (API, Admin, customer dashboard, logs) confirming no authentication tokens, credentials, or internal exception details are ever disclosed (QCC-SEC-005/006, QCC-AUDIT-005), and that rejected-request/resubmission-accepted audit log entries are distinguishable from operational-failure log entries (QCC-AUDIT-007) — found and fixed one issue: `Controller/Adminhtml/Customer/Credit/Save.php` previously displayed raw `\Exception::getMessage()` for any exception type; now only `LocalizedException` messages (this module's own curated, safe exceptions) are shown, everything else is logged internally and a generic message is shown instead
- [X] T060 [P] Add an architecture/layering test in `app/code/ICC/QuickConsultCredit/Test/Integration/Architecture/ServiceContractBoundaryTest.php` (e.g., via static reflection/source-scanning of the module's PHP namespace, or an equivalent PHPStan/Deptrac-style rule) asserting that `Controller/*`, `Block/*`, and any `etc/webapi.xml`-mapped API layer classes invoke balance-changing and balance/ledger-read behavior exclusively through `Api/CreditBalanceManagementInterface`, `Api/CreditTransactionManagementInterface`, and `Api/CreditLedgerInterface`, and never directly reference `Model/ResourceModel/*` or `Model/CreditBalance`/`Model/CreditTransaction` classes — proving the customer dashboard (US2), Admin adjustment (US3), and REST API (US1) paths share one non-duplicated rule set rather than three independently reimplemented ones (QCC-NFR-004, QCC-DATA-007/008) — depends on T037, T038, T039, T045, T046, T030, T031

**Checkpoint**: Feature is complete, regression-safe, and fully validated against quickstart.md

---

## Phase 7: CI/CD Quality Gates

**Purpose**: Automate the quality gates already mandated by Constitution Principle X/XI (coding standard, static analysis, SonarQube Blocker/Critical + Security Hotspot review, unit/integration/API test execution) on every push/PR, closing the gap noted in [plan.md](../plan/plan.md) Architect Review Addendum §A12/A14 (no CI pipeline currently exists in this repository)

- [X] T057 [P] Create `.github/workflows/quick-consult-credit-ci.yml` running `vendor/bin/phpcs` with the Magento coding standard (`magento/magento-coding-standard`) and `vendor/bin/phpstan` static analysis against `app/code/ICC/QuickConsultCredit/` on every push/PR touching that path, plus `vendor/bin/phpunit` for `Test/Unit` — depends on T001
- [X] T058 [P] Extend `.github/workflows/quick-consult-credit-ci.yml` with a job running `Test/Integration` and `Test/Api` suites against a configured MySQL test database service (per `dev/tests/integration`/`dev/tests/api-functional` conventions) — depends on T021, T023, T024, T025, T036, T044, T057
- [X] T059 [P] Add a SonarQube analysis job to `.github/workflows/quick-consult-credit-ci.yml` (SonarScanner CLI against a configured SonarQube Server/Cloud project, or an equivalent scanner using the same rule set) covering `app/code/ICC/QuickConsultCredit/` on every push/PR, configured to fail the job on any unresolved Blocker- or Critical-severity issue and to flag every newly introduced Security Hotspot for review — satisfying Constitution Principle XI's CI-level enforcement; additionally document in `app/code/ICC/QuickConsultCredit/README.md` that developers MUST also run SonarQube for IDE (Connected Mode) locally and resolve/justify every Blocker/Critical/new Security Hotspot **before each commit**, per Constitution Principle XI's pre-commit requirement (CI alone does not satisfy that clause) — depends on T001, T052

**Checkpoint**: Every push/PR affecting the module automatically runs coding-standard, static-analysis, SonarQube (Blocker/Critical + Security Hotspot), unit, integration, and API test gates per Constitution Principle X/XI; the pre-commit SonarQube expectation is documented for developers

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion — BLOCKS all user stories (schema, service-contract boundary, ACL tree, and configuration are shared by every story)
- **User Stories (Phase 3-5)**: All depend on Foundational phase completion; the three stories have **no dependencies on each other** (each routes exclusively through the Phase 2 service contracts) and may proceed in parallel or in priority order (P1 → P2 → P3)
- **Polish (Phase 6)**: Depends on all desired user stories being complete; T060 (architecture/layering test) additionally depends on the specific controller/block/API tasks it inspects (T030, T031, T037, T038, T039, T045, T046) across all three user stories
- **CI/CD (Phase 7)**: T057 depends only on Setup and can run from the start of the project in parallel with all other phases; T058 depends on the test suites it executes existing (US1/US2/US3 test tasks); T059 depends on Setup (T001) and the README (T052) it documents the pre-commit expectation in, and can otherwise run in parallel with T057/T058

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational — no dependency on US2 or US3
- **User Story 2 (P2)**: Can start after Foundational — no dependency on US1 or US3 (dashboard reads via `CreditBalanceManagementInterface`/`CreditLedgerInterface` directly, not via the REST layer built in US1)
- **User Story 3 (P3)**: Can start after Foundational — no dependency on US1 or US2 (admin tab reads/writes via the same service contracts, not via the REST layer or dashboard)

### Within Each User Story

- Tests are written first and should fail before implementation
- Service/observer logic before controller/UI wiring
- Core implementation before cross-cutting concerns (logging, replay handling)

### Parallel Opportunities

- T002, T003 (Setup) in parallel
- T006-T011 (Api interfaces), T012-T014 (Models/Validator), T019-T020 (ACL/config), T021-T022 (foundational tests) in parallel within Phase 2, subject to the sequential db_schema.xml edits (T004 → T005) and the service-implementation dependency chain (T015-T018)
- T023-T026 (US1 tests) in parallel
- T035-T036 (US2 tests), T037-T038 (US2 controllers) in parallel
- T043-T044 (US3 tests) in parallel
- Once Foundational (Phase 2) completes, US1, US2, and US3 can be staffed and worked in parallel by different developers

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "API contract test for GET balance in Test/Api/BalanceGetTest.php"
Task: "API contract test for POST transactions in Test/Api/TransactionCreateTest.php"
Task: "Integration test for purchase-posting idempotency in Test/Integration/Model/Service/CreditPurchaseProcessorTest.php"
Task: "MFTF functional test for purchase-to-balance flow"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL — blocks all stories)
3. Complete Phase 3: User Story 1 (purchase + redemption via REST API)
4. **STOP and VALIDATE**: Run quickstart.md Scenarios 1-3 independently
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Validate via quickstart Scenarios 1-3 → Deploy/Demo (MVP!)
3. Add User Story 2 → Validate via quickstart Scenario 4 → Deploy/Demo
4. Add User Story 3 → Validate via quickstart Scenario 5 → Deploy/Demo
5. Polish phase → Validate quickstart Scenario 6 (concurrency) and full regression (QCC-NFR-005)

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (purchase posting + REST API)
   - Developer B: User Story 2 (customer dashboard)
   - Developer C: User Story 3 (admin adjustment)
3. Stories complete and integrate independently through the shared Phase 2 service contracts

---

## Resolved Specification Decisions (previously tracked as non-blocking exceptions)

- **CLA-004** (API idempotency mechanism): Resolved 2026-09-16 — no dedicated request-idempotency/deduplication mechanism is implemented for the create-transaction endpoint. T033 verifies and documents this behavior rather than implementing a `request_reference` field. This is a final decision, not an outstanding sign-off item.
- **CLA-012** (performance target): Resolved 2026-09-16 — no formal SLA is required for this release (QCC-NFR-001). T053's quickstart validation and the efficient design choices in Foundational (materialized balance, indexed pagination) remain in place as sound engineering practice, independent of this decision.
- **CLA-016** (create-transaction `transaction_type` scope): Resolved 2026-09-16 (following `/speckit-analyze` finding C2) — `POST /V1/quick-consult-credit/transactions` accepts `transaction_type = REDEEM` only. `PURCHASE` remains system-triggered exclusively via `CreditPurchaseProcessor`/`OrderCreditPost` (T027-T029); `ADMIN_ADD`/`ADMIN_REMOVE` remain exclusively performed via the Admin customer-edit Credit tab (T046). T031 enforces this scope at the route/handler level and T024 verifies rejection of out-of-scope types with `INVALID_TRANSACTION_TYPE`.
- **CLA-020** (missing/malformed required create-transaction fields and non-integer `amount`): Resolved 2026-09-16 (following `/speckit-analyze` finding I1) — a create-transaction request missing `customer_id`, `transaction_type`, or `amount`, or supplying a non-integer/fractional `amount`, is rejected with a new `INVALID_REQUEST` error (HTTP 400), distinct from `INVALID_AMOUNT`. T032 implements the error mapping and T024 verifies it.

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story is independently completable and testable per its own Independent Test criterion above
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Constraint fields (nullable/required, enum values, uniqueness, defaults) from [data-model.md](../plan/data-model.md) are quoted verbatim in T004/T005 rather than left to implementation-time discretion

---

## Task Traceability Matrix

Full Task ID / Requirement ID / Component / Action / Dependencies / Validation mapping for every task above, completing the abbreviated example started in [plan.md](../plan/plan.md) Architect Review Addendum §A15. Requirement IDs are omitted (`—`) only for pure scaffolding/wiring tasks that do not correspond to a normative requirement ID.

| Task ID | Requirement ID(s) | Component | Action | Dependencies | Validation |
|---|---|---|---|---|---|
| T001 | — | `etc/module.xml`, `registration.php` | Register `ICC_QuickConsultCredit` with declared dependencies on `Magento_Customer`/`Magento_Sales`/`Magento_Webapi`/`Magento_Backend`/`Magento_Authorization` only | None | `bin/magento module:status` shows the module enabled |
| T002 | — | `composer.json` | Add optional package metadata | T001 | `composer validate` passes |
| T003 | — | `README.md` | Document module purpose, link spec/config | T001 | Manual review |
| T004 | QCC-ACCOUNT-001/003/006/007, QCC-DATA-001 | `etc/db_schema.xml` | Define `qcc_customer_credit` table (unique `customer_id`, non-negative `balance`, `total_credited`, `total_debited`) | T001 | `setup:upgrade` creates table matching [data-model.md](../plan/data-model.md) |
| T005 | QCC-LEDGER-002/003/004/005/006/009, QCC-PURCHASE-011 | `etc/db_schema.xml` | Define `qcc_credit_transaction` table + composite `(customer_id, created_at, entity_id)` index | T004 | `setup:upgrade` creates table matching [data-model.md](../plan/data-model.md) |
| T006 | QCC-ACCOUNT (balance contract) | `Api/Data/CreditBalanceInterface.php` | Define balance data interface | T001 | Matches [contracts/service-contracts.md](../contracts/service-contracts.md) |
| T007 | QCC-LEDGER (ledger field contract) | `Api/Data/CreditTransactionInterface.php` | Define transaction data interface | T001 | Matches [data-model.md](../plan/data-model.md) Credit Transaction fields |
| T008 | QCC-LEDGER-009 | `Api/Data/CreditTransactionSearchResultsInterface.php` | Define paginated search-results interface | T001 | Extends `SearchResultsInterface` |
| T009 | QCC-ACCOUNT-004 | `Api/CreditBalanceManagementInterface.php` | Define balance read/lazy-create interface | T001 | Matches [contracts/service-contracts.md](../contracts/service-contracts.md) |
| T010 | QCC-DATA-007/008 | `Api/CreditTransactionManagementInterface.php` | Define credit/debit/admin-add/admin-remove interface | T001 | Matches [contracts/service-contracts.md](../contracts/service-contracts.md) |
| T011 | QCC-SEC-004 | `Api/CreditLedgerInterface.php` | Define paginated per-customer ledger-read interface | T001 | Matches [contracts/service-contracts.md](../contracts/service-contracts.md) |
| T012 | QCC-ACCOUNT-001 | `Model/CreditBalance.php` + `ResourceModel/CreditBalance*` | Implement balance model/resource/collection | T004, T006 | CRUD against `qcc_customer_credit` succeeds |
| T013 | QCC-LEDGER-001/008 | `Model/CreditTransaction.php` + `ResourceModel/CreditTransaction*` | Implement immutable, append-only transaction model/resource/collection | T005, T007 | No update/delete save path exists |
| T014 | QCC-REDEEM-004/005, QCC-API-006, QCC-LEDGER-004, QCC-ADMIN-005, QCC-DATA-001 | `Model/Service/Validator/TransactionValidator.php` | Implement amount/reason/balance validation rules | T007 | T022 unit tests pass |
| T015 | QCC-ACCOUNT-004, QCC-API-004 | `Model/Service/CreditBalanceManagement.php` | Implement lazy-create, read-only balance service | T006, T009, T012 | Balance read creates account at 0 if absent, no side effects |
| T016 | QCC-DATA-001/002/003/006 | `Model/Service/CreditTransactionManagement.php` | Implement atomic locking-read/validate/ledger-write/balance-update entry point | T007, T010, T013, T014 | T021 integration test passes |
| T017 | QCC-LEDGER-009, QCC-SEC-004 | `Model/Service/CreditLedger.php` | Implement paginated, customer-scoped ledger read | T008, T011, T013 | T036 integration test passes |
| T018 | — | `etc/di.xml` | Wire `Api/*Interface` preferences to `Model/Service/*` implementations | T015, T016, T017 | `setup:di:compile` succeeds; interfaces resolve |
| T019 | QCC-SEC-002/003 | `etc/acl.xml` | Define `ICC_QuickConsultCredit::credit`/`::manage` ACL tree | T001 | Resources visible under Admin User Role tree |
| T020 | QCC-CONFIG-001/002/003/004 | `etc/adminhtml/system.xml` | Define enable/disable, product reference, qualifying condition, page-size fields | T001 | Fields appear in Admin Stores > Configuration |
| T021 | QCC-DATA-001/002/003/006, QCC-NFR-002 | `Test/Integration/Model/Service/CreditTransactionManagementTest.php` | Write atomicity/row-locking/rollback integration test | T016 | Test passes |
| T022 | QCC-REDEEM-004/005, QCC-LEDGER-004, QCC-DATA-001 | `Test/Unit/Model/Service/Validator/TransactionValidatorTest.php` | Write validator unit tests (zero/negative amount, missing reason, over-debit) | T014 | Test passes |
| T023 | QCC-API-001/002/003/004/009/018 | `Test/Api/BalanceGetTest.php` | Write balance-GET contract test incl. malformed/out-of-range `customerId` (QCC-API-018) | T030 | Test passes |
| T024 | QCC-API-005/006/007/008/010/011/012/013/017/019, QCC-SEC-007, CLA-004, CLA-016, CLA-020 | `Test/Api/TransactionCreateTest.php` | Write transaction-POST contract test incl. REDEEM-only scope, `INVALID_REQUEST` validation, unauthorized+malformed-payload precedence (QCC-API-012 AC-4), and replay behavior | T031 | Test passes |
| T025 | QCC-PURCHASE-001/009/010/011, QCC-DATA-005 | `Test/Integration/Model/Service/CreditPurchaseProcessorTest.php` | Write purchase-posting idempotency integration test | T027 | Test passes, no double-post |
| T026 | QCC-PURCHASE-001/002 | `Test/Mftf/Test/PurchasePostsCreditTest.xml` | Write end-to-end purchase-to-balance MFTF test | T027, T028, T029, T030, T031 | MFTF run passes |
| T027 | QCC-PURCHASE-002/009/010/011 | `Model/Service/CreditPurchaseProcessor.php` | Implement quantity-derived, idempotent purchase posting | T016 | T025 passes |
| T028 | QCC-PROD-005/006/007/008, QCC-PURCHASE-003/004/005/006/007/008 | `Observer/OrderCreditPost.php` | Implement invoice-save observer resolving qualifying items only | T020, T027 | T026 confirms no posting on non-qualifying events |
| T029 | — | `etc/events.xml` | Register observer on `sales_order_invoice_save_after` | T028 | Observer fires on invoice save |
| T030 | QCC-API-009/018 | `etc/webapi.xml` | Define `GET /V1/quick-consult-credit/balance/:customerId` route, incl. malformed/out-of-range `customerId` rejection (QCC-API-018) | T009, T015, T019 | T023 passes |
| T031 | QCC-API-005/017, CLA-007, CLA-016 | `etc/webapi.xml` | Define `POST /V1/quick-consult-credit/transactions` route (integration/admin ACL only, REDEEM only) | T010, T016, T019, T030 | T024 passes |
| T032 | QCC-API-011/012/017/019, QCC-SEC-005/006 | `etc/webapi.xml` handlers | Implement standardized `{success, error_code, message}` error shape incl. `INVALID_TRANSACTION_TYPE`, `INVALID_REQUEST`, and structured `authorization_category`/`decision` logging | T031 | T024 error-case assertions pass; T054 confirms log entries carry the defined category/decision fields |
| T033 | QCC-API-013, CLA-004 | `Model/Service/CreditTransactionManagement.php` | Verify/document no request-level idempotency mechanism exists | T031 | T024 replay assertion passes |
| T034 | QCC-AUDIT-005/007, QCC-NFR-003 | `Model/Service/CreditPurchaseProcessor.php`, `CreditTransactionManagement.php` | Add structured failure logging (no credentials) plus distinct rejected-request/resubmission-accepted audit logging | T016, T027 | T054 confirms no sensitive data in logs and rejected/replay events are distinguishable from operational-failure events |
| T035 | QCC-CUSTOMER-002/003/004/006 | `Test/Mftf/Test/CustomerDashboardTest.xml` | Write dashboard + cross-customer-denial MFTF test | T039, T040, T041 | MFTF run passes |
| T036 | QCC-SEC-004 | `Test/Integration/Model/Service/CreditLedgerTest.php` | Write cross-customer isolation integration test | T017 | Test passes |
| T037 | QCC-CUSTOMER-001/005 | `Controller/Account/Index.php` | Implement My Account landing controller (session identity only) | T018 | Page loads for authenticated customer only |
| T038 | QCC-CUSTOMER-004/005 | `Controller/Account/Transactions.php` | Implement paginated history controller (session identity only) | T018 | Returns session-scoped data only |
| T039 | QCC-CUSTOMER-005/006 | `Block/Account/Credit.php` | Implement block reading balance/history scoped to session customer | T009, T011, T037, T038 | T036 passes |
| T040 | QCC-CUSTOMER-001 | `view/frontend/layout/` | Add My Account navigation entry | T039 | Nav entry visible in My Account |
| T041 | QCC-CUSTOMER-002/003 | `view/frontend/templates/` | Add balance/history templates (date/type/amount/balance-after/message) | T039 | Templates render required columns |
| T042 | QCC-CUSTOMER-004 | `Block/Account/Credit.php` | Apply configured page size to pagination | T020, T039 | Pagination matches configured size |
| T043 | QCC-ADMIN-003/004/005/009 | `Test/Mftf/Test/AdminCreditAdjustmentTest.xml` | Write add/remove/insufficient-balance/missing-reason/unauthorized MFTF test | T045, T046, T047, T048 | MFTF run passes |
| T044 | QCC-ADMIN-006/007, QCC-AUDIT-002/003 | `Test/Integration/Model/Service/CreditTransactionManagementAdminTest.php` | Write admin add/remove atomicity + identity/reason integration test | T016 | Test passes |
| T045 | QCC-ADMIN-001/002 | `Block/Adminhtml/Customer/Edit/Tab/Credit.php` | Implement balance/lifetime-totals/history tab block | T009, T011 | Tab displays balance/totals/history |
| T046 | QCC-ADMIN-005/008/009, QCC-DATA-008 | `Controller/Adminhtml/Customer/Credit/Save.php` | Implement Add/Remove Credit form handling via service contract only | T010, T016, T019 | T044 passes; reason and ACL enforced |
| T047 | QCC-ADMIN-001 | `view/adminhtml/layout/` | Wire Credit tab into customer edit page | T045 | Tab appears on customer edit page |
| T048 | — | `view/adminhtml/templates/` | Add Credit tab templates (history grid, Add/Remove forms) | T045 | Forms render correctly |
| T049 | QCC-AUDIT-002/003 | `Block/Adminhtml/Customer/Edit/Tab/Credit.php` | Display administrator identity + reason per admin history row | T045, T046 | T044 identity/reason assertions pass |
| T050 | QCC-NFR-005 | `Test/Integration/Regression/ExistingCheckoutAndCustomerFlowsTest.php` | Write regression test for unaffected existing flows | All US1-US3 tasks | Test passes |
| T051 | CLA-001/CLA-002 | `Test/Unit/Model/Service/Validator/TransactionValidatorTest.php` | Add whole-number-only (reject fractional) unit tests | T014 | Fractional input rejected |
| T052 | — | `README.md` | Expand configuration/usage documentation | T020 | Manual review |
| T053 | QCC-REDEEM-011/012, QCC-DATA-006, SC-004 (inspection-verified), all quickstart scenarios | [quickstart.md](../plan/quickstart.md) | Execute all 6 quickstart scenarios end-to-end and record pass/fail | T027-T049 | 6/6 scenarios pass |
| T054 | QCC-SEC-005/006, QCC-AUDIT-005/007 | API/Admin/dashboard/logs (cross-cutting) | Perform security review for sensitive-data disclosure and rejected/replay audit-log distinguishability | T032, T034 | No disclosure found across layers; rejected/replay events distinguishable from operational failures |
| T060 | QCC-NFR-004, QCC-DATA-007/008 | `Test/Integration/Architecture/ServiceContractBoundaryTest.php` | Write architecture/layering test proving Controller/Block/API layers call only `Api/*Interface` methods, never `Model/ResourceModel` directly | T037, T038, T039, T045, T046, T030, T031 | Test passes; no direct persistence reference found in Controller/Block/API layers |
| T055 | QCC-PROD-001/002/003 | Admin Catalog (deployment/config task, no code) | Create Quick Consult Credit product, assign Consultation Services attribute set | T020 | [quickstart.md](../plan/quickstart.md) prerequisites checklist passes |
| T056 | QCC-PROD-004 | Cart/checkout plugin or observer | Implement guest-checkout prevention for the credit product | T020, T027 | Guest checkout with product is rejected or forces authentication |
| T057 | plan.md §A12/§A14 (CI dependency, not a QCC-ID) | `.github/workflows/quick-consult-credit-ci.yml` | Create CI job for coding standard + static analysis + unit tests | T001 | Workflow runs green on PR |
| T058 | plan.md §A12 (CI dependency, not a QCC-ID) | `.github/workflows/quick-consult-credit-ci.yml` | Extend CI with integration + API-functional test jobs against a test DB | T021, T023, T024, T025, T036, T044, T057 | CI job passes with configured test database |
| T059 | Constitution Principle XI (Quality Gates), plan.md §A12 (CI dependency, not a QCC-ID) | `.github/workflows/quick-consult-credit-ci.yml`, `README.md` | Add CI SonarQube job (fail on unresolved Blocker/Critical, flag new Security Hotspots) and document the mandatory pre-commit SonarQube for IDE gate | T001, T052 | CI job fails on an introduced Blocker/Critical issue; README documents pre-commit gate |

---

## Open Questions

All 15 specification ambiguities (CLA-001 through CLA-015) are resolved in [clarifications.md](../clarifications.md) — no requirement or acceptance criterion is unresolved. The following are downstream **operational/process** questions that the specification set does not answer and that this task list cannot resolve on its own (carried over from [plan.md](../plan/plan.md) Architect Review Addendum §A14, Open Questions 1-3):

1. **Product/attribute-set provisioning ownership**: Who owns creating the Quick Consult Credit product and Consultation Services attribute-set assignment in each environment (dev/staging/prod)? T055 implements the one-time task, but per-environment execution ownership/runbook placement is unresolved.
2. **Integration-partner documentation ownership**: Should the "no create-transaction idempotency mechanism" behavior (CLA-004; see T033) be documented for external integration partners as part of this feature's delivery, or is that owned by a separate API-partner-docs process? No task in this list authors that document.
3. **CI pipeline ownership**: T057/T058 add a feature-scoped GitHub Actions workflow to close the gap noted in [plan.md](../plan/plan.md) §A12/§A14, but whether a repository-wide CI strategy already exists or is planned separately (which this workflow should conform to rather than duplicate) is unresolved.
