# Quickstart: Quick Consult Credit Validation Guide

This guide describes how to set up and manually/functionally validate the Quick Consult Credit module against its acceptance scenarios. It references [data-model.md](./data-model.md) and [contracts/](./contracts/) rather than duplicating schemas. It does **not** contain implementation code — see `/speckit-tasks` and `/speckit-implement` for build steps.

## 1. Setup

1. Enable the module: `bin/magento module:enable ICC_QuickConsultCredit && bin/magento setup:upgrade`.
2. Configure denominations: Admin → Stores → Configuration → (Quick Consult Credit section) → set the allowed denomination list (defaults 25.00/50.00/100.00/250.00 per QCC-PROD-003; [research.md](./research.md) Decision 1).
3. Confirm the "Consultation Services" attribute set exists or is auto-created on first relevant product save (QCC-PROD-002a).
4. Create/verify at least one storefront customer account and one Admin user with the `ICC_QuickConsultCredit::manage` ACL resource granted (QCC-ADMIN-002).
5. Obtain an integration/admin API token for exercising [contracts/rest-api.md](./contracts/rest-api.md).

## 2. Scenario: Purchase Credits Balance (User Story P1)

- **Setup**: Customer has $0.00 balance. Place an order containing a Quick Consult Credit product (denomination $50.00).
- **Action**: Complete checkout so the order reaches `processing` (virtual product) or `complete` with an invoice generated (non-virtual), per the resolved qualifying condition (QCC-PURCHASE-001/003).
- **Expected outcome**: A `qcc_credit_transaction` row is created with `transaction_type=PURCHASE`, `direction=CREDIT`, `amount=50.00`, `reference_type=ORDER_ITEM`; `qcc_customer_credit.balance` increases by 50.00; `GET /V1/quick-consult-credit/balance/:customerId` (see [contracts/rest-api.md](./contracts/rest-api.md#endpoint-1-get-credit-balance)) reflects the new balance.
- **Re-run check**: Re-triggering the same order-item event (e.g., re-saving the invoice) must **not** create a second ledger row — verifies the purchase idempotency key ([research.md](./research.md) Decision 3).

## 3. Scenario: Redeem Credit via External API (User Story P2)

- **Setup**: Customer has a $100.00 balance.
- **Action**: `POST /V1/quick-consult-credit/transactions` with `transaction_type=REDEEM`, `amount=25.00`, a fresh `idempotency_key` (see [contracts/rest-api.md](./contracts/rest-api.md#endpoint-2-create-credit-transaction-redeem)).
- **Expected outcome**: `200 OK`; balance decreases to $75.00; a `DEBIT`-direction ledger row is created.
- **Idempotent replay check**: Re-send the identical request/key → same `200` result, no new ledger row (QCC-IDEMP-003/005).
- **Conflicting replay check**: Re-send the same `idempotency_key` with a different `amount` → `409 IDEMPOTENCY_KEY_CONFLICT` ([research.md](./research.md) Decision 5).

## 4. Scenario: Insufficient Balance (Edge Case)

- **Setup**: Customer has a $10.00 balance.
- **Action**: `POST /V1/quick-consult-credit/transactions` with `amount=25.00`.
- **Expected outcome**: `409 INSUFFICIENT_BALANCE`; balance unchanged; no ledger row created (QCC-REDEEM-003).

## 5. Scenario: Authorization Precedence (Security)

- **Action**: Call `GET /V1/quick-consult-credit/balance/:customerId` with no token, using a `customerId` that does not exist.
- **Expected outcome**: `401`, **not** `404` — confirms authorization is evaluated before the existence check (QCC-SEC-008, QCC-API-005/007).

## 6. Scenario: External Caller Restricted to REDEEM

- **Action**: `POST /V1/quick-consult-credit/transactions` with `transaction_type=PURCHASE` (or `ADMIN_ADD`/`ADMIN_REMOVE`) using an external/API token.
- **Expected outcome**: `400 INVALID_TRANSACTION_TYPE` — confirms external callers cannot post any transaction type other than `REDEEM` (QCC-API-016).

## 7. Scenario: Admin Manual Credit Adjustment (User Story P3)

- **Setup**: Admin user with `ICC_QuickConsultCredit::manage` grant, viewing a customer's edit page (Quick Consult Credit tab, QCC-ADMIN-003).
- **Action (Add)**: Submit an "Add Credit" action with amount `20.00` and a mandatory reason message (QCC-ADMIN-005).
- **Expected outcome**: Balance increases by `20.00`; ledger row `transaction_type=ADMIN_ADD`, `source=ADMIN`, `created_by=<admin username>` (QCC-ADMIN-010).
- **Action (Remove)**: Submit a "Remove Credit" action for an amount exceeding the current balance.
- **Expected outcome**: Rejected with an insufficient-balance error; no ledger row created (QCC-ADMIN-008/009).

## 8. Scenario: Concurrency (Non-Functional)

- **Action**: Issue two near-simultaneous `REDEEM` requests for the same customer, each for an amount that individually fits the balance but together would overdraw it (e.g., balance $30.00, two concurrent requests for $20.00 each).
- **Expected outcome**: Exactly one request succeeds (`200`), the other fails with `409 INSUFFICIENT_BALANCE`; final balance reflects only the successful debit — confirms row-level pessimistic locking ([research.md](./research.md) Decision 4, QCC-CONC-001-004). Automated coverage lives in the dedicated concurrency test described in [research.md](./research.md) Decision 8.

## 9. Scenario: Customer Dashboard & Admin History View

- **Action**: As the customer, view the My Account credit dashboard tab (QCC-CUSTOMER-002/003).
- **Expected outcome**: Current balance and a paginated (page size 20, QCC-LEDGER-012) transaction history are displayed, newest-first (QCC-LEDGER-010), sourced from `CreditLedgerInterface::getHistory()` ([contracts/service-contracts.md](./contracts/service-contracts.md#creditledgerinterface)).
- **Action**: As an admin, view the same customer's Quick Consult Credit tab in the Admin customer-edit page.
- **Expected outcome**: Same ledger data is visible, plus the Add/Remove Credit actions (QCC-ADMIN-003/004).

## 10. Non-Functional Spot Checks

- Balance retrieval (`GET .../balance/:customerId`) responds within 500ms p95 under representative load (QCC-PERF-001).
- Redemption write (`POST .../transactions`) responds within 1s p95 under representative load (QCC-PERF-003).
- These targets are validated via the performance test layer described in [research.md](./research.md) Decision 8, not manually in this quickstart.

## Out of Scope for This Guide

- Database migrations / `db_schema.xml` contents (see [data-model.md](./data-model.md)).
- Full automated test suite implementation (see [research.md](./research.md) Decision 8 and the forthcoming `/speckit-tasks` breakdown).
- UI markup/layout XML details (see [research.md](./research.md) Decision 7).
