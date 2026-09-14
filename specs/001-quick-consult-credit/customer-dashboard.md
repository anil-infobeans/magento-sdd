# Specification: Customer Quick Consult Credit Dashboard

## Metadata

- **Specification name**: Customer Quick Consult Credit Dashboard
- **Specification identifier**: `QCC-CUSTOMER`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §5.1
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §12
- **Dependencies**: [account.md](./account.md), [ledger.md](./ledger.md), [security.md](./security.md)
- **Impacted existing specifications**: None found for existing Magento "My Account" navigation specifications in `/specs`

## Purpose

Define the measurable requirements for the authenticated customer-facing Quick Consult Credit dashboard within Magento "My Account", including strict customer-isolation guarantees.

## Scope

**In scope**: Navigation entry, balance display, transaction history display and pagination, customer identity derivation, isolation between customers.

**Out of scope**: Admin-facing views (see [admin.md](./admin.md)); the underlying account/ledger data model (see [account.md](./account.md), [ledger.md](./ledger.md)).

**Actors**: Authenticated registered customer.

**System boundaries**: The dashboard is a read-only view backed by the same service contracts used elsewhere; it does not perform balance-changing operations.

**External dependencies**: Magento customer account session/authentication.

## Definitions

- **Authenticated customer context**: The customer identity established by Magento's session/authentication mechanism for the current request, as distinct from any customer identifier that might be supplied by client-side input.

## Actors

- **Registered customer**: Views only their own balance and history.

## Functional Requirements

- **QCC-CUSTOMER-001**: THE SYSTEM SHALL add a "Quick Consult Credit" navigation entry to the Magento customer "My Account" area. *(Source: SRS §5.1)*
- **QCC-CUSTOMER-002**: THE SYSTEM SHALL display the customer's current available Quick Consult Credit balance on the dashboard. *(Source: SRS §5.1)*
- **QCC-CUSTOMER-003**: THE SYSTEM SHALL display the balance together with its currency. *(Source: SRS §5.1, §4.1)*
- **QCC-CUSTOMER-004**: THE SYSTEM SHALL display a transaction history table containing, for each transaction: date, type, amount (with credit/debit direction indicated, e.g., +$100 / -$25), previous balance, resulting balance, and message/reference. *(Source: SRS §5.1 "Transaction Table Columns"; Architecture §12)*
- **QCC-CUSTOMER-005**: THE SYSTEM SHALL paginate the transaction history rather than returning the complete history in a single response. *(Source: SRS §5.1 "with pagination"; Architecture §12, §19 "Customer history page size")*
- **QCC-CUSTOMER-006**: THE SYSTEM SHALL derive the customer identity used to retrieve balance and history exclusively from the authenticated customer session context. *(Source: Architecture §12 "Customer context must always be derived from the authenticated customer session")*
- **QCC-CUSTOMER-007**: THE SYSTEM SHALL NOT accept a browser- or client-supplied customer identifier as authoritative for determining which customer's balance/history is displayed or returned. *(Source: Architecture §12, §16 "Tampered browser customer id")*
- **QCC-CUSTOMER-008**: THE SYSTEM SHALL restrict each customer to viewing only their own balance and transaction history; requests attempting to view another customer's data SHALL be denied without disclosure. *(Source: SRS §5.1 "Access Control"; Architecture §24 "Customer access")*

## Acceptance Criteria

1. **Given** an authenticated customer, **When** they navigate to "My Account", **Then** a "Quick Consult Credit" entry is present and leads to their dashboard. *(Validates QCC-CUSTOMER-001)*
2. **Given** an authenticated customer with a $75.00 balance, **When** they open the dashboard, **Then** "$75.00" and the account currency are displayed. *(Validates QCC-CUSTOMER-002, QCC-CUSTOMER-003)*
3. **Given** an authenticated customer with a `PURCHASE` and a `REDEEM` transaction, **When** they view the history table, **Then** both rows show date, type, signed amount, previous balance, resulting balance, and message/reference. *(Validates QCC-CUSTOMER-004)*
4. **Given** an authenticated customer with more transactions than one page size, **When** they request subsequent pages, **Then** each page contains a distinct, bounded subset of transactions. *(Validates QCC-CUSTOMER-005)*
5. **Given** authenticated Customer A, **When** Customer A submits a request with Customer B's identifier substituted (e.g., in a form field or query parameter), **Then** the system uses only Customer A's authenticated identity and returns Customer A's own data — never Customer B's. *(Validates QCC-CUSTOMER-006, QCC-CUSTOMER-007, QCC-CUSTOMER-008)*

## Traceability

See [traceability.md](./traceability.md). Contributes `QCC-CUSTOMER-001`…`QCC-CUSTOMER-008`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability) |
