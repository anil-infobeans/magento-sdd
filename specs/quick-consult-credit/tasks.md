---

description: "Task list template for feature implementation"
---

# Tasks: Quick Consult Credit

**Input**: Design documents from `/specs/quick-consult-credit/`
**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/rest-api.md](./contracts/rest-api.md), [contracts/service-contracts.md](./contracts/service-contracts.md), [quickstart.md](./quickstart.md), 13 sub-specifications, [clarifications.md](./clarifications.md)

**Tests**: Test tasks are INCLUDED. [testing-and-acceptance.md](./testing-and-acceptance.md) explicitly mandates Unit/Integration/API/Functional/Regression/Security test-layer coverage, and Constitution Principle IV (Testing Standards) requires this at task level.

**Organization**: Tasks are grouped by user story (from [spec.md](./spec.md)) to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Exact file paths are included in each description; all paths are relative to the repository root

## Path Conventions

Single Magento module project: `app/code/ICC/QuickConsultCredit/` (per [plan.md](./plan.md) Project Structure). Tests live under the module's own `Test/Unit`, `Test/Integration`, `Test/Api`, and `Test/Mftf` directories, discovered by the corresponding suites in `dev/tests/unit`, `dev/tests/integration`, `dev/tests/api-functional`, and MFTF.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Module initialization and registration

- [ ] T001 Create module registration and manifest: `app/code/ICC/QuickConsultCredit/registration.php` and `app/code/ICC/QuickConsultCredit/etc/module.xml` declaring module `ICC_QuickConsultCredit` with dependencies on `Magento_Customer`, `Magento_Sales`, `Magento_Webapi`, `Magento_Backend`, `Magento_Authorization` only (no third-party libraries, per [plan.md](./plan.md) Primary Dependencies)
- [ ] T002 [P] Create `app/code/ICC/QuickConsultCredit/composer.json` (optional package metadata for future extraction, per [plan.md](./plan.md) Project Structure)
- [ ] T003 [P] Create `app/code/ICC/QuickConsultCredit/README.md` summarizing the module's purpose and linking to [spec.md](./spec.md) and [configuration.md](./configuration.md)

**Checkpoint**: Module skeleton registers cleanly with `bin/magento module:status`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Persistence schema, service-contract boundary, and cross-cutting infrastructure that every user story depends on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T004 Define the `qcc_customer_credit` table in `app/code/ICC/QuickConsultCredit/etc/db_schema.xml`: `entity_id` (identity PK), `customer_id` (integer, **unique** — exactly one account per customer per QCC-ACCOUNT-001), `balance` (integer, not nullable, default 0, must never go negative per QCC-ACCOUNT-003/QCC-DATA-001), `total_credited` (integer, not nullable, default 0, QCC-ACCOUNT-006), `total_debited` (integer, not nullable, default 0, QCC-ACCOUNT-007), `created_at`, `updated_at` — per [data-model.md](./data-model.md) Customer Credit Account
- [ ] T005 Extend `app/code/ICC/QuickConsultCredit/etc/db_schema.xml` with the `qcc_credit_transaction` table: `entity_id` (identity PK), `customer_id` (integer, not nullable), `transaction_type` (enumerated string, not nullable, one of `PURCHASE`/`REDEEM`/`ADMIN_ADD`/`ADMIN_REMOVE` per QCC-LEDGER-002), `direction` (enumerated string, not nullable, `CREDIT`/`DEBIT` per QCC-LEDGER-003), `amount` (integer, positive, not nullable), `balance_before` (integer, not nullable), `balance_after` (integer, not nullable, must equal `balance_before ± amount` per direction), `message` (text, nullable, **required** when `transaction_type` is `ADMIN_ADD`/`ADMIN_REMOVE` per QCC-LEDGER-004), `source` (enumerated string, not nullable, `CUSTOMER`/`API`/`ADMIN`/`SYSTEM` per QCC-LEDGER-005), `created_by` (string, nullable), `source_reference` (string, nullable, **unique when present**, required for `PURCHASE` rows per QCC-PURCHASE-011), `created_at` (server-assigned, not nullable per QCC-LEDGER-006); add a composite index on `(customer_id, created_at, entity_id)` for paginated ordered retrieval (QCC-LEDGER-009) — per [data-model.md](./data-model.md) Credit Transaction
- [ ] T006 [P] Define `app/code/ICC/QuickConsultCredit/Api/Data/CreditBalanceInterface.php` (customer identity + current integer balance) per [data-model.md](./data-model.md) and [contracts/service-contracts.md](./contracts/service-contracts.md) Credit Balance data contract
- [ ] T007 [P] Define `app/code/ICC/QuickConsultCredit/Api/Data/CreditTransactionInterface.php` with getters for every field listed in [data-model.md](./data-model.md) Credit Transaction (transaction_type, direction, amount, balance_before, balance_after, message, source, created_by, source_reference, created_at)
- [ ] T008 [P] Define `app/code/ICC/QuickConsultCredit/Api/Data/CreditTransactionSearchResultsInterface.php` extending `Magento\Framework\Api\SearchResultsInterface` for paginated ledger queries (QCC-LEDGER-009)
- [ ] T009 [P] Define `app/code/ICC/QuickConsultCredit/Api/CreditBalanceManagementInterface.php`: get balance (create lazily with balance 0 if absent, QCC-ACCOUNT-004) — per [contracts/service-contracts.md](./contracts/service-contracts.md) Balance Read/Create interface
- [ ] T010 [P] Define `app/code/ICC/QuickConsultCredit/Api/CreditTransactionManagementInterface.php`: credit, debit/redeem, admin-add, admin-remove operations, each requiring amount > 0 and returning a deterministic error on failure (`INVALID_AMOUNT`, `INSUFFICIENT_BALANCE`, `CUSTOMER_NOT_FOUND`, authorization error) — per [contracts/service-contracts.md](./contracts/service-contracts.md) Transaction Management interface (the sole entry point per QCC-DATA-007/008)
- [ ] T011 [P] Define `app/code/ICC/QuickConsultCredit/Api/CreditLedgerInterface.php`: paginated `getList` by customer identity + search criteria, and `getById`, never returning another customer's entries (QCC-SEC-004) — per [contracts/service-contracts.md](./contracts/service-contracts.md) Ledger Read interface
- [ ] T012 [P] Implement `app/code/ICC/QuickConsultCredit/Model/CreditBalance.php`, `Model/ResourceModel/CreditBalance.php`, and `Model/ResourceModel/CreditBalance/Collection.php` mapped to `qcc_customer_credit`
- [ ] T013 [P] Implement `app/code/ICC/QuickConsultCredit/Model/CreditTransaction.php`, `Model/ResourceModel/CreditTransaction.php`, and `Model/ResourceModel/CreditTransaction/Collection.php` mapped to `qcc_credit_transaction`, with no update/delete save path exposed (immutable/append-only per QCC-LEDGER-001/008)
- [ ] T014 [P] Implement `app/code/ICC/QuickConsultCredit/Model/Service/Validator/TransactionValidator.php` enforcing: amount must be a positive integer (rejects zero per QCC-REDEEM-004 and negative per QCC-REDEEM-005/QCC-API-006), `message` non-empty for `ADMIN_ADD`/`ADMIN_REMOVE` (QCC-LEDGER-004/QCC-ADMIN-005), debit amount ≤ current balance (QCC-DATA-001/QCC-REDEEM-006)
- [ ] T015 Implement `app/code/ICC/QuickConsultCredit/Model/Service/CreditBalanceManagement.php` implementing `CreditBalanceManagementInterface`: lazily creates the account with `balance = 0` on first access if absent (QCC-ACCOUNT-004), read-only with no side effects (QCC-API-004) — depends on T006, T009, T012
- [ ] T016 Implement `app/code/ICC/QuickConsultCredit/Model/Service/CreditTransactionManagement.php` implementing `CreditTransactionManagementInterface` as the single atomic entry point: begins a DB transaction, performs a locking read of the customer's balance row, invokes `TransactionValidator`, writes exactly one ledger row (QCC-DATA-002), updates the materialized balance, commits, and fully rolls back both balance and ledger on any failure (QCC-DATA-001/003/006) — per [research.md](./research.md) §3; depends on T007, T010, T013, T014
- [ ] T017 Implement `app/code/ICC/QuickConsultCredit/Model/Service/CreditLedger.php` implementing `CreditLedgerInterface`: paginated, ordered per-customer transaction listing scoped strictly to the requesting identity, never disclosing another customer's rows (QCC-LEDGER-009, QCC-SEC-004) — depends on T008, T011, T013
- [ ] T018 Wire preferences for all four `Api/*ManagementInterface`/`Api/*Interface` contracts to their `Model/Service/*` implementations in `app/code/ICC/QuickConsultCredit/etc/di.xml` — depends on T015, T016, T017
- [ ] T019 [P] Define the ACL resource tree in `app/code/ICC/QuickConsultCredit/etc/acl.xml`: parent resource `ICC_QuickConsultCredit::credit` with child resource `ICC_QuickConsultCredit::manage` for least-privilege administrative/integration access (QCC-SEC-002/003) — per [research.md](./research.md) §10
- [ ] T020 [P] Define configuration fields in `app/code/ICC/QuickConsultCredit/etc/adminhtml/system.xml`: module enable/disable (QCC-CONFIG-001), credit product/attribute-set reference (QCC-CONFIG-002), qualifying order/payment condition defaulting to "invoice generated / payment captured" (QCC-CONFIG-003, resolved default per [clarifications.md](./clarifications.md) CLA-003), customer transaction history page size defaulting to 20 (QCC-CONFIG-004)
- [ ] T021 [P] Integration test for atomicity and row-locking in `app/code/ICC/QuickConsultCredit/Test/Integration/Model/Service/CreditTransactionManagementTest.php`: verify balance never goes negative, exactly one ledger row per successful operation, and full rollback (no partial state) on a simulated failure (QCC-DATA-001/002/003/006, QCC-NFR-002) — depends on T016
- [ ] T022 [P] Unit tests for `TransactionValidator` in `app/code/ICC/QuickConsultCredit/Test/Unit/Model/Service/Validator/TransactionValidatorTest.php` covering zero amount, negative amount, missing admin reason, and debit exceeding balance (QCC-REDEEM-004/005, QCC-LEDGER-004, QCC-DATA-001) — depends on T014

**Checkpoint**: Foundation ready — user story implementation can now begin

---

## Phase 3: User Story 1 - Customer purchases and later redeems consultation credit (Priority: P1) 🎯 MVP

**Goal**: A qualifying purchase posts credit to the customer's account exactly once; an authorized external system or admin redeems part of that balance via the REST API, with the balance and ledger updated atomically.

**Independent Test**: Complete a qualifying order for the Quick Consult Credit product and confirm the balance/ledger reflect the purchase (quickstart Scenario 1); then submit a valid `POST /V1/quick-consult-credit/transactions` redemption request and confirm the balance/ledger reflect the debit (quickstart Scenario 2).

### Tests for User Story 1

- [ ] T023 [P] [US1] API contract test for `GET /V1/quick-consult-credit/balance/:customerId` in `app/code/ICC/QuickConsultCredit/Test/Api/BalanceGetTest.php` covering: authenticated success, 401 unauthenticated, 403 cross-customer, 404 unknown customer, and 200 zero-balance for a customer with no account (QCC-API-001/002/003/004/009)
- [ ] T024 [P] [US1] API contract test for `POST /V1/quick-consult-credit/transactions` in `app/code/ICC/QuickConsultCredit/Test/Api/TransactionCreateTest.php` covering: authorized success response shape (`transaction_id`, `type`, `amount`, `previous_balance`, `current_balance`), `INVALID_AMOUNT` (zero/negative), `INSUFFICIENT_BALANCE`, `CUSTOMER_NOT_FOUND`, `UNAUTHORIZED` for a customer-session caller, and replay via `request_reference` (QCC-API-005/006/007/008/010/011/012/013)
- [ ] T025 [P] [US1] Integration test for purchase-posting idempotency in `app/code/ICC/QuickConsultCredit/Test/Integration/Model/Service/CreditPurchaseProcessorTest.php`: re-processing the same qualifying order item must not create a second `PURCHASE` ledger row or double-credit the balance (QCC-PURCHASE-001/009/010/011, QCC-DATA-005)
- [ ] T026 [P] [US1] MFTF functional test for the full purchase-to-balance flow (add credit product to cart with quantity, checkout, generate invoice, verify balance via API) in `app/code/ICC/QuickConsultCredit/Test/Mftf/Test/PurchasePostsCreditTest.xml` (QCC-PURCHASE-001/002, quickstart Scenario 1)

### Implementation for User Story 1

- [ ] T027 [US1] Implement `app/code/ICC/QuickConsultCredit/Model/Service/CreditPurchaseProcessor.php`: derive the credit amount from the qualifying order item's purchased quantity (QCC-PURCHASE-002), use the order item identifier as the deterministic `source_reference` (QCC-PURCHASE-011), invoke `CreditTransactionManagementInterface` credit operation with `source = SYSTEM`, and no-op (idempotent skip) if that reference was already posted (QCC-PURCHASE-001/009/010) — depends on T016
- [ ] T028 [US1] Implement `app/code/ICC/QuickConsultCredit/Observer/OrderCreditPost.php`: observes the invoice-save event, resolves qualifying Quick Consult Credit order items from the invoice per the configured qualifying condition, and calls `CreditPurchaseProcessor::postPurchase()` for each; must NOT post on cart addition, quote creation, pending order, failed payment, or cancelled/non-qualifying order (QCC-PROD-005/006/007/008, QCC-PURCHASE-003/004/005/006/007/008) — depends on T020, T027
- [ ] T029 [US1] Register the observer on the `sales_order_invoice_save_after` event in `app/code/ICC/QuickConsultCredit/etc/events.xml` — depends on T028
- [ ] T030 [US1] Define the REST route `GET /V1/quick-consult-credit/balance/:customerId` in `app/code/ICC/QuickConsultCredit/etc/webapi.xml`, mapped to `CreditBalanceManagementInterface`, permitting customer self-access (own ID only, authenticated session is authoritative regardless of the path parameter per QCC-API-009) or admin/integration ACL resource `ICC_QuickConsultCredit::credit` — depends on T009, T015, T019
- [ ] T031 [US1] Extend `app/code/ICC/QuickConsultCredit/etc/webapi.xml` with the REST route `POST /V1/quick-consult-credit/transactions`, mapped to `CreditTransactionManagementInterface`, restricted to integration/admin ACL resource `ICC_QuickConsultCredit::manage` only — **no customer self-access** (CLA-007 resolved, QCC-API-005) — depends on T010, T016, T019, T030
- [ ] T032 [US1] Implement the standardized error-response shape `{ "success": false, "error_code": "...", "message": "..." }` for `INVALID_AMOUNT`, `INSUFFICIENT_BALANCE`, `CUSTOMER_NOT_FOUND`, and `UNAUTHORIZED` across both endpoints, with no internal exception details or credentials disclosed (QCC-API-012, QCC-SEC-005/006) — depends on T031
- [ ] T033 [US1] Add optional `request_reference` support to the create-transaction request: when supplied and previously seen, enforce uniqueness at the persistence layer and return the original result instead of creating a duplicate ledger entry (QCC-API-013, QCC-REDEEM-010, QCC-SEC-008 — recommended pattern per [research.md](./research.md) §6, pending final CLA-004 sign-off) — depends on T031
- [ ] T034 [US1] Add structured failure logging (customer_id, transaction_type, amount, reference, exception summary — no credentials) via `Psr\Log\LoggerInterface` in `CreditPurchaseProcessor` and `CreditTransactionManagement` (QCC-AUDIT-005, QCC-NFR-003) — depends on T016, T027

**Checkpoint**: User Story 1 (MVP) is fully functional and independently testable — purchase posts credit, redemption debits credit, both via the governed service boundary and REST API

---

## Phase 4: User Story 2 - Customer reviews balance and history (Priority: P2)

**Goal**: A logged-in customer sees their current balance and paginated transaction history in My Account, isolated from all other customers' data.

**Independent Test**: Log in as a customer, view the Quick Consult Credit dashboard, and confirm balance/history display correctly; attempt to retrieve another customer's balance/history and confirm it is denied (quickstart Scenario 4).

### Tests for User Story 2

- [ ] T035 [P] [US2] MFTF functional test for the customer dashboard in `app/code/ICC/QuickConsultCredit/Test/Mftf/Test/CustomerDashboardTest.xml`: verify current balance and paginated, correctly ordered transaction history display, and that cross-customer access is denied (QCC-CUSTOMER-002/003/004/006, quickstart Scenario 4)
- [ ] T036 [P] [US2] Integration test in `app/code/ICC/QuickConsultCredit/Test/Integration/Model/Service/CreditLedgerTest.php` verifying `CreditLedger::getList` never returns another customer's ledger entries (QCC-SEC-004)

### Implementation for User Story 2

- [ ] T037 [P] [US2] Implement `app/code/ICC/QuickConsultCredit/Controller/Account/Index.php`: My Account "Quick Consult Credit" landing controller, resolving customer identity solely from the authenticated customer session (never a request parameter) (QCC-CUSTOMER-001/005)
- [ ] T038 [P] [US2] Implement `app/code/ICC/QuickConsultCredit/Controller/Account/Transactions.php`: paginated transaction history controller, session-scoped identity only (QCC-CUSTOMER-004/005)
- [ ] T039 [US2] Implement `app/code/ICC/QuickConsultCredit/Block/Account/Credit.php` reading balance via `CreditBalanceManagementInterface` and history via `CreditLedgerInterface`, scoped strictly to the session customer, denying any attempt to reference another customer's ID (QCC-CUSTOMER-005/006) — depends on T009, T011, T037, T038
- [ ] T040 [P] [US2] Add frontend layout XML for the My Account navigation entry and Quick Consult Credit section in `app/code/ICC/QuickConsultCredit/view/frontend/layout/` (QCC-CUSTOMER-001) — depends on T039
- [ ] T041 [P] [US2] Add frontend templates in `app/code/ICC/QuickConsultCredit/view/frontend/templates/` displaying current balance and paginated transaction history with columns date, type, amount, balance-after, and message (QCC-CUSTOMER-002/003) — depends on T039
- [ ] T042 [US2] Apply the configured customer history page size (QCC-CONFIG-004) to transaction history pagination in `Block/Account/Credit.php` (QCC-CUSTOMER-004) — depends on T020, T039

**Checkpoint**: User Stories 1 AND 2 both work independently

---

## Phase 5: User Story 3 - Administrator manually adjusts a customer's credit (Priority: P3)

**Goal**: An authorized administrator adds or removes credit from a customer's account via the Admin customer-edit page, with a mandatory reason recorded against the administrator's identity.

**Independent Test**: Perform an authorized Add Credit and Remove Credit action and confirm the resulting balance, ledger entries, and recorded administrator identity/reason (quickstart Scenario 5).

### Tests for User Story 3

- [ ] T043 [P] [US3] MFTF functional test in `app/code/ICC/QuickConsultCredit/Test/Mftf/Test/AdminCreditAdjustmentTest.xml` covering authorized Add Credit, authorized Remove Credit, insufficient-balance removal rejection, missing-reason rejection, and unauthorized-administrator rejection (QCC-ADMIN-003/004/005/009, quickstart Scenario 5)
- [ ] T044 [P] [US3] Integration test in `app/code/ICC/QuickConsultCredit/Test/Integration/Model/Service/CreditTransactionManagementAdminTest.php` verifying admin add/remove atomicity and that administrator identity + reason are recorded on the ledger entry (QCC-ADMIN-006/007, QCC-AUDIT-002/003)

### Implementation for User Story 3

- [ ] T045 [US3] Implement `app/code/ICC/QuickConsultCredit/Block/Adminhtml/Customer/Edit/Tab/Credit.php` displaying current balance, lifetime totals, and full transaction history on the customer edit page (QCC-ADMIN-001/002) — depends on T009, T011
- [ ] T046 [P] [US3] Implement `app/code/ICC/QuickConsultCredit/Controller/Adminhtml/Customer/Credit/Save.php` handling Add/Remove Credit form submission: requires a non-empty reason (QCC-ADMIN-005, QCC-LEDGER-004), delegates entirely to `CreditTransactionManagementInterface` (no direct persistence access, QCC-DATA-008), and enforces the `ICC_QuickConsultCredit::manage` ACL resource (QCC-ADMIN-008/009) — depends on T010, T016, T019
- [ ] T047 [P] [US3] Add adminhtml layout XML wiring the Credit tab into the customer edit page in `app/code/ICC/QuickConsultCredit/view/adminhtml/layout/` (QCC-ADMIN-001) — depends on T045
- [ ] T048 [P] [US3] Add adminhtml templates for the Credit tab (balance, lifetime totals, history grid, Add/Remove Credit forms) in `app/code/ICC/QuickConsultCredit/view/adminhtml/templates/` — depends on T045
- [ ] T049 [US3] Display administrator identity and reason for every `ADMIN_ADD`/`ADMIN_REMOVE` history row in the Credit tab's history view (QCC-AUDIT-002/003) — depends on T045, T046

**Checkpoint**: All user stories (US1, US2, US3) are now independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Regression safety, remaining NFR coverage, and end-to-end validation across all stories

- [ ] T050 [P] Add regression test coverage in `app/code/ICC/QuickConsultCredit/Test/Integration/Regression/ExistingCheckoutAndCustomerFlowsTest.php` (or MFTF equivalent) confirming existing checkout, customer account, order-processing, and admin customer-management behavior is unaffected for products/customers not involved with Quick Consult Credit (QCC-NFR-005)
- [ ] T051 [P] Add unit tests for whole-number-only amount handling (rejecting fractional/decimal input) in `app/code/ICC/QuickConsultCredit/Test/Unit/Model/Service/Validator/TransactionValidatorTest.php` (CLA-001/CLA-002 resolved precision rule)
- [ ] T052 [P] Expand `app/code/ICC/QuickConsultCredit/README.md` with configuration and usage documentation cross-referencing [configuration.md](./configuration.md)
- [ ] T053 Execute all six [quickstart.md](./quickstart.md) validation scenarios end-to-end against a deployed instance and record pass/fail results, including the concurrency scenario (two simultaneous redemptions where only one may succeed, QCC-REDEEM-011/012, QCC-DATA-006)
- [ ] T054 Security review pass across all layers (API, Admin, customer dashboard, logs) confirming no authentication tokens, credentials, or internal exception details are ever disclosed (QCC-SEC-005/006, QCC-AUDIT-005)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion — BLOCKS all user stories (schema, service-contract boundary, ACL tree, and configuration are shared by every story)
- **User Stories (Phase 3-5)**: All depend on Foundational phase completion; the three stories have **no dependencies on each other** (each routes exclusively through the Phase 2 service contracts) and may proceed in parallel or in priority order (P1 → P2 → P3)
- **Polish (Phase 6)**: Depends on all desired user stories being complete

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

## Tracked Non-Blocking Exceptions (carried from plan.md Complexity Tracking)

- **CLA-004** (API idempotency-key contract): T033 implements a recommended technical pattern (`request_reference`); the field is not yet a finalized mandatory business contract. Final sign-off by the API/integration architecture owner remains outstanding and does not block T033's implementation.
- **CLA-012** (performance target): No numeric SLA task is defined because none exists in the source documents (QCC-NFR-001). T053's quickstart validation and the efficient design choices in Foundational (materialized balance, indexed pagination) stand in for formal performance sign-off, which remains pending business input.

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story is independently completable and testable per its own Independent Test criterion above
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Constraint fields (nullable/required, enum values, uniqueness, defaults) from [data-model.md](./data-model.md) are quoted verbatim in T004/T005 rather than left to implementation-time discretion
