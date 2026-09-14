# Feature Specification: Quick Consult Credit

**Feature Branch**: `001-quick-consult-credit`

**Created**: 2026-09-12

**Status**: Draft

**Input**: User description: "Convert Quick Consult Credit SRS v1.0 and its supporting Technical Architecture into a complete, implementation-ready set of specifications under /specs."

**Source SRS**: Quick Consult Credit — Customer Credit Management and Redemption, v1.0 (docs/Quick_Consult_Credit_SRS_v1.0.docx)

**Source Architecture**: Quick Consult Credit Technical Architecture, Draft/Implementation Baseline, dated 10 September 2026 (docs/Quick_Consult_Credit_Technical_Architecture.docx, docs/Quick_Consult_Credit_Architecture.png)

**Specification set**: This is the overview specification for the Quick Consult Credit capability. Detailed, independently testable requirements are decomposed into the sibling specification files listed in [Specification Index](#specification-index). This document defines the overall user scenarios, cross-cutting success criteria, and assumptions; it does not repeat every functional requirement already stated in the sub-specifications.

## Specification Index

| # | File | Covers |
|---|------|--------|
| 1 | [product.md](./product.md) | Credit product, attribute set, denomination configuration |
| 2 | [account.md](./account.md) | Customer credit account, balance, currency and monetary precision |
| 3 | [ledger.md](./ledger.md) | Immutable append-only transaction ledger |
| 4 | [purchase.md](./purchase.md) | Purchase-to-credit posting lifecycle |
| 5 | [redemption.md](./redemption.md) | Credit redemption/debit business operation |
| 6 | [api.md](./api.md) | External REST API contracts (balance retrieval, transactions) |
| 7 | [customer-dashboard.md](./customer-dashboard.md) | Customer "My Account" Quick Consult Credit dashboard |
| 8 | [admin.md](./admin.md) | Admin customer credit management |
| 9 | [security.md](./security.md) | Authentication, authorization, ACL |
| 10 | [idempotency-concurrency.md](./idempotency-concurrency.md) | Duplicate prevention, atomicity, concurrency |
| 11 | [audit-observability.md](./audit-observability.md) | Auditability, logging, operational metrics |
| 12 | [non-functional.md](./non-functional.md) | Performance, regulatory/compliance, out-of-scope, testing strategy |
| 13 | [traceability.md](./traceability.md) | Full requirement traceability matrix |
| 14 | [ambiguity-register.md](./ambiguity-register.md) | Unresolved ambiguities / TBD register |
| 15 | [change-summary.md](./change-summary.md) | Specification change summary and validation report |

## Clarifications

### Session 2026-09-12

- Q: What Magento order/payment condition should trigger Quick Consult Credit purchase posting? → A: An invoice has been generated for the order AND the order status is `complete` (non-virtual orders) or `processing` (virtual orders, which do not transition through a shipment step to reach `complete`).
- Q: Which transaction types may an external (non-admin) integration submit via the credit-transaction API? → A: `REDEEM` only; `PURCHASE` remains system-initiated only (see [purchase.md](./purchase.md)), and `ADMIN_ADD`/`ADMIN_REMOVE` remain admin-only (see [admin.md](./admin.md)).
- Q: When an unauthenticated/unauthorized caller requests a customer's balance or history for a customer that doesn't actually exist, should the response reveal that the customer doesn't exist, or give a uniform denial? → A: Authorization is checked first; if the caller isn't authorized for the target customer, the system returns a uniform unauthorized/denied response regardless of whether that customer exists. `CUSTOMER_NOT_FOUND` is returned only once the caller's authorization for the target customer has already been established.
- Q: Should the proposed performance targets (balance retrieval, history retrieval, redemption, admin view) be approved now as binding targets? → A: Yes, approved as-is: 500ms p95 for balance retrieval and transaction-history retrieval; 1 second p95 for redemption transactions and admin balance/history view, all under nominal load.
- Q: Does the "Consultation Services" product attribute set already exist in this Magento catalog, or does it need to be created as part of this initiative? → A: Create it if not already available, derived from Magento's Default attribute set.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Customer purchases and later redeems consultation credit (Priority: P1)

A registered store customer purchases a Quick Consult Credit denomination through standard Magento checkout so that the value becomes available in their account, and it is later redeemed (via an authorized external consultation system) when they use a paid consultation service.

**Why this priority**: This is the core value proposition of the feature — without reliable purchase-to-balance posting and redemption, no other capability (dashboard, admin, API) has anything to operate on.

**Independent Test**: Can be fully tested by completing a qualifying order for a $100 denomination, confirming the balance becomes $100 with one PURCHASE ledger row, then submitting a $25 redemption via the transaction API and confirming the balance becomes $75 with one REDEEM ledger row.

**Acceptance Scenarios**:

1. **Given** a customer with no existing credit account, **When** they complete a qualifying order (payment/order condition satisfied) for a $100 Quick Consult Credit denomination, **Then** their balance becomes $100.00, `total_credited` becomes $100.00, and exactly one immutable PURCHASE ledger transaction is created referencing the order.
2. **Given** a customer with a $100.00 balance, **When** an authorized caller submits a valid redemption request for $25.00, **Then** the balance becomes $75.00, `total_debited` increases by $25.00, and exactly one immutable REDEEM ledger transaction is created.
3. **Given** a customer with a $100.00 balance, **When** a redemption request for $120.00 is submitted, **Then** the request is rejected with an insufficient-balance error, the balance remains $100.00, and no debit ledger transaction is created.

---

### User Story 2 - Customer reviews their credit balance and history (Priority: P2)

A registered customer views their available Quick Consult Credit balance and full transaction history from their Magento "My Account" area, and cannot see any other customer's credit information.

**Why this priority**: Transparency of balance and history is required for customer trust and support-ticket deflection, and is independently valuable once an account/ledger exists, regardless of admin tooling.

**Independent Test**: Can be fully tested by logging in as a customer with existing PURCHASE and REDEEM transactions, navigating to the Quick Consult Credit dashboard, and confirming the displayed balance and paginated history match the ledger; then confirming that a request for another customer's data is denied.

**Acceptance Scenarios**:

1. **Given** an authenticated customer with a $75.00 balance and two ledger transactions, **When** they open the Quick Consult Credit dashboard, **Then** the current balance and both transactions (date, type, amount, resulting balance, message/reference) are displayed, most recent first.
2. **Given** authenticated Customer A, **When** Customer A attempts to retrieve Customer B's balance or history by any means (URL manipulation, API call, client-supplied identifier), **Then** the request is denied and no data belonging to Customer B is disclosed.
3. **Given** a customer with more transactions than one page size, **When** they page through history, **Then** each page returns a bounded, deterministic subset of transactions without omission or duplication.

---

### User Story 3 - Administrator manages a customer's credit balance (Priority: P3)

An authorized Magento administrator reviews a customer's Quick Consult Credit balance, lifetime totals, and full transaction history from the customer edit screen, and can add or remove credit with a mandatory reason, recorded against the administrator's identity.

**Why this priority**: Manual adjustment and support-driven correction is a required operational capability, but depends on the account/ledger/security foundation already being in place (P1/P2), so it is appropriately sequenced after them.

**Independent Test**: Can be fully tested by an authorized administrator opening a customer record, adding $20 credit with a reason, confirming the balance and ledger update, then attempting to remove more credit than is available and confirming rejection.

**Acceptance Scenarios**:

1. **Given** a customer with a $75.00 balance, **When** an authorized administrator adds $20.00 with a reason, **Then** the balance becomes $95.00, an ADMIN_ADD ledger transaction is created recording the administrator's identity and reason, and the operation is atomic.
2. **Given** a customer with a $95.00 balance, **When** an authorized administrator attempts to remove $150.00, **Then** the request is rejected, the balance remains $95.00, and no ADMIN_REMOVE ledger transaction is created.
3. **Given** a user without the Quick Consult Credit management ACL resource, **When** that user attempts to add or remove credit, **Then** the action is denied regardless of UI visibility.

### Edge Cases

- What happens when a purchase-qualifying order event is delivered more than once (retry, requeue, duplicate webhook/observer firing)? See [purchase.md](./purchase.md) (QCC-PURCHASE-004) and [idempotency-concurrency.md](./idempotency-concurrency.md) (QCC-IDEMP-001).
- How does the system handle two concurrent redemption requests that would each individually succeed but together would overdraw the balance? See [idempotency-concurrency.md](./idempotency-concurrency.md) (QCC-CONC-003).
- How does the system handle a redemption/API request replayed with the same idempotency key? See [idempotency-concurrency.md](./idempotency-concurrency.md) (QCC-IDEMP-004).
- What happens when a persistence failure occurs after the balance is calculated but before the ledger row and balance update are committed? See [idempotency-concurrency.md](./idempotency-concurrency.md) (QCC-CONC-004).
- How does the system behave when a customer has no credit account yet (never purchased credit)? See [account.md](./account.md) (QCC-ACCOUNT-004) and [api.md](./api.md) (QCC-API-004).
- What happens when an order is cancelled, refunded, or never reaches the qualifying state after credit was expected? See [purchase.md](./purchase.md) (QCC-PURCHASE-005).
- What happens when a transaction request's currency does not match the account's currency? See [account.md](./account.md) (QCC-CURR-004).

## Requirements *(mandatory)*

Functional requirements are decomposed by domain across the sibling specification files listed in the [Specification Index](#specification-index). Each requirement carries a unique identifier (`QCC-<DOMAIN>-<NNN>`) and is traceable to its SRS/Architecture source in [traceability.md](./traceability.md).

### Cross-Cutting Requirements

- **QCC-CROSS-001**: THE SYSTEM SHALL implement all Quick Consult Credit business logic behind Magento service contracts (interfaces) such that customer UI, admin UI, REST API, and purchase-posting integration invoke the same rules, per the architecture's "Service Contracts & Extension Attributes" decision (Technical Architecture §6.2, §8).
- **QCC-CROSS-002**: THE SYSTEM SHALL treat the immutable transaction ledger as the audit source of truth and the customer credit account balance as materialized current state derived from that ledger (Technical Architecture §3, §6).
- **QCC-CROSS-003**: THE SYSTEM SHALL NOT require manual SQL statements for routine credit operations; any data-correction outside normal service contracts shall be represented as an explicit compensating ledger transaction (Technical Architecture §25).

### Key Entities

- **Customer Credit Account**: Represents one customer's current Quick Consult Credit balance and lifetime statistics. See [account.md](./account.md).
- **Credit Transaction (Ledger Entry)**: An immutable record of a single balance movement. See [ledger.md](./ledger.md).
- **Quick Consult Credit Product**: The Magento saleable product/denomination that, when purchased and qualified, results in a credit transaction. See [product.md](./product.md).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of qualifying Quick Consult Credit purchases result in exactly one corresponding PURCHASE ledger transaction and correct balance increase, verified through functional and concurrency test suites (see [purchase.md](./purchase.md), [non-functional.md](./non-functional.md)).
- **SC-002**: 0% of redemption attempts are able to reduce any customer's balance below zero, under both sequential and concurrent load, verified through concurrency tests (see [idempotency-concurrency.md](./idempotency-concurrency.md)).
- **SC-003**: 100% of duplicate purchase events and 100% of duplicate API requests (same idempotency key) result in no additional balance movement (see [idempotency-concurrency.md](./idempotency-concurrency.md)).
- **SC-004**: 100% of attempts by one customer to view another customer's balance or transaction history are denied, verified through authorization tests (see [customer-dashboard.md](./customer-dashboard.md), [security.md](./security.md)).
- **SC-005**: 100% of successful balance-changing operations (purchase, redeem, admin add, admin remove) produce a ledger entry containing the actor/source identity, timestamp, and reference, verified through audit tests (see [audit-observability.md](./audit-observability.md)).
- **SC-006**: Customers can retrieve their current balance within 500ms and their first page of transaction history within 500ms, at the 95th percentile under nominal load; redemption transactions complete within 1 second at the 95th percentile; admin balance/history views render within 1 second at the 95th percentile (approved 2026-09-12; see [non-functional.md](./non-functional.md) QCC-PERF-001…004).

## Assumptions

- Guest checkout does not apply to this product; a Magento customer account is required to own a credit account (SRS §2.5).
- The store operates in a single configured currency per credit account; multi-currency conversion is out of scope for this release (Technical Architecture §3, §26).
- The "qualifying successful order/payment condition" that triggers purchase posting is: an invoice generated for the order AND the order status is `complete` (non-virtual orders) or `processing` (virtual orders) — resolved 2026-09-12 (see [Clarifications](#clarifications) and [purchase.md](./purchase.md) QCC-PURCHASE-001/003). This condition remains configurable per deployment (Technical Architecture §10, §19) but the default is now definitive rather than illustrative.
- External integrations authenticate using Magento's native Web API security mechanisms (integration tokens, admin tokens, or customer tokens) rather than a custom authentication scheme (SRS §7.1, Technical Architecture §14).
- Numeric performance targets are not stated in the SRS or Technical Architecture; the targets in [non-functional.md](./non-functional.md) QCC-PERF-001–004 were proposed based on standard web/API responsiveness expectations and were formally approved as binding acceptance criteria on 2026-09-12 (see [Clarifications](#clarifications)).
