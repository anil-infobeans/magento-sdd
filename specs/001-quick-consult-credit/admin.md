# Specification: Admin Customer Credit Management

## Metadata

- **Specification name**: Admin Customer Credit Management
- **Specification identifier**: `QCC-ADMIN`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §5.2
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §13, §22.3
- **Dependencies**: [account.md](./account.md), [ledger.md](./ledger.md), [security.md](./security.md), [idempotency-concurrency.md](./idempotency-concurrency.md)
- **Impacted existing specifications**: None found for existing Magento Admin customer-edit specifications in `/specs`

## Purpose

Define the measurable requirements for authorized administrator visibility into, and manual adjustment of, a customer's Quick Consult Credit balance from the Magento Admin customer view.

## Scope

**In scope**: Admin display elements, add/remove credit input requirements and validation, atomicity and audit recording of admin adjustments, separation of ACL from UI visibility.

**Out of scope**: ACL resource definitions themselves (see [security.md](./security.md)); ledger structure (see [ledger.md](./ledger.md)).

**Actors**: Authorized administrator, Magento Admin panel.

**System boundaries**: Admin adjustments invoke the same transaction service contracts as other operations; the admin controller does not update tables directly (Architecture §13).

**External dependencies**: Magento Admin authentication and ACL framework.

## Definitions

- **Admin add**: A manual credit-increasing adjustment performed by an authorized administrator.
- **Admin remove**: A manual credit-decreasing adjustment performed by an authorized administrator.

## Actors

- **Authorized administrator**: Views and adjusts any customer's credit account, subject to ACL.

## Functional Requirements

### Display

- **QCC-ADMIN-001**: THE SYSTEM SHALL display, within the Magento Admin customer view, the customer's current balance, lifetime purchased/credited total, and lifetime redeemed/debited total. *(Source: SRS §5.2)*
- **QCC-ADMIN-002**: THE SYSTEM SHALL display the customer's complete Quick Consult Credit transaction (audit ledger) history within the Admin customer view. *(Source: SRS §5.2)*

### Admin Add

- **QCC-ADMIN-003**: THE SYSTEM SHALL allow an authorized administrator to add credit to a customer's balance by specifying an amount, a reason/message, and an optional reference identifier. *(Source: SRS §5.2 "Admin Add Credit")*
- **QCC-ADMIN-004**: THE SYSTEM SHALL require the admin-add amount to be strictly greater than zero, rejecting otherwise. *(Source: SRS §5.2 "Amount (must be > 0)")*
- **QCC-ADMIN-005**: THE SYSTEM SHALL require a reason/message to be supplied for every admin-add operation. *(Source: SRS §5.2)*

### Admin Remove

- **QCC-ADMIN-006**: THE SYSTEM SHALL allow an authorized administrator to remove credit from a customer's balance by specifying an amount, a reason/message, and an optional reference identifier. *(Source: SRS §5.2 "Admin Remove Credit")*
- **QCC-ADMIN-007**: THE SYSTEM SHALL require the admin-remove amount to be strictly greater than zero and less than or equal to the customer's current available balance, rejecting otherwise. *(Source: SRS §5.2 "Amount (must be > 0 and <= current balance)")*
- **QCC-ADMIN-008**: THE SYSTEM SHALL require a reason/message to be supplied for every admin-remove operation. *(Source: SRS §5.2)*

### Atomicity & Audit

- **QCC-ADMIN-009**: WHEN an admin-add or admin-remove operation succeeds, THE SYSTEM SHALL change the balance by exactly the requested amount, update the corresponding cumulative statistic (total credited or total debited), and create exactly one immutable `ADMIN_ADD` or `ADMIN_REMOVE` ledger transaction, all as a single atomic operation. *(Source: SRS §5.2 "committed atomically"; Architecture §9)*
- **QCC-ADMIN-010**: THE SYSTEM SHALL record the identity (e.g., username/email) of the authenticated administrator who performed the adjustment on the resulting ledger transaction. *(Source: SRS §5.2, §7.1)*
- **QCC-ADMIN-011**: THE SYSTEM SHALL record the supplied reason/message on the resulting ledger transaction. *(Source: SRS §5.2)*
- **QCC-ADMIN-012**: WHERE an optional reference identifier is supplied, THE SYSTEM SHALL record it on the resulting ledger transaction. *(Source: SRS §5.2)*

### ACL Separation

- **QCC-ADMIN-013**: THE SYSTEM SHALL enforce authorization for admin add/remove operations through a dedicated ACL resource independent of whether the corresponding UI controls are rendered. *(Source: Architecture §13 "should not be introduced unless..."; §14; see [security.md](./security.md) QCC-SEC-002)*
- **QCC-ADMIN-014**: IF an authenticated Admin user lacks the required ACL resource, THEN THE SYSTEM SHALL deny the add/remove action even if invoked directly (e.g., via a crafted request bypassing the UI). *(Source: Architecture §14, §16)*

## Acceptance Criteria

1. **Given** a customer with a $75.00 balance, lifetime credited $175.00, lifetime debited $100.00, **When** an administrator opens the customer's Quick Consult Credit tab, **Then** all three figures and the full ledger history are displayed. *(Validates QCC-ADMIN-001, QCC-ADMIN-002)*
2. **Given** a customer with a $75.00 balance, **When** an authorized administrator adds $20.00 with reason "Goodwill credit", **Then** the balance becomes $95.00, total credited increases by $20.00, and one `ADMIN_ADD` ledger row records the administrator's identity and reason. *(Validates QCC-ADMIN-003, QCC-ADMIN-004, QCC-ADMIN-005, QCC-ADMIN-009, QCC-ADMIN-010, QCC-ADMIN-011)*
3. **Given** a customer with a $95.00 balance, **When** an authorized administrator attempts to remove $150.00, **Then** the request is rejected, the balance remains $95.00, and no `ADMIN_REMOVE` ledger row is created. *(Validates QCC-ADMIN-007)*
4. **Given** an admin-add or admin-remove request without a reason/message, **When** it is submitted, **Then** it is rejected. *(Validates QCC-ADMIN-005, QCC-ADMIN-008)*
5. **Given** an authenticated Admin user without the Quick Consult Credit management ACL resource, **When** they attempt to add or remove credit by any means, **Then** the action is denied. *(Validates QCC-ADMIN-013, QCC-ADMIN-014)*

## Traceability

See [traceability.md](./traceability.md). Contributes `QCC-ADMIN-001`…`QCC-ADMIN-014`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability) |
