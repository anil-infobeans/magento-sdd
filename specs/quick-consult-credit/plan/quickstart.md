# Quickstart: Quick Consult Credit

**Input**: [contracts/rest-api.md](../contracts/rest-api.md); [data-model.md](./data-model.md); [configuration.md](../non-functional/configuration.md); [testing-and-acceptance.md](../non-functional/testing-and-acceptance.md)

**Purpose**: Runnable validation scenarios that prove the feature works end-to-end once implemented. This is a validation/run guide, not an implementation guide — it references contracts and the data model rather than duplicating them, and contains no model/service/controller code.

## Prerequisites

- Magento 2.4.8 (Adobe Commerce) instance with `ICC_QuickConsultCredit` module deployed and enabled.
- Consultation Services attribute set exists, with a Quick Consult Credit product assigned to it ([product-configuration.md](../functional/product-configuration.md) QCC-PROD-001/002).
- Module configuration set ([configuration.md](../non-functional/configuration.md)):
  - Module enabled (QCC-CONFIG-001).
  - Qualifying condition left at its resolved default (invoice generated / payment captured) or explicitly overridden (QCC-CONFIG-003).
  - Customer history page size left at default (20) or configured (QCC-CONFIG-004).
- A registered (non-guest) test customer account exists.
- An Admin user exists with the `ICC_QuickConsultCredit::manage` ACL resource granted.
- An authorized API integration (or Admin token) exists for calling the create-transaction endpoint.

## Setup Commands

```bash
# From the Magento root
bin/magento module:enable ICC_QuickConsultCredit
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

**Expected outcome**: Module enabled with no errors; `qcc_customer_credit` and `qcc_credit_transaction` tables exist in the database schema ([data-model.md](./data-model.md)).

## Scenario 1: Qualifying purchase posts credit (User Story 1, part 1)

1. As the test customer, add the Quick Consult Credit product to the cart with quantity 100.
2. Complete checkout with a payment method that reaches the configured qualifying condition (e.g., generate an invoice for the order).
3. Call `GET /rest/V1/quick-consult-credit/balance/:customerId` with the customer's own token.

**Expected outcome**: Response is `{"customer_id": <id>, "balance": 100}` ([contracts/rest-api.md](../contracts/rest-api.md) Endpoint 1). Exactly one `PURCHASE` ledger entry exists for that customer (QCC-PURCHASE-001, QCC-LEDGER-002).

**Negative check**: Adding the product to the cart alone (before checkout) must not change the balance (QCC-PROD-005, QCC-PURCHASE-003).

## Scenario 2: Authorized redemption debits balance (User Story 1, part 2)

1. Using an authorized integration or Admin token, call:

   ```http
   POST /rest/V1/quick-consult-credit/transactions
   {
     "customer_id": <id>,
     "transaction_type": "REDEEM",
     "amount": 25,
     "message": "Consultation completed"
   }
   ```

2. Re-check the balance via Endpoint 1.

**Expected outcome**: Response reports `previous_balance: 100`, `current_balance: 75` ([contracts/rest-api.md](../contracts/rest-api.md) Endpoint 2). Exactly one `REDEEM` ledger entry exists (QCC-REDEEM-002).

**Negative check**: Repeat the exact same request. Per the resolved decision in [clarifications.md](../clarifications.md) CLA-004 (no dedicated idempotency mechanism), the resubmitted request is validated independently against the balance at that time and MAY be accepted as an additional debit if it individually passes validation (e.g., sufficient remaining balance) — this is expected behavior, not a defect (QCC-REDEEM-010).

## Scenario 3: Insufficient balance is rejected

1. Call the create-transaction endpoint requesting an amount greater than the current balance (e.g., 1000 against a balance of 75).

**Expected outcome**: `400 INSUFFICIENT_BALANCE`; balance unchanged; no new ledger entry (QCC-REDEEM-006, [contracts/rest-api.md](../contracts/rest-api.md) Error responses table).

## Scenario 4: Customer dashboard shows own data only (User Story 2)

1. Log in to the storefront as the test customer.
2. Navigate to My Account → Quick Consult Credit.

**Expected outcome**: Current balance and paginated transaction history are displayed, matching the ledger from Scenarios 1–3 (QCC-CUSTOMER-002/003/004).

3. Attempt to access a second customer's balance/history (e.g., by manipulating a request to reference another customer's ID).

**Expected outcome**: Request denied; no data disclosed (QCC-CUSTOMER-006, SC-005 in [spec.md](../spec.md)).

## Scenario 5: Admin add/remove credit (User Story 3)

1. Log in to Magento Admin as the authorized administrator.
2. Open the test customer's record → Quick Consult Credit tab.
3. Click Add Credit, enter amount 20 and a reason, submit.

**Expected outcome**: Balance increases by 20; `ADMIN_ADD` ledger entry records the administrator's identity and reason (QCC-ADMIN-003/006).

4. Click Remove Credit, enter an amount greater than the current balance, submit.

**Expected outcome**: Request rejected; balance unchanged; no ledger entry created (QCC-ADMIN-004 AC-2).

5. Click Remove Credit again with a valid amount and reason.

**Expected outcome**: Balance decreases accordingly; `ADMIN_REMOVE` ledger entry records administrator identity and reason (QCC-ADMIN-004 AC-1, QCC-ADMIN-006).

6. Attempt the same actions logged in as an Admin user without the `ICC_QuickConsultCredit::manage` permission.

**Expected outcome**: Actions denied; balance unchanged (QCC-ADMIN-009).

## Scenario 6: Concurrency safety

1. Issue two concurrent redemption requests for an amount that individually fits the balance but whose sum exceeds it (e.g., balance 100, two requests for 80 each).

**Expected outcome**: At most one request succeeds; final balance is never negative (QCC-REDEEM-011/012, QCC-DATA-001/006).

## Running the Automated Test Suites

```bash
# Unit tests
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/ICC/QuickConsultCredit/Test/Unit

# Integration tests (requires configured dev/tests/integration/etc/install-config-mysql.php)
vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist app/code/ICC/QuickConsultCredit/Test/Integration

# API-functional tests (requires configured dev/tests/api-functional environment)
vendor/bin/phpunit -c dev/tests/api-functional/phpunit.xml.dist app/code/ICC/QuickConsultCredit/Test/Api

# MFTF functional/regression tests (once test modules are authored)
vendor/bin/mftf run:test <QuickConsultCredit test names>
```

**Expected outcome**: All suites pass, covering the scenarios in [testing-and-acceptance.md](../non-functional/testing-and-acceptance.md) Consolidated Acceptance Test Scenarios.

## Success Criteria Traceability

| Quickstart scenario | [spec.md](../spec.md) Success Criteria |
|---|---|
| Scenario 1 | SC-001 |
| Scenario 2, 3 | SC-002, SC-003 |
| Scenario 4 | SC-004, SC-005 |
| Scenario 5 | SC-002, SC-006 |
| Scenario 6 | SC-003 |
