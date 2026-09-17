# Feature Specification: Quick Consult Credit

**Feature Branch**: `quick-consult-credit`

**Created**: 2026-09-15

**Status**: Draft

**Input**: Quick Consult Credit SRS v1.0 and Quick Consult Credit Technical Architecture — prepaid customer-credit capability integrated with Magento / Adobe Commerce.

**Version**: 1.4
**Type**: Normative (master overview; detailed normative requirements live in the linked sub-specifications below)

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation of Quick Consult Credit specification set | Quick Consult Credit SRS v1.0; Quick Consult Credit Technical Architecture | New feature; no prior specifications existed for this capability |
| 1.1 | Resolved 5 clarifications (credit-unit semantics, decimal precision, qualifying order state default, REST API path, customer self-redemption authorization) | /speckit-clarify session 2026-09-15 | Updated Assumptions, Notes on Source Conflicts, and 8 sub-specifications; unblocks corresponding requirements for implementation planning |
| 1.2 | Reorganized the flat specification-set layout into `functional/`, `non-functional/`, `plan/`, and `tasks/` subfolders (contracts/ and checklists/ retained as their own dedicated subfolders) and updated every internal cross-reference link accordingly; no requirement content, identifier, or acceptance criterion was altered | Constitution v1.4.0 Principle XIII (Specification Organization); Sync Impact Report follow-up TODO, 2026-09-16 | Purely structural — moves 13 sub-specifications, plan.md, research.md, data-model.md, quickstart.md, and tasks.md into their mandated subfolders; all requirement IDs, statements, and acceptance criteria are unchanged |
| 1.3 | Resolved all 8 remaining open ambiguities (API idempotency mechanism, transaction-type casing, extension-attribute necessity, post-cancellation correction, purchase-reference granularity, admin adjustments vs. lifetime totals, performance target, regulatory retention) plus 2 newly identified items (API rate limiting, V1 versioning policy) | /speckit-clarify session 2026-09-16 | Updated this file's Clarifications and Notes on Source Conflicts sections, [clarifications.md](./clarifications.md), and 9 sub-specifications/design artifacts; the register is now fully resolved (15/15) |
| 1.4 | Resolved 4 additional items identified during `/speckit-analyze` follow-up sessions (CLA-016 create-transaction `transaction_type` scope; CLA-017 401 vs. 403 error-payload scope; CLA-018 malformed `customerId` behavior; CLA-019 fixed create-transaction success status/field names); split FR-000-e into FR-000-e (purchase-posting duplicate protection) and new FR-000-f (create-transaction field validation plus the resolved redemption-replay exception), correcting a contradiction with QCC-API-013/QCC-REDEEM-010/QCC-SEC-008 (finding F1) | `/speckit-analyze` findings C2, H1-H4, F1-F3, 2026-09-16 | Updated this file's Requirements and Notes on Source Conflicts sections, [clarifications.md](./clarifications.md) (now 20/20 resolved), [checklists/requirements.md](./checklists/requirements.md), and the affected functional sub-specifications; the register is now fully resolved |

## Overview

Quick Consult Credit is a prepaid, customer-owned credit balance sold as a Magento product and consumed against professional consultation services. A customer purchases credit through standard Magento checkout; once the order reaches a configured qualifying state, the purchased credit is posted to the customer's account. Customers can view their balance and history in their account dashboard. Authorized external systems and Magento administrators can redeem credit and adjust balances through a governed service boundary, with every balance change recorded in an immutable transaction ledger.

This master specification defines the feature's user scenarios, cross-cutting requirements, success criteria, assumptions, and scope boundary. Detailed, testable, per-capability normative requirements are decomposed into the sub-specifications listed in [Specification Index](#specification-index).

## Clarifications

### Session 2026-09-15

- Q: Does buying N units grant N credit points, or a price-based monetary credit amount? → A: Quantity-based (1 unit purchased = 1 credit point), independent of product price.
- Q: Which order/payment state should be configured as the default qualifying condition for posting Quick Consult Credit? → A: Invoice generated (payment captured), used as the default configuration value; the setting remains configurable per [configuration.md](./non-functional/configuration.md) QCC-CONFIG-003.
- Q: May an authenticated customer redeem their own credit directly via the API, or is redemption restricted to authorized external systems and admins only? → A: Restricted to authorized external integration systems and Magento admins only; there is no direct customer-self-redemption API path.
- Q: Which REST API path convention should be the single authoritative contract for the balance and transaction endpoints? → A: The Technical Architecture path convention (`/V1/quick-consult-credit/balance/:customerId` and `/V1/quick-consult-credit/transactions`) is authoritative.
- Q: Which decimal precision/scale should be authoritative for balance and transaction amount fields? → A: Credit points are whole numbers with no fractional value; balance and transaction amounts are stored as integers rather than a decimal type.

### Session 2026-09-16

- Q: How should the create-transaction API detect and reject a duplicate/replayed request? → A: No formal idempotency mechanism. Each request is validated and processed independently; a replayed/retried request MAY result in more than one debit if each attempt individually passes validation. This explicitly narrows SRS §7.2's duplicate-prevention expectation for this interface (see [clarifications.md](./clarifications.md) CLA-004).
- Q: What performance/concurrency target should the balance and transaction APIs meet? → A: No formal SLA is required for this release; explicit decision, not merely an undefined target (CLA-012).
- Q: Which casing convention is authoritative for transaction_type and direction values? → A: UPPERCASE (`PURCHASE`/`REDEEM`/`ADMIN_ADD`/`ADMIN_REMOVE`, `CREDIT`/`DEBIT`), with `direction` as a first-class stored attribute (CLA-006).
- Q: Should purchase-posting duplicate-prevention be keyed at the order-item or order level? → A: Order-item level (`sales_order_item_id`) (CLA-010).
- Q: Should ADMIN_ADD/ADMIN_REMOVE count toward the same lifetime totals as PURCHASE/REDEEM? → A: Yes, included in the same `total_credited`/`total_debited` accumulators; no separate admin-only accumulator (CLA-011).
- Q: Does a specific regulatory jurisdiction or minimum ledger-retention period apply? → A: No specific regulation; the ledger is retained indefinitely as standard operational/audit data (CLA-013).
- Q: Should the credit balance be exposed as a Magento customer extension attribute? → A: Not required for this release; accessible only via the dedicated REST API and UI (CLA-008).
- Q: Should the REST endpoints have a feature-specific rate limit beyond platform defaults? → A: No; rely on Magento's existing platform-level API throttling only (CLA-014).
- Q: What is the policy for introducing breaking changes to the V1 REST contract? → A: V1 is frozen; any backward-incompatible change ships as a new version (CLA-015).
- Q: Is manual Admin Remove Credit sufficient when a posted-credit order is later cancelled/refunded? → A: Yes; no automated reversal workflow is introduced (CLA-009).

## Specification Index

| Specification | Requirement ID prefix | Type |
|---|---|---|
| [product-configuration.md](./functional/product-configuration.md) | QCC-PROD | Normative (functional) |
| [customer-credit-account.md](./functional/customer-credit-account.md) | QCC-ACCOUNT | Normative (functional) |
| [credit-ledger.md](./functional/credit-ledger.md) | QCC-LEDGER | Normative (functional) |
| [credit-purchase-posting.md](./functional/credit-purchase-posting.md) | QCC-PURCHASE | Normative (functional) |
| [credit-redemption.md](./functional/credit-redemption.md) | QCC-REDEEM | Normative (functional) |
| [credit-rest-api.md](./functional/credit-rest-api.md) | QCC-API | Normative (functional) |
| [customer-dashboard.md](./functional/customer-dashboard.md) | QCC-CUSTOMER | Normative (functional) |
| [admin-credit-management.md](./functional/admin-credit-management.md) | QCC-ADMIN | Normative (functional) |
| [security-and-access-control.md](./non-functional/security-and-access-control.md) | QCC-SEC | Normative (non-functional) |
| [audit-and-observability.md](./non-functional/audit-and-observability.md) | QCC-AUDIT | Normative (non-functional) |
| [data-integrity-and-concurrency.md](./non-functional/data-integrity-and-concurrency.md) | QCC-DATA | Normative (non-functional) |
| [configuration.md](./non-functional/configuration.md) | QCC-CONFIG | Normative (non-functional) |
| [testing-and-acceptance.md](./non-functional/testing-and-acceptance.md) | QCC-NFR / test scenarios | Normative + architectural (non-functional / test strategy) |
| [clarifications.md](./clarifications.md) | CLA | Ambiguity register (non-normative until resolved) |

Planning artifacts ([plan.md](./plan/plan.md), [research.md](./plan/research.md), [data-model.md](./plan/data-model.md), [quickstart.md](./plan/quickstart.md)) live under `plan/`; execution artifacts ([tasks.md](./tasks/tasks.md)) live under `tasks/`; design contracts live under `contracts/`; quality checklists live under `checklists/` — per Constitution Principle XIII (Specification Organization).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Customer purchases and later redeems consultation credit (Priority: P1)

A registered customer buys a quantity of the Quick Consult Credit product through normal checkout. Once the order reaches the qualifying state, the purchased amount is posted to the customer's credit account. Later, an authorized consultation system redeems part of that balance when a consultation is completed.

**Why this priority**: This is the core value loop of the feature; without purchase posting and redemption, no other capability has meaning.

**Independent Test**: Can be fully tested by completing a qualifying order for the credit product and confirming the balance and ledger reflect the purchase, then submitting a valid redemption request and confirming the balance and ledger reflect the debit.

**Acceptance Scenarios**:

1. **Given** a customer with no existing credit account, **When** a qualifying order for 100 units of Quick Consult Credit completes, **Then** the customer's balance is 100, `total_credited` is 100, and exactly one PURCHASE ledger record exists.
2. **Given** a customer with balance 100, **When** an authorized redemption request for 25 is submitted, **Then** the balance becomes 75, `total_debited` is 25, and exactly one REDEEM ledger record exists.

*(See [credit-purchase-posting.md](./functional/credit-purchase-posting.md) and [credit-redemption.md](./functional/credit-redemption.md) for normative detail.)*

---

### User Story 2 - Customer reviews balance and history (Priority: P2)

A logged-in customer opens the "Quick Consult Credit" section of their account and sees their current balance and a paginated transaction history, and cannot see any other customer's data.

**Why this priority**: Transparency of balance/history is required for customer trust and support-ticket reduction, but the feature has value even before this view exists (redemption can already occur via API).

**Independent Test**: Can be fully tested by logging in as a customer, viewing the dashboard, and attempting (and failing) to retrieve another customer's balance/history.

**Acceptance Scenarios**:

1. **Given** a customer with transaction history, **When** they open the Quick Consult Credit dashboard, **Then** the current balance and a paginated, correctly ordered transaction history are displayed.
2. **Given** an authenticated customer, **When** they attempt to request another customer's balance or history, **Then** the request is denied and no data is disclosed.

---

### User Story 3 - Administrator manually adjusts a customer's credit (Priority: P3)

An authorized administrator opens a customer's record in the Magento Admin, reviews the credit ledger, and adds or removes credit with a mandatory reason, which is recorded against the administrator's identity.

**Why this priority**: Manual adjustment is an operational/support capability that depends on the account and ledger already existing, so it is valuable but not blocking for the initial purchase/redeem loop.

**Independent Test**: Can be fully tested by performing an authorized Add Credit and Remove Credit action and confirming the resulting balance, ledger entries, and recorded administrator identity/reason.

**Acceptance Scenarios**:

1. **Given** a customer with balance 75, **When** an authorized administrator adds 20 with a reason, **Then** the balance becomes 95 and an ADMIN_ADD ledger record stores the administrator identity and reason.
2. **Given** a customer with balance 95, **When** an authorized administrator attempts to remove 200 (exceeding balance), **Then** the request is rejected, the balance remains 95, and no ledger record is created.

---

### Edge Cases

- What happens when a redemption request and an admin removal are submitted concurrently against the same account? (See [data-integrity-and-concurrency.md](./non-functional/data-integrity-and-concurrency.md).)
- How does the system handle a duplicate/replayed purchase-posting event for the same order item? (See [credit-purchase-posting.md](./functional/credit-purchase-posting.md).)
- How does the system handle a duplicate/replayed API transaction request? (See [credit-rest-api.md](./functional/credit-rest-api.md), [clarifications.md](./clarifications.md) CLA-004.)
- What happens if an order that already posted credit is later cancelled or refunded? (See [clarifications.md](./clarifications.md) CLA-009 — explicitly out of scope for automatic reversal in v1.)

## Requirements *(mandatory)*

Detailed, individually identified functional requirements are defined in the sub-specifications listed in [Specification Index](#specification-index). At a cross-cutting level, the feature:

- **FR-000-a**: The system MUST represent Quick Consult Credit as a customer-owned account with exactly one current balance per customer (see [customer-credit-account.md](./functional/customer-credit-account.md)).
- **FR-000-b**: The system MUST record every balance-changing operation as an immutable, append-only ledger entry (see [credit-ledger.md](./functional/credit-ledger.md)).
- **FR-000-c**: The system MUST route every balance-changing operation through a single defined business/service-contract boundary; presentation, API, and integration layers MUST NOT directly modify credit persistence state (see [data-integrity-and-concurrency.md](./non-functional/data-integrity-and-concurrency.md)).
- **FR-000-d**: The system MUST prevent the balance from ever becoming negative (see [data-integrity-and-concurrency.md](./non-functional/data-integrity-and-concurrency.md)).
- **FR-000-e**: The system MUST protect purchase posting from duplicate/replayed processing (see [credit-purchase-posting.md](./functional/credit-purchase-posting.md)).
- **FR-000-f**: The system MUST NOT accept a create-transaction request with a malformed or missing required field, or a non-integer/fractional `amount`, distinct from the `amount ≤ 0` case (see [credit-rest-api.md](./functional/credit-rest-api.md) QCC-API-019). The create-transaction (redemption) REST API does **not** implement a dedicated replay/idempotency mechanism; a replayed or retried request is validated independently and MAY produce an additional accepted transaction if it individually passes validation — an explicit, resolved scope decision that intentionally narrows the general duplicate-prevention expectation to purchase posting only (see [credit-redemption.md](./functional/credit-redemption.md) QCC-REDEEM-010, [clarifications.md](./clarifications.md) CLA-004).

### Key Entities *(include if feature involves data)*

- **Customer Credit Account**: One per Magento customer; holds current balance, lifetime credited total, lifetime debited total. See [customer-credit-account.md](./functional/customer-credit-account.md).
- **Credit Transaction (Ledger Entry)**: Immutable record of a single balance movement (PURCHASE, REDEEM, ADMIN_ADD, ADMIN_REMOVE), including balance-before/after, message, source, and creator. See [credit-ledger.md](./functional/credit-ledger.md).
- **Quick Consult Credit Product**: A Magento product in the Consultation Services attribute set whose quantity purchased determines the qualifying credit amount. See [product-configuration.md](./functional/product-configuration.md).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of qualifying purchases result in exactly one balance credit and exactly one corresponding PURCHASE ledger entry, with zero instances of duplicate posting for the same purchase reference.
- **SC-002**: 100% of accepted redemption, admin-add, and admin-remove operations produce a balance that is arithmetically consistent with its ledger entry (`balance_after = balance_before ± amount`), with zero exceptions across audit sampling.
- **SC-003**: 0% of observed account states include a negative balance, under both sequential and concurrent redemption load.
- **SC-004**: Customers can locate their current balance and most recent transaction within two navigation actions from account login.
- **SC-005**: 100% of cross-customer data-access attempts (customer A requesting customer B's balance/history) are denied with no data disclosed.
- **SC-006**: 100% of admin add/remove actions without a supplied reason are rejected before any balance or ledger change occurs.

## Assumptions

- The store has a functional payment gateway and order-processing flow already in place (SRS §2.5).
- Customer accounts are mandatory for purchasing Quick Consult Credit; guest checkout is not supported for this product (SRS §2.5, §3.1).
- The qualifying "successful order/payment" condition is deployment-configurable rather than a single hardcoded order state (Technical Architecture §3, §10; see [configuration.md](./non-functional/configuration.md)).
- The Consultation Services attribute set already exists or will be provisioned as part of deployment configuration, not as a runtime feature of this specification (Technical Architecture §19).
- One unit of purchased quantity corresponds to one whole-number unit of credit (a "credit point"); credit amounts are not price-derived and are never fractional (resolved 2026-09-15, see [Clarifications](#clarifications)).
- Redemption, admin add, and admin remove are performed by authorized external integration systems or Magento admins only; there is no direct customer-self-redemption API path, and no anonymous or guest path to any balance-changing operation (resolved 2026-09-15, see [Clarifications](#clarifications)).

## Out of Scope

The following are explicitly excluded from this specification set and MUST NOT be treated as implicit acceptance criteria in any sub-specification (SRS §8.1; Technical Architecture §2, §26):

- Credit expiry dates or expiry policies.
- Automatic refund calculation or credit reversal when an order is cancelled or refunded after credit has already been posted.
- Scheduled or recurring credit grants/subscriptions.
- Promotional, loyalty, or free-of-charge credit campaigns.
- Peer-to-peer credit transfers between customers.
- Split payments combining credit with standard payment methods during checkout.
- Real-time credit-utilization email/SMS notifications.
- Advanced merchant analytics or business-reporting dashboards beyond the ledger itself.

Any future work in these areas requires a new, explicitly approved specification and is not authorized by this document.

## Notes on Source Conflicts

Where the SRS and Technical Architecture disagreed, this specification set did not silently resolve the conflict. All source-derived conflicts and gaps identified during specification decomposition have now been resolved through four clarification passes and are recorded in [clarifications.md](./clarifications.md) (20 of 20 items resolved):

- **2026-09-15 session** (5 items): credit-unit semantics, decimal precision, qualifying order state default, customer self-redemption authorization, and REST API path.
- **2026-09-16 clarify session** (8 items plus 2 newly identified items): API idempotency mechanism (CLA-004, resolved as "no dedicated mechanism" — an explicit, documented narrowing of SRS §7.2's general duplicate-prevention expectation for the create-transaction endpoint only; purchase-posting idempotency is unaffected), transaction-type/direction casing (CLA-006), extension-attribute necessity (CLA-008), post-cancellation manual-correction acceptance (CLA-009), purchase-reference granularity (CLA-010), admin adjustments vs. lifetime totals (CLA-011), performance target (CLA-012, resolved as "no formal SLA required"), regulatory jurisdiction/retention (CLA-013, resolved as "no specific regulation; retain indefinitely"), API rate limiting (CLA-014, newly identified, resolved as "platform defaults only"), and V1 API versioning policy (CLA-015, newly identified, resolved as "V1 frozen, breaking changes require a new version").
- **2026-09-16 analyze-follow-up sessions** (4 items, identified during `/speckit-analyze` findings C2/H2/H3/H4): create-transaction `transaction_type` scope (CLA-016, resolved as "REDEEM only"), standardized error-payload scope for 401 vs. 403 (CLA-017), malformed/out-of-range `customerId` handling (CLA-018), and fixed create-transaction success status/field names (CLA-019).
- **2026-09-16 analyze-follow-up session** (1 item, identified during a subsequent `/speckit-analyze` pass as finding I1): missing/malformed required create-transaction fields and non-integer `amount` values, distinct from the `amount ≤ 0` case (CLA-020, resolved as a new `INVALID_REQUEST` validation error — see QCC-API-019).

No unresolved ambiguity remains in [clarifications.md](./clarifications.md) as of this specification's v1.4.
