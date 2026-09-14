# Specification: Auditability & Observability

## Metadata

- **Specification name**: Auditability & Observability
- **Specification identifier**: `QCC-AUDIT`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §6.1, §7.1
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §18
- **Dependencies**: [ledger.md](./ledger.md), [security.md](./security.md), [admin.md](./admin.md)
- **Impacted existing specifications**: None found

## Purpose

Define measurable requirements for auditability of every balance-changing operation and for operational observability of the Quick Consult Credit module, without prescribing a specific logging implementation.

## Scope

**In scope**: Ledger-based audit sufficiency, administrator identity recording, operational failure logging content and exclusions, derivable operational metrics, reconciliation support.

**Out of scope**: Regulatory retention periods (see [non-functional.md](./non-functional.md) Regulatory/Compliance section); the ledger's field-level structure itself (see [ledger.md](./ledger.md)).

**Actors**: Authorized administrator (consumer of audit data), Magento system/process (producer of audit/log data).

**System boundaries**: This specification governs what must be observable/auditable; it does not define a specific log storage or monitoring product.

**External dependencies**: None specific beyond standard Magento logging (Architecture §18 references `var/log/quick_consult_credit.log`).

## Definitions

- **Operational failure**: A condition where a requested state-changing operation could not be completed (e.g., persistence failure, validation rejection, duplicate/idempotency rejection, negative-balance prevention).
- **Reconciliation**: The ability to independently verify that the materialized balance matches the net effect of all ledger transactions for a customer.

## Actors

- **Authorized administrator**: Reviews ledger and log-derived information for support/compliance purposes.
- **Magento system/process**: Produces ledger entries and operational logs.

## Functional Requirements

- **QCC-AUDIT-001**: THE SYSTEM SHALL ensure the immutable ledger (see [ledger.md](./ledger.md)) is sufficient on its own to reconstruct the complete history of balance movements for any customer, without requiring external log correlation. *(Source: SRS §6.1; Architecture §3 "Ledger is audit source")*
- **QCC-AUDIT-002**: THE SYSTEM SHALL record the authenticated administrator's identity on every ledger transaction resulting from an admin-initiated operation. *(Source: SRS §7.1; duplicated with QCC-SEC-005 for cross-cutting emphasis)*
- **QCC-AUDIT-003**: WHEN an operational failure occurs (e.g., persistence failure, validation rejection, duplicate-request rejection, negative-balance prevention), THE SYSTEM SHALL log sufficient context — customer identifier, transaction type, amount, reference type/identifier, source, and exception/failure reason — to diagnose the failure. *(Source: Architecture §18 "Recommended log context")*
- **QCC-AUDIT-004**: THE SYSTEM SHALL NOT include authentication tokens, credentials, or other secrets in any log entry. *(Source: Architecture §18 "without logging sensitive credentials or full authentication tokens")*
- **QCC-AUDIT-005**: THE SYSTEM SHALL make the following operational counts derivable from the ledger and/or operational logs: credits posted, redemptions processed, failed attempts, admin adjustments, duplicate requests detected, and negative-balance-prevention events. *(Source: Architecture §18 "Operational metrics should be derivable from the ledger")*
- **QCC-AUDIT-006**: THE SYSTEM SHALL support reconciliation by allowing the sum of a customer's ledger movements to be compared against their materialized balance at any point in time, with no undetected divergence after any successful operation. *(Source: Architecture §3, §16; supports [account.md](./account.md) QCC-ACCOUNT-007)*
- **QCC-AUDIT-007**: THE SYSTEM SHALL log an operational event for every prevented negative-balance attempt and every rejected duplicate/idempotent request, distinguishable from successful operations. *(Source: Architecture §18 "negative-balance prevention attempts"; §24)*

## Acceptance Criteria

1. **Given** any customer's complete ledger, **When** the movements are summed in order, **Then** the result equals the customer's current materialized balance with no unexplained difference. *(Validates QCC-AUDIT-001, QCC-AUDIT-006)*
2. **Given** an admin add/remove operation, **When** the resulting ledger row is inspected, **Then** the administrator's identity is present. *(Validates QCC-AUDIT-002)*
3. **Given** a simulated persistence failure during a redemption, **When** operational logs are inspected, **Then** an entry exists containing the customer identifier, transaction type, amount, reference, and failure reason, and containing no credentials or tokens. *(Validates QCC-AUDIT-003, QCC-AUDIT-004)*
4. **Given** a period containing known counts of purchases, redemptions, admin adjustments, duplicate requests, and rejected negative-balance attempts, **When** operational metrics are derived, **Then** each count is obtainable and accurate. *(Validates QCC-AUDIT-005, QCC-AUDIT-007)*

## Traceability

See [traceability.md](./traceability.md). Contributes `QCC-AUDIT-001`…`QCC-AUDIT-007`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability) |
