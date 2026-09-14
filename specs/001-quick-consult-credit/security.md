# Specification: Security, Authentication & Authorization

## Metadata

- **Specification name**: Security, Authentication & Authorization
- **Specification identifier**: `QCC-SEC`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §7.1
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §14
- **Dependencies**: [api.md](./api.md), [customer-dashboard.md](./customer-dashboard.md), [admin.md](./admin.md), [audit-observability.md](./audit-observability.md)
- **Impacted existing specifications**: None found for existing authentication/ACL specifications in `/specs`

## Purpose

Define measurable security requirements governing authentication, authorization boundaries, ACL structure, and identity-tampering prevention across all Quick Consult Credit surfaces.

## Scope

**In scope**: Authentication mechanism alignment with Magento-native security, ACL resource structure, least-privilege access, prevention of customer-identity tampering, prevention of credential/token exposure, audit-identity recording.

**Out of scope**: Business validation rules themselves (covered in the domain specifications that reference this one); network-layer transport security (TLS termination) which is a general Magento/infrastructure concern not specific to this feature.

**Actors**: Registered customer, authorized administrator, authorized external integration, Magento Web API / ACL framework.

**System boundaries**: This specification constrains every other Quick Consult Credit specification; it does not independently expose new functionality.

**External dependencies**: Magento customer authentication, Magento admin authentication, Magento integration/OAuth token framework, Magento ACL framework.

## Definitions

- **Least privilege**: Granting each actor only the ACL permissions required to perform the actions they are authorized to perform, no more.
- **Identity tampering**: An attempt to influence which customer's data is accessed or modified by supplying a client-controlled identifier that conflicts with the authenticated identity.

## Actors

- **Registered customer**: Authenticated via Magento customer session/token; authorized only for their own data.
- **Authorized administrator**: Authenticated via Magento admin session/token; authorized per ACL resource grants.
- **Authorized external integration**: Authenticated via Magento integration/admin token; authorized per ACL/API scope.

## Functional Requirements

- **QCC-SEC-001**: THE SYSTEM SHALL authenticate all Quick Consult Credit API requests using Magento's native Web API security mechanisms (customer token, admin token, or integration token), and SHALL NOT implement a custom/bespoke authentication mechanism. *(Source: SRS §7.1 "Magento's web API security framework"; Architecture §14)*
- **QCC-SEC-002**: THE SYSTEM SHALL define a dedicated Magento ACL resource hierarchy for Quick Consult Credit administration (e.g., a parent "credit" resource and a child "manage credit" resource) distinct from unrelated Magento ACL resources. *(Source: SRS §7.1 "dedicated Magento ACL resource"; Architecture §14)*
- **QCC-SEC-003**: THE SYSTEM SHALL grant each actor class (customer, administrator, integration) only the minimum ACL/authorization scope required for their defined operations (e.g., a customer-facing token SHALL NOT be sufficient to perform admin add/remove operations). *(Source: Architecture §14 "Recommended permissions... match the organization role model")*
- **QCC-SEC-004**: THE SYSTEM SHALL derive customer identity for customer-facing operations exclusively from the authenticated session/token context, and SHALL NOT accept a client-supplied customer identifier as authoritative for customer self-service operations. *(Source: Architecture §12, §16 "Tampered browser customer id"; duplicated as QCC-CUSTOMER-006/007 for the dashboard surface)*
- **QCC-SEC-005**: THE SYSTEM SHALL record the authenticated administrator's identity on every ledger transaction resulting from an admin-initiated operation. *(Source: SRS §7.1 "All admin activities must record the administrator's email/username directly in the immutable ledger"; see [ledger.md](./ledger.md) QCC-LEDGER-002)*
- **QCC-SEC-006**: THE SYSTEM SHALL NOT include authentication tokens, credentials, or equivalent secrets in application logs, ledger records, or API responses. *(Source: SRS implied by §7.1 auditing requirements combined with general Magento security posture; Architecture §18 "without logging sensitive credentials or full authentication tokens"; see [audit-observability.md](./audit-observability.md) QCC-AUDIT-003)*
- **QCC-SEC-007**: IF a caller's authentication is invalid or absent, THEN THE SYSTEM SHALL reject the request with an unauthorized response and SHALL NOT process any balance-changing or balance-disclosing operation. *(Source: SRS §4.1, §4.2)*
- **QCC-SEC-008**: THE SYSTEM SHALL evaluate authorization for the target customer before evaluating whether that customer exists. IF a caller is authenticated but not authorized for the specific customer or operation requested, THEN THE SYSTEM SHALL deny the request with a uniform unauthorized/denied response, regardless of whether the target customer exists, and SHALL NOT return `CUSTOMER_NOT_FOUND` in that case. THE SYSTEM SHALL return `CUSTOMER_NOT_FOUND` only after the caller's authorization for the target customer has already been established and the customer is subsequently found not to exist. *(Source: SRS §4.1, §4.2 error scenarios; least-disclosure/anti-enumeration principle; Clarified 2026-09-12, resolves AMB-005)*

## Acceptance Criteria

1. **Given** a request without a valid Magento authentication token, **When** any Quick Consult Credit API endpoint is called, **Then** the request is rejected as unauthorized. *(Validates QCC-SEC-001, QCC-SEC-007)*
2. **Given** a customer-scoped token, **When** an admin-only operation (add/remove credit) is attempted, **Then** the request is denied. *(Validates QCC-SEC-002, QCC-SEC-003)*
3. **Given** an authenticated customer session for Customer A, **When** a request substitutes Customer B's identifier in any client-controlled field, **Then** the system still operates only on Customer A's data. *(Validates QCC-SEC-004)*
4. **Given** an authorized administrator performs an add/remove operation, **When** the resulting ledger row is inspected, **Then** it contains the administrator's authenticated identity. *(Validates QCC-SEC-005)*
5. **Given** any operational log or API response produced by the system, **When** inspected, **Then** it contains no authentication tokens or credentials. *(Validates QCC-SEC-006)*
6. **Given** a caller not authorized for a target customer, **When** the target customer identifier does not actually exist, **Then** the response is the same uniform unauthorized/denied response as when the customer does exist, and `CUSTOMER_NOT_FOUND` is never returned to an unauthorized caller. *(Validates QCC-SEC-008)*

## Traceability

See [traceability.md](./traceability.md). Contributes `QCC-SEC-001`…`QCC-SEC-008`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability) |
