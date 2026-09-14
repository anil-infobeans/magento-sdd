# Specification: Non-Functional Requirements — Performance, Compliance, Testing & Out-of-Scope

## Metadata

- **Specification name**: Non-Functional Requirements — Performance, Compliance, Testing & Out-of-Scope
- **Specification identifier**: `QCC-PERF` / `QCC-COMPLY` / `QCC-TEST` / `QCC-SCOPE`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §7.2, §8.1
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §19, §23, §24, §26
- **Dependencies**: All other specification files in this set
- **Impacted existing specifications**: None found

## Purpose

Capture cross-cutting non-functional requirements — performance targets, regulatory/compliance considerations, testing strategy, and preserved out-of-scope boundaries — that apply across the whole Quick Consult Credit capability.

## Scope

**In scope**: Performance acceptance targets (proposed), regulatory/compliance considerations, testing-layer coverage expectations, explicit preservation of SRS out-of-scope items.

**Out of scope**: Functional business rules (covered elsewhere).

**Actors**: All actors defined across the specification set.

## Performance Requirements

The SRS and Technical Architecture do not state numeric performance SLAs. The following targets were proposed based on standard web/API responsiveness expectations and were **formally approved as binding acceptance criteria on 2026-09-12** via `/speckit-clarify` (see [spec.md](./spec.md#clarifications)).

- **QCC-PERF-001**: Customer balance retrieval (API and dashboard) SHALL return within 500ms at the 95th percentile under nominal load. *(Basis: standard web/API responsiveness expectation; approved 2026-09-12.)*
- **QCC-PERF-002**: Transaction-history retrieval for a single page SHALL return within 500ms at the 95th percentile under nominal load. *(Same basis; approved 2026-09-12.)*
- **QCC-PERF-003**: A redemption transaction (validate, lock, persist, respond) SHALL complete within 1 second at the 95th percentile under nominal load. *(Same basis; approved 2026-09-12.)*
- **QCC-PERF-004**: Admin balance/history view SHALL render within 1 second at the 95th percentile under nominal admin load. *(Same basis; approved 2026-09-12.)*
- **QCC-PERF-005**: THE SYSTEM SHALL preserve correctness (no negative balance, no duplicate debit) under concurrent redemption load regardless of throughput; correctness is not conditional on performance targets. *(Source: Architecture §16, §24 — correctness requirement is unconditional, unlike the numeric targets above.)*
- **QCC-PERF-006**: THE SYSTEM SHALL use a default customer transaction-history page size of 20, configurable per project standard. *(Source: Architecture §19 "Customer history page size — 20 or project standard")*

AMB-007 in [ambiguity-register.md](./ambiguity-register.md) is resolved; QCC-PERF-001–004 are now binding, testable acceptance criteria rather than proposed assumptions.

## Regulatory / Compliance Considerations

Quick Consult Credit behaves as a financial-like prepaid credit capability from an audit and customer-trust perspective, even though the SRS does not identify a specific regulatory regime. The following considerations are identified without asserting legal compliance:

- **QCC-COMPLY-001**: THE SYSTEM SHALL support organizational retention and privacy policies applicable to customer financial-like transaction records; the specific retention period is **TBD / business or compliance owner decision** (see [ambiguity-register.md](./ambiguity-register.md) AMB-009). *(Source: neutral compliance framing per task instructions; no specific regulation identified in SRS/Architecture.)*
- **QCC-COMPLY-002**: THE SYSTEM SHALL restrict access to customer financial-like transaction data to the authenticated owning customer and authorized administrators/integrations, per [security.md](./security.md). *(Source: SRS §5.1, §7.1.)*
- **QCC-COMPLY-003**: THE SYSTEM SHALL maintain administrator accountability for every manual adjustment via mandatory reason capture and identity recording, per [admin.md](./admin.md). *(Source: SRS §5.2, §7.1.)*
- **QCC-COMPLY-004**: THE SYSTEM SHALL maintain data integrity and traceability of every balance movement via the immutable ledger, per [ledger.md](./ledger.md). *(Source: SRS §6.1.)*
- **QCC-COMPLY-005**: Payment/order lifecycle controls governing when credit is granted (see [purchase.md](./purchase.md)) SHALL remain aligned with the store's existing payment/order compliance controls; no new payment-compliance obligation is introduced by this feature beyond what Magento's core order/payment lifecycle already provides. *(Source: SRS §2.5 assumptions.)*
- **QCC-COMPLY-006**: A specific jurisdictional, tax, or financial-services regulatory obligation is **not identified** in the SRS or Technical Architecture and SHALL NOT be assumed; any such obligation is **TBD / business or compliance owner decision** (see [ambiguity-register.md](./ambiguity-register.md) AMB-010).

## Testing Strategy

- **QCC-TEST-001**: THE SYSTEM implementation SHALL be covered by unit tests for validators, transaction-type rules, balance calculations, idempotency decisions, and mapping logic. *(Source: Architecture §23 "Unit")*
- **QCC-TEST-002**: THE SYSTEM implementation SHALL be covered by integration tests for database transaction behavior, row locking, and ledger+balance atomicity. *(Source: Architecture §23 "Integration")*
- **QCC-TEST-003**: THE SYSTEM implementation SHALL be covered by API tests for authentication, authorization, payload validation, success/error responses, and duplicate-request behavior. *(Source: Architecture §23 "API")*
- **QCC-TEST-004**: THE SYSTEM implementation SHALL be covered by Magento functional tests verifying that a successful purchase posts correct credit, that failed/cancelled/non-qualifying orders do not post credit, and that the customer dashboard and admin adjustments behave as specified. *(Source: Architecture §23 "Magento functional"; §24)*
- **QCC-TEST-005**: THE SYSTEM implementation SHALL be covered by concurrency tests verifying no negative balance and no duplicate debit under parallel redemption load. *(Source: Architecture §23 "Concurrency")*
- **QCC-TEST-006**: THE SYSTEM implementation SHALL be covered by regression tests spanning checkout, customer account, admin customer edit, order processing, and cron/queue behavior. *(Source: Architecture §23 "Regression")*
- **QCC-TEST-007**: THE SYSTEM implementation SHALL be verified through end-to-end regression, performance, and security penetration testing prior to release. *(Source: SRS §8.1 "QA" appendix.)*
- **QCC-TEST-008**: The following 20 acceptance scenarios SHALL be represented in the automated or manual test suite at minimum: (1) purchase $100 successfully; (2) redeem $25 from $100; (3) attempt to redeem $120 from $100; (4) admin adds $20 to a $75 balance; (5) admin removes $30 from a $95 balance; (6) admin attempts to remove more than the current balance; (7) duplicate purchase event; (8) duplicate API request with the same idempotency key; (9) two concurrent $80 redeems against a $100 balance; (10) Customer A attempts to access Customer B's balance/history; (11) unauthorized API request; (12) unknown customer; (13) customer without an existing credit account; (14) invalid/zero/negative amount; (15) failed persistence during a balance-changing operation; (16) failed/cancelled/non-qualifying order must not grant credit; (17) transaction history pagination; (18) audit record contains required actor/reference information; (19) existing ledger entries remain immutable; (20) currency mismatch behavior. *(Source: user-specified acceptance-criteria checklist; each scenario is also represented in its owning domain specification's Acceptance Criteria section.)*

## Out-of-Scope Preservation

The following capabilities are explicitly out of scope for this release and SHALL NOT be introduced as acceptance criteria in any Quick Consult Credit specification:

- **QCC-SCOPE-001**: Credit expiry dates/policies. *(Source: SRS §8.1; Architecture §2.)*
- **QCC-SCOPE-002**: Automated refund calculations or credit reversals when an order is cancelled/refunded. *(Source: SRS §8.1; Architecture §2, §26 "Future Extension Points.")*
- **QCC-SCOPE-003**: Scheduled or recurring credit subscriptions. *(Source: SRS §8.1; Architecture §2.)*
- **QCC-SCOPE-004**: Promotional, loyalty, or free-of-charge credit campaigns. *(Source: SRS §8.1; Architecture §2.)*
- **QCC-SCOPE-005**: Peer-to-peer credit transfers between customers. *(Source: SRS §8.1.)*
- **QCC-SCOPE-006**: Split payments combining credit and standard payment methods during checkout. *(Source: SRS §8.1; Architecture §2.)*
- **QCC-SCOPE-007**: Real-time credit-utilization email/SMS notifications. *(Source: SRS §8.1.)*
- **QCC-SCOPE-008**: Advanced merchant analytics/reporting beyond the ledger-derived operational metrics in [audit-observability.md](./audit-observability.md). *(Source: SRS §8.1.)*
- **QCC-SCOPE-009**: Multi-currency conversion between a transaction's currency and the account currency. *(Source: Architecture §3, §26; see [account.md](./account.md) QCC-CURR-005.)*

## Acceptance Criteria

1. **Given** the approved performance targets QCC-PERF-001–004, **When** performance tests are executed under nominal load, **Then** each measured operation meets its approved 95th-percentile target. *(Validates QCC-PERF-001–004.)*
2. **Given** the full test suite, **When** executed, **Then** all 20 scenarios in QCC-TEST-008 pass. *(Validates QCC-TEST-008.)*
3. **Given** the specification baseline, **When** reviewed for scope creep, **Then** none of the nine out-of-scope items in QCC-SCOPE-001…009 appear as an implemented acceptance criterion anywhere in `/specs/001-quick-consult-credit/`. *(Validates QCC-SCOPE-001…009.)*

## Traceability

See [traceability.md](./traceability.md). Contributes `QCC-PERF-001`…`QCC-PERF-006`, `QCC-COMPLY-001`…`QCC-COMPLY-006`, `QCC-TEST-001`…`QCC-TEST-008`, `QCC-SCOPE-001`…`QCC-SCOPE-009`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability) |
