# Specification: Credit Purchase Posting

**Specification**: quick-consult-credit / credit-purchase-posting
**Version**: 1.1
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-PURCHASE

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §3.3.1; Technical Architecture §10, §24, §28 | New specification |
| 1.1 | Confirmed quantity-based credit amount in QCC-PURCHASE-002 (removed open-ambiguity caveat) | /speckit-clarify session 2026-09-15 (CLA-001) | Removes reference to an unresolved clarification; behavior unchanged |

## Purpose

Defines the state transition by which a qualifying Quick Consult Credit purchase becomes a posted credit on the customer's account, including required negative-case behavior and idempotency guarantees.

## Purchase Flow (informative)

```
Qualifying purchase
  -> identify customer
  -> identify qualifying credit quantity
  -> determine credit amount
  -> verify purchase has not already been posted
  -> credit account
  -> create PURCHASE ledger record
  -> commit atomically
```

## Requirements

### QCC-PURCHASE-001 — Posting occurs exactly once per purchase reference

**Statement (EARS)**: When an order item qualifies for credit posting, the system shall post credit for that purchase reference exactly once.

**Source**: SRS §3.3.1; Technical Architecture §10

**Acceptance Criteria**:
- AC-1: Given a qualifying order item that has not yet been posted, when the qualifying-state event is processed, then exactly one PURCHASE ledger entry is created for that order item.
- AC-2: Given a qualifying order item that has already been posted, when the qualifying-state event is processed again for the same reference, then no additional ledger entry is created and the balance is unchanged.

### QCC-PURCHASE-002 — Credit amount determined from qualifying quantity

**Statement (EARS)**: The system shall determine the posted credit amount for a qualifying order item from that item's purchased quantity, where one unit of quantity equals one whole-number credit point.

**Source**: SRS §3.1; Technical Architecture §10; Resolved clarification (see [clarifications.md](./clarifications.md) CLA-001, resolved 2026-09-15)

**Acceptance Criteria**:
- AC-1: Given a qualifying order item with quantity Q, when credit is posted, then the PURCHASE ledger entry's amount equals Q credit points (a whole number, not a price-derived monetary value).

### QCC-PURCHASE-003 — No posting on cart addition

**Statement (EARS)**: The system shall not post credit when Quick Consult Credit is added to a cart.

**Source**: SRS §3.3.1 (Note)

**Acceptance Criteria**:
- AC-1: Given Quick Consult Credit added to a cart, when the cart is inspected without checkout completing, then no PURCHASE ledger entry exists and the balance is unchanged.

### QCC-PURCHASE-004 — No posting on quote creation

**Statement (EARS)**: The system shall not post credit upon creation of a quote/cart containing Quick Consult Credit.

**Source**: SRS §3.3.1 (Note); Technical Architecture §10

**Acceptance Criteria**:
- AC-1: Given a quote containing Quick Consult Credit, when the quote exists without an associated qualifying order, then no PURCHASE ledger entry exists.

### QCC-PURCHASE-005 — No posting on pending order

**Statement (EARS)**: The system shall not post credit while an order containing Quick Consult Credit remains in a pending (non-qualifying) state.

**Source**: SRS §3.3.1 (Note)

**Acceptance Criteria**:
- AC-1: Given an order in a pending state, when the order is inspected, then no PURCHASE ledger entry exists for its Quick Consult Credit item and the balance is unchanged.

### QCC-PURCHASE-006 — No posting on failed payment

**Statement (EARS)**: The system shall not post credit for an order item whose payment has failed.

**Source**: SRS §3.3.1

**Acceptance Criteria**:
- AC-1: Given an order whose payment fails, when the failure is recorded, then no PURCHASE ledger entry is created for that order's Quick Consult Credit item.

### QCC-PURCHASE-007 — No posting on cancelled order

**Statement (EARS)**: The system shall not post credit for an order item belonging to a cancelled order.

**Source**: SRS §3.3.1

**Acceptance Criteria**:
- AC-1: Given a cancelled order containing Quick Consult Credit that never reached the qualifying state, when the order is inspected, then no PURCHASE ledger entry exists.

### QCC-PURCHASE-008 — No posting for non-qualifying order state

**Statement (EARS)**: The system shall not post credit for an order item whose order is in any state other than the deployment-configured qualifying state.

**Source**: Technical Architecture §3, §10; [product-configuration.md](./product-configuration.md) QCC-PROD-008

**Acceptance Criteria**:
- AC-1: Given an order in a state other than the configured qualifying state, when evaluated for posting, then posting does not occur.

### QCC-PURCHASE-009 — Repeated order-processing event does not duplicate posting

**Statement (EARS)**: When the qualifying-state event for an already-posted order item is raised again (e.g., due to retry, redelivery, or reprocessing), the system shall not create an additional PURCHASE ledger entry or increase the balance again.

**Source**: SRS §7.2 (idempotency); Technical Architecture §10, §28 (risk: "Purchase event fires more than once")

**Acceptance Criteria**:
- AC-1: Given a qualifying-state event has already resulted in a posted PURCHASE entry for a reference, when the same event is processed again, then the balance and ledger remain exactly as they were after the first successful posting.

### QCC-PURCHASE-010 — Repeated processing of the same purchase reference

**Statement (EARS)**: The system shall treat repeated processing attempts referencing the same purchase reference as a single logical purchase for posting purposes.

**Source**: Technical Architecture §7.2 ("a deterministic source reference ... should also be protected from double posting")

**Acceptance Criteria**:
- AC-1: Given two independent processing attempts carrying the same purchase reference, when both are processed (even concurrently), then only one results in a posted ledger entry.

### QCC-PURCHASE-011 — Idempotent posting via deterministic purchase reference

**Statement (EARS)**: The system shall use a deterministic purchase reference (the qualifying order item) to determine whether a purchase has already been posted, without prescribing the underlying persistence mechanism.

**Source**: Technical Architecture §7.2, §10; Derived clarification (see [clarifications.md](./clarifications.md) CLA-010)

**Acceptance Criteria**:
- AC-1: Given the same order item identifier, when posting is attempted multiple times, then the system consistently identifies prior posting and prevents duplication regardless of timing.

### QCC-PURCHASE-012 — No automatic reversal on later cancellation/refund

**Statement (EARS)**: The system shall not automatically reverse a previously posted PURCHASE credit if the originating order is later cancelled or refunded.

**Source**: SRS §8.1 (out of scope); Technical Architecture §2, §26 (future extension point); [clarifications.md](./clarifications.md) CLA-009

**Acceptance Criteria**:
- AC-1: Given a previously posted PURCHASE credit, when the originating order is subsequently cancelled or refunded, then the customer's balance and ledger are not automatically altered by that cancellation/refund event.

## Test Scenarios (Given/When/Then summary)

| Scenario | Given | When | Then |
|---|---|---|---|
| Successful qualifying purchase | Customer with balance 0 | Order for 100 qty reaches qualifying state | Balance = 100; one PURCHASE entry |
| Quantity-based credit | Order for N qty | Qualifying state reached | Posted amount = N |
| Pending order | Order pending | Inspected before qualifying state | No posting |
| Failed payment | Payment fails | Failure recorded | No posting |
| Cancelled/non-qualifying order | Order cancelled pre-qualification | Inspected | No posting |
| Duplicate purchase processing | Already posted order item | Event reprocessed | No duplicate posting |
| Retry after processing failure | Posting attempt failed mid-operation | Retried | Exactly one successful posting results |

## Related Specifications

- [product-configuration.md](./product-configuration.md) — product- and configuration-level preconditions for a qualifying purchase.
- [credit-ledger.md](./credit-ledger.md) — ledger entry structure created by posting.
- [data-integrity-and-concurrency.md](./data-integrity-and-concurrency.md) — atomicity and duplicate-prevention guarantees.
- [clarifications.md](./clarifications.md) — CLA-001 (resolved), CLA-003 (resolved), CLA-009 (open, non-blocking), CLA-010 (open, non-blocking).
