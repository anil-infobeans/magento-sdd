# Specification: Credit Purchase Posting

## Metadata

- **Specification name**: Credit Purchase Posting
- **Specification identifier**: `QCC-PURCHASE`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §3.3.1, §3.1
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §10, §19, §22.1
- **Dependencies**: [product.md](./product.md), [account.md](./account.md), [ledger.md](./ledger.md), [idempotency-concurrency.md](./idempotency-concurrency.md)
- **Impacted existing specifications**: None found (assumes existing Magento order/payment lifecycle behavior is unchanged; see [ambiguity-register.md](./ambiguity-register.md) AMB-003 for the exact qualifying order state)

## Purpose

Define the complete, measurable lifecycle by which a Quick Consult Credit purchase becomes a posted credit transaction exactly once, including all negative cases that must NOT result in credit.

## Scope

**In scope**: The qualifying condition concept, the purchase-to-credit posting sequence, exactly-once posting guarantees, and negative-case behavior for cart/quote/pending/failed/cancelled/non-qualifying/duplicate order events.

**Out of scope**: The redemption of posted credit (see [redemption.md](./redemption.md)); the product/denomination configuration itself (see [product.md](./product.md)); general idempotency-key mechanics for externally initiated API writes (see [idempotency-concurrency.md](./idempotency-concurrency.md), which this specification also depends on for the purchase-specific idempotency rule).

**Actors**: Registered customer (purchaser), Magento system/process (order/payment lifecycle, purchase processor).

**System boundaries**: Purchase posting is system-initiated (triggered by Magento's own order/payment lifecycle), not directly invoked by a customer or external API caller.

**External dependencies**: Magento Sales/Checkout order and payment/invoice lifecycle (Architecture §22.1).

## Definitions

- **Qualifying condition**: The configured order/payment state after which a Quick Consult Credit purchase becomes eligible for posting. Resolved (Clarified 2026-09-12): an invoice has been generated for the order AND the order status is `complete` (non-virtual orders) or `processing` (virtual orders, which do not transition through a shipment step to reach `complete`).
- **Purchase reference**: A deterministic identifier (e.g., order item identifier) used to guarantee that a given purchase is posted at most once.
- **Posting**: The act of creating exactly one `PURCHASE` ledger transaction and updating the associated account balance/total-credited amount.

## Actors

- **Registered customer**: Initiates the purchase through cart/checkout; does not directly trigger posting.
- **Magento system/process**: Evaluates the qualifying condition and invokes the posting operation exactly once per qualifying purchase reference.

## Functional Requirements

### Qualifying Condition

- **QCC-PURCHASE-001**: THE SYSTEM SHALL define the qualifying condition for Quick Consult Credit purchase posting as: an invoice has been generated for the order AND the order status is `complete` (non-virtual orders) or `processing` (virtual orders). THE SYSTEM SHALL keep this condition configurable per deployment rather than hard-coded. *(Source: SRS §3.1, §3.3.1; Architecture §19 "Posting condition"; Clarified 2026-09-12)*
- **QCC-PURCHASE-002**: THE SYSTEM SHALL NOT post credit for a Quick Consult Credit line item before the configured qualifying condition is satisfied for the containing order. *(Source: SRS §3.3.1 "Note")*
- **QCC-PURCHASE-003**: WHERE the Quick Consult Credit order is virtual (no physical shipment step), THE SYSTEM SHALL accept order status `processing` — in addition to `complete` — as satisfying the qualifying condition, provided an invoice has already been generated for the order. *(Source: Clarified 2026-09-12; resolves SRS §3.1 example condition)*

### Posting Sequence

- **QCC-PURCHASE-004**: WHEN the qualifying condition is satisfied for an order containing one or more Quick Consult Credit line items, THE SYSTEM SHALL, for each qualifying line item, determine the credit amount as denomination value × purchased quantity (see [product.md](./product.md) QCC-PROD-008), and post exactly one `PURCHASE` ledger transaction per qualifying purchase reference. *(Source: SRS §3.3.1; Architecture §10)*
- **QCC-PURCHASE-005**: THE SYSTEM SHALL record the originating Magento order (and, where the line-item level is the qualifying unit, the order item) as the `reference_type`/`reference_id` on the resulting ledger transaction. *(Source: SRS §3.3.1 step 5 "referencing the Magento Order ID")*
- **QCC-PURCHASE-006**: THE SYSTEM SHALL update the customer's available balance and total credited amount as part of the same atomic operation that creates the `PURCHASE` ledger transaction (see [idempotency-concurrency.md](./idempotency-concurrency.md) QCC-CONC-002). *(Source: Architecture §9.1)*

### Exactly-Once / Idempotent Posting

- **QCC-PURCHASE-007**: THE SYSTEM SHALL treat purchase posting as idempotent with respect to a deterministic purchase reference (e.g., order item identifier), such that a qualifying purchase is posted at most once regardless of how many times the qualifying event is observed. *(Source: Architecture §10, §15)*
- **QCC-PURCHASE-008**: WHEN the qualifying event for a given purchase reference is observed and a `PURCHASE` transaction already exists for that reference, THE SYSTEM SHALL skip posting and SHALL NOT create a second credit transaction or modify the account balance. *(Source: Architecture §10 sequence "Already -> skip")*
- **QCC-PURCHASE-009**: THE SYSTEM SHALL enforce purchase-posting uniqueness using a database-level uniqueness constraint on the deterministic purchase reference, rather than relying solely on an application-level existence check. *(Source: Architecture §15 "Do not rely on application-level check-then-insert without a database uniqueness constraint")*

### Negative Cases

- **QCC-PURCHASE-010**: IF a Quick Consult Credit item exists only in a cart (no order created), THEN THE SYSTEM SHALL NOT post any credit transaction. *(Source: SRS §3.3.1 "Note")*
- **QCC-PURCHASE-011**: IF a quote has been created but no order exists, THEN THE SYSTEM SHALL NOT post any credit transaction. *(Source: SRS §3.3.1 "Note")*
- **QCC-PURCHASE-012**: IF an order containing a Quick Consult Credit item remains in a pending or otherwise non-qualifying status, THEN THE SYSTEM SHALL NOT post any credit transaction for that order. *(Source: SRS §3.1, §3.3.1)*
- **QCC-PURCHASE-013**: IF payment for an order containing a Quick Consult Credit item fails, THEN THE SYSTEM SHALL NOT post any credit transaction for that order. *(Source: SRS §3.1 "after successful order and payment completion")*
- **QCC-PURCHASE-014**: IF an order containing a Quick Consult Credit item is cancelled before the qualifying condition is reached, THEN THE SYSTEM SHALL NOT post any credit transaction for that order. *(Source: SRS §3.1; Architecture §2 scope)*
- **QCC-PURCHASE-015**: WHEN the same qualifying order event is delivered more than once (duplicate event, retry, requeue), THE SYSTEM SHALL post credit at most once for the affected purchase reference, per QCC-PURCHASE-007/008. *(Source: Architecture §24 "Duplicate purchase event -> No second credit is created")*
- **QCC-PURCHASE-016**: IF purchase posting fails partway through (e.g., a persistence failure occurs after balance calculation but before commit), THEN THE SYSTEM SHALL roll back the entire posting operation such that neither a partial ledger entry nor a partial balance change persists. *(Source: Architecture §9.1 "If any step fails, roll back the entire operation"; §17)*

## Acceptance Criteria

1. **Given** a qualifying order for a $100 denomination, **When** an invoice has been generated and the order status is `complete` (non-virtual) or `processing` (virtual), **Then** the customer's balance increases by exactly $100.00 and exactly one `PURCHASE` ledger transaction referencing the order is created. *(Validates QCC-PURCHASE-001, QCC-PURCHASE-003, QCC-PURCHASE-004, QCC-PURCHASE-005, QCC-PURCHASE-006)*
2. **Given** a Quick Consult Credit item only in a shopping cart, **When** the cart/account is inspected, **Then** no credit transaction exists. *(Validates QCC-PURCHASE-010)*
3. **Given** a pending order for a Quick Consult Credit item, **When** the account is inspected, **Then** no credit transaction exists until the qualifying condition is met. *(Validates QCC-PURCHASE-012)*
4. **Given** a failed-payment order for a Quick Consult Credit item, **When** the account is inspected, **Then** no credit transaction exists. *(Validates QCC-PURCHASE-013)*
5. **Given** a cancelled order for a Quick Consult Credit item, **When** the account is inspected, **Then** no credit transaction exists. *(Validates QCC-PURCHASE-014)*
6. **Given** the same qualifying order event delivered twice (e.g., duplicate observer invocation), **When** posting is attempted the second time, **Then** no second `PURCHASE` transaction is created and the balance is unchanged by the duplicate. *(Validates QCC-PURCHASE-007, QCC-PURCHASE-008, QCC-PURCHASE-009, QCC-PURCHASE-015)*
7. **Given** a simulated persistence failure during posting, **When** the operation is retried after the failure, **Then** no partial ledger or balance state exists prior to the retry, and the retry results in exactly one successful posting. *(Validates QCC-PURCHASE-016)*

## Traceability

See [traceability.md](./traceability.md). Contributes `QCC-PURCHASE-001`…`QCC-PURCHASE-016`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability) |
