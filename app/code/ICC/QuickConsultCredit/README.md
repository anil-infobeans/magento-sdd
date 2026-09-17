# ICC_QuickConsultCredit

Quick Consult Credit is a Magento/Adobe Commerce module implementing a prepaid,
customer-owned credit balance that is sold as a Magento product and consumed against
consultation services.

## Purpose

- Customers purchase a "Quick Consult Credit" product; a qualifying order posts credit
  to the customer's account exactly once.
- An authorized external integration or Magento admin redeems (debits) part of that
  balance via a REST API.
- Customers can view their current balance and full transaction history from a
  dedicated "Quick Consult Credit" section in My Account.
- Administrators can manually add or remove credit from the Admin customer-edit page,
  with a mandatory reason recorded against the administrator's identity.

Full functional and non-functional requirements, the implementation plan, data model,
API/service contracts, and task breakdown live under
[specs/quick-consult-credit](../../../../specs/quick-consult-credit/spec.md) at the
repository root:

- [spec.md](../../../../specs/quick-consult-credit/spec.md) — master feature specification
- [functional/](../../../../specs/quick-consult-credit/functional/) — functional sub-specifications (QCC-PROD, QCC-ACCOUNT, QCC-LEDGER, QCC-PURCHASE, QCC-REDEEM, QCC-API, QCC-CUSTOMER, QCC-ADMIN)
- [non-functional/](../../../../specs/quick-consult-credit/non-functional/) — security, audit, data-integrity, configuration, and testing/acceptance sub-specifications
- [plan/](../../../../specs/quick-consult-credit/plan/) — implementation plan, research decisions, data model, quickstart validation scenarios
- [contracts/](../../../../specs/quick-consult-credit/contracts/) — REST API and internal service-contract definitions
- [tasks/tasks.md](../../../../specs/quick-consult-credit/tasks/tasks.md) — execution task list

## Architecture

This module is entirely additive: it introduces two new database tables
(`qcc_customer_credit`, `qcc_credit_transaction`), four service-contract interfaces
under `Api/`, and three consumer layers (REST API, customer My Account dashboard, Admin
customer-edit tab) that all route exclusively through those service contracts. No
existing Magento module, table, or core file is modified.

## Configuration

Configurable at **Stores > Configuration > ICC > Quick Consult Credit**. See
[non-functional/configuration.md](../../../../specs/quick-consult-credit/non-functional/configuration.md)
for the full normative specification.

| Field | Purpose | Default |
|---|---|---|
| Enable Quick Consult Credit | Module enable/disable (QCC-CONFIG-001) | Enabled |
| Credit Product SKU / Attribute Set | Identifies the Quick Consult Credit catalog product (QCC-CONFIG-002) | — |
| Qualifying Order/Payment Condition | Determines when a purchase posts credit (QCC-CONFIG-003) | Invoice generated / payment captured |
| Customer Transaction History Page Size | Pagination size for the My Account history table (QCC-CONFIG-004) | 20 |

Before the module can post credit for purchases, an administrator must:

1. Enable the module and set the **Credit Product SKU** to an existing catalog product's SKU.
2. Create (or designate) that catalog product using the "Consultation Services" attribute
   set so it is sold as the Quick Consult Credit product (see
   [functional/product-configuration.md](../../../../specs/quick-consult-credit/functional/product-configuration.md)).
   Guest checkout is automatically blocked for this product (QCC-PROD-004); it must be
   purchased by a registered customer.

## Usage

### Customers: viewing balance and history

A logged-in customer sees a "Quick Consult Credit" link in My Account navigation,
leading to `quickconsultcredit/account/index`, which shows the current balance and a
paginated transaction history (date, type, amount, balance-after, message). Only the
signed-in customer's own data is ever shown (QCC-CUSTOMER-005/006).

### Administrators: manually adjusting a customer's balance

On the Admin **Customers > All Customers > Edit** page, the "Quick Consult Credit" tab
shows the current balance, lifetime totals, and full transaction history (including the
administrator username and reason recorded against every entry, QCC-AUDIT-002/003). The
Add/Remove Credit form requires a non-empty reason and enforces the
`ICC_QuickConsultCredit::manage` ACL resource.

### Integrations: redeeming credit via REST

Only an admin/integration token may redeem credit — there is no customer self-access to
this endpoint (CLA-007). See [contracts/rest-api.md](../../../../specs/quick-consult-credit/contracts/rest-api.md)
for the full contract.

```bash
# Get a customer's current balance (customer self-access token, or admin/integration token)
curl -X GET "https://<host>/rest/V1/quick-consult-credit/balance/12345" \
  -H "Authorization: Bearer <token>"
# => { "customer_id": 12345, "balance": 75 }

# Redeem 25 credit points (admin/integration token only)
curl -X POST "https://<host>/rest/V1/quick-consult-credit/transactions" \
  -H "Authorization: Bearer <admin-or-integration-token>" \
  -H "Content-Type: application/json" \
  -d '{"customer_id": 12345, "transaction_type": "REDEEM", "amount": 25, "message": "Consultation completed"}'
# => { "transaction_id": 98765, "type": "REDEEM", "amount": 25, "previous_balance": 100, "current_balance": 75 }
```

`amount` must be a whole, positive integer (CLA-001/CLA-002); a fractional or missing
value is rejected as `INVALID_REQUEST`, and a structurally valid but non-positive
amount is rejected as `INVALID_AMOUNT`. `transaction_type` must be `REDEEM` — `PURCHASE`
is system-triggered only, and `ADMIN_ADD`/`ADMIN_REMOVE` are only ever performed via the
Admin Credit tab, never through this endpoint.

### How a purchase posts credit automatically

When the configured qualifying condition is met (default: an invoice is generated for
an order containing the configured Credit Product SKU), `Observer/OrderCreditPost`
posts a `PURCHASE` credit transaction for the ordered quantity exactly once per order
item, keyed by a unique `source_reference` so duplicate event delivery never
double-credits the balance (QCC-PURCHASE-009/010/011).

## Design Decisions

### `POST /V1/quick-consult-credit/transactions` has no request-level idempotency (QCC-API-013)

Per [clarifications.md](../../../../specs/quick-consult-credit/clarifications.md) CLA-004
(resolved 2026-09-16), the create-transaction (redeem) endpoint deliberately has **no**
request-identity or deduplication field, and **no** persistence-layer uniqueness check
on this path. Every request is validated independently against the customer's balance
at the time it is processed; a resubmitted (replayed) request may therefore produce an
additional, separately valid debit if the balance still supports it. Callers
(integrations) are responsible for their own retry/dedup semantics if needed.

This is distinct from the purchase-posting path
(`Model/Service/CreditPurchaseProcessor`), where `source_reference` (the order item ID)
**is** required and enforced unique (QCC-PURCHASE-011) — that uniqueness exists to make
invoice-save-event re-delivery idempotent, not to deduplicate REST client requests.

Every accepted create-transaction call is logged via
`AuthorizationAuditLogger::logResubmissionAccepted()`, and every rejected one via
`AuthorizationAuditLogger::logRejection()` (QCC-AUDIT-007), separate from the
operational-failure logging in `CreditTransactionManagement::execute()`.

## Quality Gates

Every push/PR touching `app/code/ICC/QuickConsultCredit/` is expected to pass, via CI
(`.github/workflows/quick-consult-credit-ci.yml`):

- Magento coding standard (`vendor/bin/phpcs` with `magento/magento-coding-standard`; run with `-n` so only errors, not pre-existing docblock-style warnings, fail the build)
- Static analysis (`vendor/bin/phpstan`, configured via `phpstan.neon.dist` in this module; requires `bin/magento setup:di:compile` to have run first so generated factory/interceptor classes resolve — the `ignoreErrors` entries in that file are documented, narrowly-scoped Magento typing gaps, not blanket suppressions)
- Unit tests (`vendor/bin/phpunit` against `Test/Unit`)
- Integration and API-functional test suites against a configured MySQL test database
- SonarQube analysis (fails on unresolved Blocker/Critical issues; flags new Security Hotspots for review)

**Developers MUST also run SonarQube for IDE (Connected Mode) locally and resolve or
justify every Blocker/Critical issue and new Security Hotspot before each commit.**
The CI SonarQube job enforces this at the pipeline level, but it does not replace the
pre-commit expectation — CI alone is a safety net, not a substitute for local review
before code is committed.
