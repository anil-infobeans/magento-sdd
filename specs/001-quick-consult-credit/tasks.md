---

description: "Task list template for feature implementation"
---

# Tasks: Quick Consult Credit

**Input**: Design documents from `/specs/001-quick-consult-credit/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/), [quickstart.md](./quickstart.md)

**Tests**: Test tasks are included because [non-functional.md](./non-functional.md) contains explicit, mandatory testing requirements (QCC-TEST-001–008) covering unit, integration, API-functional, Magento functional/MFTF, and concurrency layers — these are formal acceptance requirements, not optional additions.

**Organization**: Tasks are grouped by user story (from [spec.md](./spec.md)) to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Every task includes an exact file path under `app/code/ICC/QuickConsultCredit/` (module does not yet exist; created starting Phase 1)

## Path Conventions

Single Magento 2 module project per [plan.md](./plan.md#project-structure): `app/code/ICC/QuickConsultCredit/` with `Test/Unit`, `Test/Integration`, `Test/Api`, `Test/Mftf` subfolders, plus `dev/tests/integration/testsuite/ICC/QuickConsultCredit/` and `dev/tests/api-functional/testsuite/ICC/QuickConsultCredit/` harness locations.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Module scaffolding and registration

- [ ] T001 Create module registration file `app/code/ICC/QuickConsultCredit/registration.php` and `app/code/ICC/QuickConsultCredit/etc/module.xml` declaring module `ICC_QuickConsultCredit`
- [ ] T002 [P] Create `app/code/ICC/QuickConsultCredit/composer.json` with `magento/framework`, `magento/module-customer`, `magento/module-sales`, `magento/module-webapi`, `magento/module-catalog` dependencies per [plan.md](./plan.md#technical-context)
- [ ] T003 [P] Create `app/code/ICC/QuickConsultCredit/README.md` documenting module purpose, denomination configuration, and API endpoints
- [ ] T004 Enable the module (`bin/magento module:enable ICC_QuickConsultCredit && bin/magento setup:upgrade`) and verify it loads with no errors

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Data schema, service-contract interfaces, ACL, validators, and configuration that every user story depends on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T005 Define `qcc_customer_credit` table in `app/code/ICC/QuickConsultCredit/etc/db_schema.xml` with columns `entity_id` (int, PK, auto-increment), `customer_id` (int unsigned, not null, unique), `currency_code` (char(3), not null), `balance` (decimal 14,2, not null, default 0.00), `total_credited` (decimal 14,2, not null, default 0.00), `total_debited` (decimal 14,2, not null, default 0.00), `created_at` (timestamp, not null, default CURRENT_TIMESTAMP), `updated_at` (timestamp, not null, default CURRENT_TIMESTAMP on update) per [data-model.md](./data-model.md#entity-customer-credit-account-qcc_customer_credit)
- [ ] T006 Add `qcc_credit_transaction` table to `app/code/ICC/QuickConsultCredit/etc/db_schema.xml` with columns `entity_id`, `customer_id`, `transaction_type` (varchar 32, not null, enum `PURCHASE|REDEEM|ADMIN_ADD|ADMIN_REMOVE`), `amount` (decimal 14,2, not null, must be > 0), `direction` (varchar 8, not null, enum `CREDIT|DEBIT`), `balance_before` (decimal 14,2, not null), `balance_after` (decimal 14,2, not null), `currency_code` (char(3), not null), `reference_type` (varchar 32, nullable), `reference_id` (varchar 128, nullable), `idempotency_key` (varchar 128, nullable), `message` (text, nullable), `source` (varchar 32, not null, enum `SYSTEM|API|ADMIN|CUSTOMER`), `created_by` (varchar 128, nullable), `created_at` (timestamp, not null); add composite index `(customer_id, created_at, entity_id)`, unique index on `idempotency_key` (nulls excluded), and unique index enforcing at most one row per `(reference_type, reference_id)` where `reference_type = 'ORDER_ITEM'` per [data-model.md](./data-model.md#entity-credit-transaction--ledger-entry-qcc_credit_transaction)
- [ ] T007 [P] Create `app/code/ICC/QuickConsultCredit/Api/Data/CreditBalanceInterface.php` with getters `getCustomerId(): int`, `getCurrencyCode(): string`, `getBalance(): string`, `getTotalCredited(): string`, `getTotalDebited(): string`, `getUpdatedAt(): string` per [contracts/service-contracts.md](./contracts/service-contracts.md#data-interfaces-apidata)
- [ ] T008 [P] Create `app/code/ICC/QuickConsultCredit/Api/Data/CreditTransactionInterface.php` with all getters listed in [contracts/service-contracts.md](./contracts/service-contracts.md#data-interfaces-apidata) (`getEntityId`, `getCustomerId`, `getTransactionType`, `getDirection`, `getAmount`, `getBalanceBefore`, `getBalanceAfter`, `getCurrencyCode`, `getReferenceType`, `getReferenceId`, `getIdempotencyKey`, `getMessage`, `getSource`, `getCreatedBy`, `getCreatedAt`)
- [ ] T009 [P] Create `app/code/ICC/QuickConsultCredit/Api/Data/CreditTransactionSearchResultsInterface.php` extending `Magento\Framework\Api\SearchResultsInterface` for paginated ledger retrieval
- [ ] T010 [P] Create `app/code/ICC/QuickConsultCredit/Model/CreditBalance.php` implementing `CreditBalanceInterface` (extends `AbstractExtensibleModel`)
- [ ] T011 [P] Create `app/code/ICC/QuickConsultCredit/Model/CreditTransaction.php` implementing `CreditTransactionInterface` (extends `AbstractExtensibleModel`)
- [ ] T012 [P] Create `app/code/ICC/QuickConsultCredit/Model/ResourceModel/CreditBalance.php` and `app/code/ICC/QuickConsultCredit/Model/ResourceModel/CreditBalance/Collection.php` bound to `qcc_customer_credit`
- [ ] T013 [P] Create `app/code/ICC/QuickConsultCredit/Model/ResourceModel/CreditTransaction.php` and `app/code/ICC/QuickConsultCredit/Model/ResourceModel/CreditTransaction/Collection.php` bound to `qcc_credit_transaction`
- [ ] T014 Create `app/code/ICC/QuickConsultCredit/etc/di.xml` wiring `CreditBalanceInterface` → `Model\CreditBalance`, `CreditTransactionInterface` → `Model\CreditTransaction`, and the corresponding resource-model/collection preferences (depends on T007-T013)
- [ ] T015 Create `app/code/ICC/QuickConsultCredit/etc/acl.xml` defining parent ACL resource `ICC_QuickConsultCredit::credit` and child resource `ICC_QuickConsultCredit::manage` per [security.md](./security.md) QCC-SEC-002
- [ ] T016 [P] Create `app/code/ICC/QuickConsultCredit/Model/Service/Validator/AmountValidator.php` rejecting any amount that is not strictly greater than zero or not expressed to exactly 2 decimal places, per [account.md](./account.md) QCC-CURR-002/003
- [ ] T017 [P] Create `app/code/ICC/QuickConsultCredit/Model/Service/Validator/CustomerValidator.php` resolving `customer_id` to an existing Magento customer, throwing `NoSuchEntityException` (`CUSTOMER_NOT_FOUND`) otherwise
- [ ] T018 [P] Create `app/code/ICC/QuickConsultCredit/Model/Service/Validator/CurrencyValidator.php` rejecting a request whose currency does not match the account's `currency_code`, per [account.md](./account.md) QCC-CURR-004 (no conversion performed, per QCC-CURR-005)
- [ ] T019 [P] Create `app/code/ICC/QuickConsultCredit/Api/Exception/InsufficientBalanceException.php` and `app/code/ICC/QuickConsultCredit/Api/Exception/IdempotencyKeyConflictException.php` per [contracts/service-contracts.md](./contracts/service-contracts.md#credittransactionmanagementinterface)
- [ ] T020 Create `app/code/ICC/QuickConsultCredit/etc/system.xml` with a configurable denomination list (default 25.00/50.00/100.00/250.00), the qualifying-condition setting, and the "API idempotency required" toggle (default enabled) per [research.md](./research.md) Decisions 1 and 5, and [product.md](./product.md) QCC-PROD-004/005
- [ ] T021 Create a data patch under `app/code/ICC/QuickConsultCredit/Setup/Patch/Data/` that creates the "Consultation Services" attribute set derived from Magento's Default attribute set if it does not already exist, per [product.md](./product.md) QCC-PROD-002a

**Checkpoint**: Foundation ready — user story implementation can now begin

---

## Phase 3: User Story 1 - Customer purchases and later redeems consultation credit (Priority: P1) 🎯 MVP

**Goal**: A qualifying purchase posts credit exactly once, and an authorized external caller can redeem credit against the resulting balance, with insufficient-balance requests rejected.

**Independent Test**: Complete a qualifying order for a $100 denomination, confirm the balance becomes $100 with one `PURCHASE` ledger row, then submit a $25 redemption via the transaction API and confirm the balance becomes $75 with one `REDEEM` ledger row (per [spec.md](./spec.md#user-story-1---customer-purchases-and-later-redeems-consultation-credit-priority-p1)).

### Tests for User Story 1 ⚠️

- [ ] T022 [P] [US1] Unit tests for `AmountValidator`, `CustomerValidator`, `CurrencyValidator` in `app/code/ICC/QuickConsultCredit/Test/Unit/Validator/` per QCC-TEST-001
- [ ] T023 [P] [US1] Integration test for exactly-once purchase posting and rollback-on-failure in `dev/tests/integration/testsuite/ICC/QuickConsultCredit/PurchasePostingTest.php` per QCC-TEST-002, validating QCC-PURCHASE-007/008/009/016
- [ ] T024 [P] [US1] API-functional test for `POST /V1/quick-consult-credit/transactions` (redeem success, `INSUFFICIENT_BALANCE`, `INVALID_AMOUNT`, idempotent replay, `IDEMPOTENCY_KEY_CONFLICT`) in `dev/tests/api-functional/testsuite/ICC/QuickConsultCredit/TransactionCreateTest.php` per QCC-TEST-003 and [contracts/rest-api.md](./contracts/rest-api.md#endpoint-2-create-credit-transaction-redeem)
- [ ] T025 [P] [US1] Concurrency test: two concurrent $80.00 redemption requests against a $100.00 balance in `dev/tests/integration/testsuite/ICC/QuickConsultCredit/ConcurrencyRedeemTest.php` per QCC-TEST-005, validating QCC-CONC-007

### Implementation for User Story 1

- [ ] T026 [US1] Create the Quick Consult Credit product with denominations 25.00/50.00/100.00/250.00 assigned to the Consultation Services attribute set, using the T020 configuration and T021 attribute set, per [product.md](./product.md) QCC-PROD-001/003/009 (depends on T020, T021)
- [ ] T027 [US1] Implement `getBalance(int $customerId): CreditBalanceInterface` in `app/code/ICC/QuickConsultCredit/Model/Service/CreditBalanceManagement.php`, creating a zero-balance account on first access per [account.md](./account.md) QCC-ACCOUNT-003/004 (depends on T010, T012, T017)
- [ ] T028 [US1] Implement `postPurchase(OrderItemInterface $orderItem): ?CreditTransactionInterface` in `app/code/ICC/QuickConsultCredit/Model/Service/CreditPurchaseProcessor.php`: compute credit amount as denomination × quantity (QCC-PROD-008), lock the `qcc_customer_credit` row, insert the `PURCHASE`/`CREDIT` ledger row with `reference_type=ORDER_ITEM`/`reference_id=sales_order_item_id` inside one DB transaction, and no-op if the reference already exists (depends on T026, T027)
- [ ] T029 [US1] Create `app/code/ICC/QuickConsultCredit/Observer/OrderCreditPost.php` evaluating the qualifying condition (invoice generated AND status `complete` for non-virtual orders or `processing` for virtual orders) before invoking `CreditPurchaseProcessor::postPurchase`, per [purchase.md](./purchase.md) QCC-PURCHASE-001/003 (depends on T028)
- [ ] T030 [US1] Register the observer on `sales_order_invoice_save_after` and `sales_order_save_after` in `app/code/ICC/QuickConsultCredit/etc/events.xml` (depends on T029)
- [ ] T031 [US1] Implement `redeem(int $customerId, string $amount, string $referenceType, string $referenceId, string $idempotencyKey, ?string $message = null): CreditTransactionInterface` in `app/code/ICC/QuickConsultCredit/Model/Service/CreditTransactionManagement.php`: validate via T016-T018 validators, check authorization before existence (per [security.md](./security.md) QCC-SEC-008), lock the account row, reject with `InsufficientBalanceException` if amount exceeds balance, persist the `idempotency_key` atomically, and return the stored result on key replay with identical payload or throw `IdempotencyKeyConflictException` on replay with a different payload (depends on T019, T027)
- [ ] T032 [US1] Create `app/code/ICC/QuickConsultCredit/etc/webapi.xml` route `POST /V1/quick-consult-credit/transactions` bound to the redeem operation, secured by ACL `ICC_QuickConsultCredit::manage`, rejecting any `transaction_type` other than `REDEEM` from non-admin callers with `INVALID_TRANSACTION_TYPE` per [api.md](./api.md) QCC-API-016 (depends on T031)
- [ ] T033 [US1] Implement the standardized error payload mapping (`success: false`, `error_code`, `message` — `INSUFFICIENT_BALANCE`, `INVALID_AMOUNT`, `CUSTOMER_NOT_FOUND`, `IDEMPOTENCY_KEY_CONFLICT`) in the webapi exception-mapping layer per [contracts/rest-api.md](./contracts/rest-api.md#endpoint-2-create-credit-transaction-redeem) (depends on T032)
- [ ] T034 [US1] Add operational failure logging (customer id, transaction type, amount, reference, failure reason; no tokens/credentials) for purchase-posting and redemption failures per [audit-observability.md](./audit-observability.md) QCC-AUDIT-003/004/007 (depends on T028, T031)

**Checkpoint**: User Story 1 is fully functional and independently testable — purchase posts credit exactly once; redemption debits correctly and rejects insufficient-balance/invalid/duplicate requests.

---

## Phase 4: User Story 2 - Customer reviews their credit balance and history (Priority: P2)

**Goal**: A customer can view their own balance and paginated history in My Account, and cannot access another customer's data by any means.

**Independent Test**: Log in as a customer with existing `PURCHASE`/`REDEEM` transactions, view the dashboard, confirm displayed balance/history match the ledger, then confirm a request for another customer's data is denied (per [spec.md](./spec.md#user-story-2---customer-reviews-their-credit-balance-and-history-priority-p2)).

### Tests for User Story 2 ⚠️

- [ ] T035 [P] [US2] MFTF test verifying the "Quick Consult Credit" My Account navigation entry, balance display, and history table columns in `app/code/ICC/QuickConsultCredit/Test/Mftf/` per QCC-TEST-004
- [ ] T036 [P] [US2] Integration test confirming Customer A cannot retrieve Customer B's balance/history via a tampered client-supplied identifier in `dev/tests/integration/testsuite/ICC/QuickConsultCredit/CustomerIsolationTest.php` per QCC-CUSTOMER-006/007/008
- [ ] T037 [P] [US2] API-functional test for `GET /V1/quick-consult-credit/balance/:customerId` (authorization-before-existence ordering, zero-balance-for-no-account) in `dev/tests/api-functional/testsuite/ICC/QuickConsultCredit/BalanceRetrievalTest.php` per QCC-API-005/006/007 and [contracts/rest-api.md](./contracts/rest-api.md#endpoint-1-get-credit-balance)

### Implementation for User Story 2

- [ ] T038 [US2] Create `app/code/ICC/QuickConsultCredit/Api/CreditLedgerInterface.php` with `getHistory(int $customerId, int $pageSize = 20, int $currentPage = 1): array` and `getHistoryCount(int $customerId): int` per [contracts/service-contracts.md](./contracts/service-contracts.md#creditledgerinterface) (depends on T009)
- [ ] T039 [US2] Implement `app/code/ICC/QuickConsultCredit/Model/Service/CreditLedger.php` returning newest-first, paginated results with a default page size of 20 per [ledger.md](./ledger.md) QCC-LEDGER-010/012 and [non-functional.md](./non-functional.md) QCC-PERF-006 (depends on T013, T038)
- [ ] T040 [US2] Add route `GET /V1/quick-consult-credit/balance/:customerId` to `app/code/ICC/QuickConsultCredit/etc/webapi.xml` with self-access ACL semantics and authorization-checked-before-existence-check ordering per [contracts/rest-api.md](./contracts/rest-api.md#endpoint-1-get-credit-balance) (depends on T027)
- [ ] T041 [US2] Create `app/code/ICC/QuickConsultCredit/Controller/Account/Index.php` deriving the customer identity exclusively from the authenticated session context, never from client input, per [customer-dashboard.md](./customer-dashboard.md) QCC-CUSTOMER-006/007 (depends on T027)
- [ ] T042 [US2] Create `app/code/ICC/QuickConsultCredit/Controller/Account/Transactions.php` returning the authenticated customer's paginated history via `CreditLedgerInterface` (depends on T039, T041)
- [ ] T043 [US2] Create `app/code/ICC/QuickConsultCredit/Block/Account/Credit.php` exposing balance and history data to the frontend template
- [ ] T044 [US2] Create `app/code/ICC/QuickConsultCredit/view/frontend/layout/` and `app/code/ICC/QuickConsultCredit/view/frontend/templates/` for the "Quick Consult Credit" My Account navigation entry and dashboard page (balance, currency, paginated history table with date/type/signed amount/previous balance/resulting balance/message) per [customer-dashboard.md](./customer-dashboard.md) QCC-CUSTOMER-001-005 (depends on T043)

**Checkpoint**: User Stories 1 AND 2 both work independently — customers can self-serve balance/history with full isolation.

---

## Phase 5: User Story 3 - Administrator manages a customer's credit balance (Priority: P3)

**Goal**: An authorized administrator can view a customer's balance/lifetime totals/history and add or remove credit with a mandatory reason, recorded against the admin's identity, gated by a dedicated ACL resource.

**Independent Test**: An authorized administrator opens a customer record, adds $20 credit with a reason, confirms the balance/ledger update, then attempts to remove more credit than available and confirms rejection (per [spec.md](./spec.md#user-story-3---administrator-manages-a-customers-credit-balance-priority-p3)).

### Tests for User Story 3 ⚠️

- [ ] T045 [P] [US3] MFTF test for admin add/remove credit with reason capture and ACL-denial-when-lacking-resource in `app/code/ICC/QuickConsultCredit/Test/Mftf/` per QCC-TEST-004
- [ ] T046 [P] [US3] Integration test verifying admin add/remove atomicity, administrator-identity recording, and reason recording in `dev/tests/integration/testsuite/ICC/QuickConsultCredit/AdminAdjustmentTest.php` per QCC-ADMIN-009/010/011

### Implementation for User Story 3

- [ ] T047 [US3] Extend `app/code/ICC/QuickConsultCredit/Model/Service/CreditTransactionManagement.php` with `adminAdd(int $customerId, string $amount, string $message, string $createdBy): CreditTransactionInterface` (amount > 0, mandatory `$message`) and `adminRemove(...)` (additionally amount ≤ current balance, throws `InsufficientBalanceException` otherwise), each committing balance + cumulative statistic + ledger row atomically per [admin.md](./admin.md) QCC-ADMIN-004/005/007/008/009 (depends on T031)
- [ ] T048 [US3] Create `app/code/ICC/QuickConsultCredit/Controller/Adminhtml/Customer/Credit/AddCredit.php` enforcing ACL resource `ICC_QuickConsultCredit::manage` independent of UI visibility per [admin.md](./admin.md) QCC-ADMIN-013/014 (depends on T047)
- [ ] T049 [US3] Create `app/code/ICC/QuickConsultCredit/Controller/Adminhtml/Customer/Credit/RemoveCredit.php` with the same ACL enforcement (depends on T047)
- [ ] T050 [US3] Create `app/code/ICC/QuickConsultCredit/Block/Adminhtml/Customer/Edit/Tab/Credit.php` displaying current balance, lifetime credited/debited totals, and full ledger history per [admin.md](./admin.md) QCC-ADMIN-001/002 (depends on T039)
- [ ] T051 [US3] Create `app/code/ICC/QuickConsultCredit/etc/adminhtml/routes.xml` and `app/code/ICC/QuickConsultCredit/view/adminhtml/layout/` + `app/code/ICC/QuickConsultCredit/view/adminhtml/templates/` for the customer-edit "Quick Consult Credit" tab with add/remove credit forms (depends on T048, T049, T050)

**Checkpoint**: All three user stories are independently functional — purchase/redeem, customer self-service, and admin management all work per their own acceptance criteria.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Improvements and validations that span all three user stories

- [ ] T052 [P] Finalize `app/code/ICC/QuickConsultCredit/README.md` with configuration, denomination management, and API usage instructions
- [ ] T053 [P] Add regression test coverage spanning checkout, customer account, admin customer-edit, and order processing in `app/code/ICC/QuickConsultCredit/Test/Mftf/` per [non-functional.md](./non-functional.md) QCC-TEST-006
- [ ] T054 Execute performance validation against the approved p95 targets (500ms balance/history retrieval, 1s redemption/admin view) under nominal load per [non-functional.md](./non-functional.md) QCC-PERF-001-004
- [ ] T055 [P] Conduct a security review confirming no authentication tokens/credentials appear in any log entry or API response across all surfaces per [security.md](./security.md) QCC-SEC-006 and [audit-observability.md](./audit-observability.md) QCC-AUDIT-004
- [ ] T056 Execute the [quickstart.md](./quickstart.md) validation scenarios end-to-end against the completed implementation

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Setup completion — BLOCKS all user stories
- **User Story 1 (Phase 3)**: Depends only on Foundational
- **User Story 2 (Phase 4)**: Depends only on Foundational (T027 from US1 is a shared foundational-adjacent service method reused, not a hard story dependency — see note below)
- **User Story 3 (Phase 5)**: Depends on Foundational; T047 extends the same `CreditTransactionManagement.php` file created in T031 (US1), so T047 must be sequenced after T031 completes even though the stories are otherwise independently testable
- **Polish (Phase 6)**: Depends on all three user stories being complete

### Note on Shared File: `CreditTransactionManagement.php`

T031 (US1) creates this file with the `redeem()` method; T047 (US3) extends the same file with `adminAdd()`/`adminRemove()`. This is a deliberate, minimal shared-file dependency (same service class per [contracts/service-contracts.md](./contracts/service-contracts.md#credittransactionmanagementinterface)) — US3 cannot start its T047 task until T031 is merged, but US3's other tasks (T045, T046, T048-T051) have no such constraint.

### Note on Shared Method: `CreditBalanceManagement::getBalance`

T027 (US1) implements `getBalance()`, which T040/T041 (US2) and T050 (US3) also call. It is placed in US1 because User Story 1's independent test requires balance confirmation first; US2 and US3 phases depend on T027 having been completed.

### Parallel Opportunities

- All Setup tasks marked [P] (T002, T003) can run in parallel
- Within Foundational, T007-T013 (interfaces/models/resource models) marked [P] can run in parallel; T016-T019 (validators/exceptions) marked [P] can run in parallel
- All test tasks within a story marked [P] can run in parallel (T022-T025; T035-T037; T045-T046)
- Once Foundational and T031 are complete, US2 and US3 implementation tasks can largely proceed in parallel by different developers

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Unit tests for AmountValidator, CustomerValidator, CurrencyValidator in Test/Unit/Validator/"
Task: "Integration test for exactly-once purchase posting in dev/tests/integration/testsuite/ICC/QuickConsultCredit/PurchasePostingTest.php"
Task: "API-functional test for POST /V1/quick-consult-credit/transactions in dev/tests/api-functional/testsuite/ICC/QuickConsultCredit/TransactionCreateTest.php"
Task: "Concurrency test for two concurrent $80 redeems in dev/tests/integration/testsuite/ICC/QuickConsultCredit/ConcurrencyRedeemTest.php"
```

## Parallel Example: Foundational Data Interfaces

```bash
Task: "Create Api/Data/CreditBalanceInterface.php"
Task: "Create Api/Data/CreditTransactionInterface.php"
Task: "Create Api/Data/CreditTransactionSearchResultsInterface.php"
Task: "Create Model/CreditBalance.php"
Task: "Create Model/CreditTransaction.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL — blocks all stories)
3. Complete Phase 3: User Story 1 (purchase posting + redemption via API)
4. **STOP and VALIDATE**: Run T022-T025 tests and the relevant [quickstart.md](./quickstart.md) scenarios (Sections 2-6, 8) independently
5. Deploy/demo if ready — this alone delivers the core value proposition per [spec.md](./spec.md#user-story-1---customer-purchases-and-later-redeems-consultation-credit-priority-p1)

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (MVP!)
3. Add User Story 2 → Test independently (dashboard + isolation) → Deploy/Demo
4. Add User Story 3 → Test independently (admin add/remove + ACL) → Deploy/Demo
5. Complete Polish phase → Final release validation

### Parallel Team Strategy

1. Team completes Setup + Foundational together
2. Once Foundational (and T031) is done:
   - Developer A: User Story 1 remainder (T026-T034 minus T031, or leads T031)
   - Developer B: User Story 2 (T035-T044)
   - Developer C: User Story 3 (T045-T051, blocked on T031 only for T047)
3. Stories complete and integrate independently; Polish phase (T052-T056) follows

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Field-level constraints in T005/T006 are quoted verbatim from [data-model.md](./data-model.md) so they are not left to implementation-time discretion
