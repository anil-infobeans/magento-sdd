# Specification: Quick Consult Credit Product & Denomination Configuration

## Metadata

- **Specification name**: Quick Consult Credit Product & Denomination Configuration
- **Specification identifier**: `QCC-PROD`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §3.1, §8.1
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §2, §10, §19
- **Dependencies**: [account.md](./account.md), [purchase.md](./purchase.md)
- **Impacted existing specifications**: None (no existing `/specs` entries found for products, credit, or denomination configuration at time of authoring; see [change-summary.md](./change-summary.md))

## Purpose

Define the measurable, testable requirements for the Quick Consult Credit saleable product and its configurable denominations, independent of how a purchase is later converted into a credit transaction.

## Scope

**In scope**: Product type/attribute-set association, denomination configuration and extensibility, the boundary between "purchasable item" and "posted credit" (i.e., what does *not* yet constitute a credit event).

**Out of scope**: Credit posting logic itself (see [purchase.md](./purchase.md)), pricing/tax rules beyond denomination value, promotional pricing, product merchandising/SEO.

**Actors**: Registered customer (purchaser), Store Administrator (product/denomination configuration owner), Magento catalog/checkout subsystem.

**System boundaries**: The product configuration governs what can be purchased and at what value; it does not itself grant credit. Credit is granted only per [purchase.md](./purchase.md).

**External dependencies**: Magento Catalog module, Consultation Services attribute set (created if not already present, derived from Magento's Default attribute set; resolved 2026-09-12, see ambiguity register item AMB-008).

## Definitions

- **Denomination**: A fixed monetary value (e.g., $25) that a customer can purchase as Quick Consult Credit.
- **Consultation Services attribute set**: The Magento product attribute set under which the Quick Consult Credit product(s) must be classified (SRS §3.1). If not already present in the target catalog, it is created based on (derived from) Magento's Default attribute set (Clarified 2026-09-12).
- **Qualifying condition**: The order/payment state after which a purchase is eligible for credit posting (defined authoritatively in [purchase.md](./purchase.md); referenced here only for scope boundary).

## Actors

- **Registered customer**: Selects a denomination and completes checkout.
- **Authorized administrator**: Configures the product, attribute set assignment, and the set of available denominations.
- **Magento system/process**: Catalog and checkout subsystems that present and sell the product.

## Functional Requirements

- **QCC-PROD-001**: THE SYSTEM SHALL provide a Magento product representing Quick Consult Credit that customers can add to cart and purchase through standard Magento checkout. *(Source: SRS §3.1, §2.2; Architecture §1)*
- **QCC-PROD-002**: THE SYSTEM SHALL require the Quick Consult Credit product(s) to be assigned to the Consultation Services attribute set. *(Source: SRS §3.1)*
- **QCC-PROD-002a**: IF the Consultation Services attribute set does not already exist in the target catalog, THEN THE SYSTEM SHALL create it, derived from Magento's Default attribute set, before it is assigned to any Quick Consult Credit product. *(Source: Clarified 2026-09-12, resolves AMB-008)*
- **QCC-PROD-003**: THE SYSTEM SHALL support, at initial release, the following denominations as purchasable options: USD 25.00, USD 50.00, USD 100.00, USD 250.00. *(Source: SRS §3.1)*
- **QCC-PROD-004**: THE SYSTEM SHALL allow an authorized administrator to add, modify, or remove available denominations through store configuration or catalog administration, without requiring a code deployment. *(Source: SRS §3.1 "must remain dynamically configurable"; Architecture §19 configuration table)*
- **QCC-PROD-005**: THE SYSTEM SHALL NOT hard-code the set of purchasable denominations in a way that requires a source-code change to add, remove, or modify a denomination value. *(Source: SRS §3.1)*
- **QCC-PROD-006**: WHEN a customer adds a Quick Consult Credit denomination to a cart, THE SYSTEM SHALL NOT create, modify, or post any credit transaction as a result of that action alone. *(Source: SRS §3.3.1 "Note")*
- **QCC-PROD-007**: WHEN an order containing a Quick Consult Credit denomination is created in a pending or otherwise non-qualifying state, THE SYSTEM SHALL NOT post any credit transaction as a result of order creation alone. *(Source: SRS §3.3.1 "Note")*
- **QCC-PROD-008**: THE SYSTEM SHALL determine the credit amount to be posted (per [purchase.md](./purchase.md)) as the purchased denomination value multiplied by the purchased quantity of that denomination line item. *(Source: Architecture §10 "calculate credit amount = configured denomination x quantity")*
- **QCC-PROD-009**: THE SYSTEM SHALL associate each configured denomination with a single unambiguous monetary value expressed in the store's configured account currency (see [account.md](./account.md) QCC-CURR-001). *(Source: SRS §3.1; Architecture §3 "Credit unit")*

## Non-Functional / Constraint Notes

- Denomination configuration ownership (i.e., which admin configuration surface — product attribute values, a dedicated configuration section, or catalog price options — is used to store denominations) is not specified in the SRS or Architecture beyond "dynamically configurable." This is recorded as **AMB-001** in [ambiguity-register.md](./ambiguity-register.md).

## Acceptance Criteria

1. **Given** the Consultation Services attribute set already exists, **When** an administrator configures the Quick Consult Credit product, **Then** the product is associated with that attribute set and is visible/purchasable in the storefront. *(Validates QCC-PROD-001, QCC-PROD-002)*
2. **Given** the Consultation Services attribute set does not yet exist in the target catalog, **When** the Quick Consult Credit module is deployed/configured, **Then** the attribute set is created, derived from the Default attribute set, before any Quick Consult Credit product is assigned to it. *(Validates QCC-PROD-002a)*
2. **Given** the default denomination configuration, **When** a customer views the Quick Consult Credit product, **Then** USD 25, 50, 100, and 250 are available as selectable purchase options. *(Validates QCC-PROD-003)*
3. **Given** an authorized administrator, **When** they add a new denomination value (e.g., USD 500) through configuration, **Then** the new denomination becomes purchasable without a code change or deployment. *(Validates QCC-PROD-004, QCC-PROD-005)*
4. **Given** a customer adds a $100 denomination to their cart and does not complete checkout, **When** the cart is inspected, **Then** no credit transaction exists for that customer. *(Validates QCC-PROD-006)*
5. **Given** an order is created but remains in a pending/unpaid state, **When** the order is inspected, **Then** no credit transaction has been posted. *(Validates QCC-PROD-007)*
6. **Given** a qualifying order for 2 units of the USD 50 denomination, **When** credit is posted, **Then** the posted credit amount equals USD 100.00 (2 × 50). *(Validates QCC-PROD-008)*

## Traceability

See [traceability.md](./traceability.md) for the consolidated matrix; this specification contributes rows `QCC-PROD-001` through `QCC-PROD-009` and `QCC-PROD-002a`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability, no prior specification existed) |
