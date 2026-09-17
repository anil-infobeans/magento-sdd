# Specification: Product Configuration

**Specification**: quick-consult-credit / product-configuration
**Version**: 1.3
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-PROD

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Quick Consult Credit SRS v1.0 §3.1; Technical Architecture §3, §10 | New specification |
| 1.1 | Recorded resolved default qualifying condition (invoice generated / payment captured) in QCC-PROD-008 | /speckit-clarify session 2026-09-15 (CLA-003) | Clarifies default configuration value without removing configurability |
| 1.2 | Relocated to `functional/` and updated cross-reference links | Constitution v1.4.0 Principle XIII, 2026-09-16 | Structural only; no requirement content changed |
| 1.3 | Fixed stale cross-reference: CLA-010 was already resolved 2026-09-16 but this file still read "open" | `/speckit-analyze` finding H1, 2026-09-16 | Cross-reference correction only; no requirement content changed |

## Purpose

Defines how Quick Consult Credit is represented and configured as a purchasable Magento product, and the conditions under which a purchase of that product becomes eligible for credit posting. This specification governs product-level rules only; the act of posting credit is defined in [credit-purchase-posting.md](./credit-purchase-posting.md).

## Requirements

### QCC-PROD-001 — Product representation

**Statement (EARS)**: The system shall represent Quick Consult Credit as a Magento product.

**Source**: SRS §3.1

**Acceptance Criteria**:
- AC-1: Given the Consultation Services attribute set exists, when Quick Consult Credit is configured, then it exists as a single, distinctly identifiable Magento product.

### QCC-PROD-002 — Attribute set assignment

**Statement (EARS)**: The system shall assign the Quick Consult Credit product to the Consultation Services attribute set.

**Source**: SRS §3.1

**Acceptance Criteria**:
- AC-1: Given the Quick Consult Credit product record, when its attribute set is inspected, then it is "Consultation Services".

### QCC-PROD-003 — Quantity-based purchasing

**Statement (EARS)**: The system shall allow customers to purchase Quick Consult Credit using product quantity.

**Source**: SRS §3.1, §2.2

**Acceptance Criteria**:
- AC-1: Given a customer on the product page, when they select a quantity and complete checkout, then the order records the selected quantity for that item.

### QCC-PROD-004 — Guest checkout not supported

**Statement (EARS)**: The system shall not permit guest checkout for the Quick Consult Credit product.

**Source**: SRS §2.5, §3.1

**Acceptance Criteria**:
- AC-1: Given an unauthenticated shopper with Quick Consult Credit in the cart, when they attempt to check out as a guest, then checkout is blocked or the shopper is required to authenticate/register before the order can be placed.

### QCC-PROD-005 — No credit on cart addition

**Statement (EARS)**: The system shall not grant credit when Quick Consult Credit is added to a cart.

**Source**: SRS §3.3.1 (Note)

**Acceptance Criteria**:
- AC-1: Given an authenticated customer, when they add Quick Consult Credit to their cart, then their credit balance is unchanged and no ledger entry is created.

### QCC-PROD-006 — No credit on order creation or pending state

**Statement (EARS)**: The system shall not grant credit merely because an order containing Quick Consult Credit is created or is in a pending state.

**Source**: SRS §3.3.1 (Note); Technical Architecture §10

**Acceptance Criteria**:
- AC-1: Given a newly placed order containing Quick Consult Credit that has not yet reached the qualifying state, when the order is inspected, then the customer's credit balance is unchanged and no PURCHASE ledger entry exists for that order.

### QCC-PROD-007 — Credit posted only after configured qualifying condition

**Statement (EARS)**: When a qualifying order/payment condition for an order containing Quick Consult Credit is met, the system shall post the corresponding credit amount to the purchasing customer's account.

**Source**: SRS §3.1 ("only after successful order and payment completion"); Technical Architecture §3, §10

**Acceptance Criteria**:
- AC-1: Given an order containing Quick Consult Credit reaches the deployment-configured qualifying state, when the qualifying-state event is processed, then credit posting is invoked exactly once for that order/order item.

### QCC-PROD-008 — Qualifying condition is a required, explicit configuration value

**Statement (EARS)**: The system shall require an explicit, deployment-configured value defining the qualifying successful order/payment state. The default value of this configuration setting shall be "invoice generated (payment captured)"; this specification does not otherwise hardcode a single specific Magento order status as the qualifying condition, and deployments may override the default.

**Source**: Technical Architecture §3, §10, §19; Resolved clarification (see [clarifications.md](../clarifications.md) CLA-003, resolved 2026-09-15)

**Rationale**: The SRS gives only an illustrative example ("e.g., invoice generated and order marked complete"); the Architecture explicitly defers this to project-specific configuration. The /speckit-clarify session established "invoice generated (payment captured)" as the default, while preserving deployment-time configurability.

**Acceptance Criteria**:
- AC-1: Given the module configuration, when the qualifying-condition setting is unset or invalid, then the system does not post any credit and records an operational error (see [configuration.md](../non-functional/configuration.md) QCC-CONFIG-003).
- AC-2: Given the qualifying-condition setting is left at its default, when an order's invoice is generated (payment captured), then credit posting proceeds per QCC-PROD-007.
- AC-3: Given the qualifying-condition setting is overridden to a different valid value, when an order reaches that configured state, then credit posting proceeds per QCC-PROD-007 using the overridden state instead.

## Related Specifications

- [credit-purchase-posting.md](./credit-purchase-posting.md) — the posting mechanics, idempotency, and negative-case behavior once a qualifying condition is met.
- [configuration.md](../non-functional/configuration.md) — QCC-CONFIG-002 (attribute-set configuration), QCC-CONFIG-003 (qualifying condition configuration).
- [clarifications.md](../clarifications.md) — CLA-001 (credit unit semantics, resolved), CLA-003 (exact qualifying state, resolved), CLA-010 (purchase reference granularity, resolved).
