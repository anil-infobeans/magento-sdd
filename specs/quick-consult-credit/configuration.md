# Specification: Configuration

**Specification**: quick-consult-credit / configuration
**Version**: 1.1
**Status**: Draft
**Type**: Normative
**Requirement ID prefix**: QCC-CONFIG

## Change History

| Version | Change | Source | Impact |
|---|---|---|---|
| 1.0 | Initial creation | Technical Architecture §19; SRS §3.1, §5.1 | New specification |
| 1.1 | Set default value for QCC-CONFIG-003 (qualifying condition) to "invoice generated (payment captured)" | /speckit-clarify session 2026-09-15 (CLA-003) | Establishes a concrete default while preserving deployment override |

## Purpose

Defines the deployment-configurable behavior of Quick Consult Credit. No configuration option is introduced beyond what the source documents support.

## Requirements

### QCC-CONFIG-001 — Module enabled/disabled

**Statement (EARS)**: The system shall provide a configuration setting to enable or disable Quick Consult Credit functionality.

**Source**: Technical Architecture §19

**Purpose**: Allows operators to turn the feature on/off without code changes.
**Allowed values**: Enabled, Disabled.
**Default**: Enabled.
**Validation**: Must be a valid boolean/toggle value.
**Runtime effect**: When disabled, purchase posting, redemption, dashboard, and admin adjustment capabilities are not active.
**Behavior when invalid/missing**: Treated as disabled (fail-safe) until explicitly configured.

**Acceptance Criteria**:
- AC-1: Given the setting is disabled, when a qualifying purchase or redemption is attempted, then no credit posting or redemption occurs.

### QCC-CONFIG-002 — Credit product / attribute-set configuration

**Statement (EARS)**: The system shall provide a configuration reference identifying which Magento product(s) and which attribute set represent Quick Consult Credit.

**Source**: SRS §3.1; Technical Architecture §19

**Purpose**: Allows the qualifying product/attribute-set to be established by deployment rather than hardcoded.
**Allowed values**: A valid reference to the Consultation Services attribute set and associated product(s).
**Default**: None (must be configured during deployment).
**Validation**: Referenced attribute set/product must exist.
**Runtime effect**: Determines which order items are evaluated for credit posting.
**Behavior when invalid/missing**: No order items qualify for credit posting; an operational error/warning is logged.

**Acceptance Criteria**:
- AC-1: Given the configuration references a valid attribute set/product, when a qualifying order for that product completes, then it is evaluated for posting per [credit-purchase-posting.md](./credit-purchase-posting.md).
- AC-2: Given the configuration is missing or invalid, when any order is processed, then no Quick Consult Credit posting occurs.

### QCC-CONFIG-003 — Qualifying successful order/payment condition

**Statement (EARS)**: The system shall provide a configuration setting defining which order/payment state qualifies for credit posting.

**Source**: Technical Architecture §3, §10, §19; [clarifications.md](./clarifications.md) CLA-003 (resolved)

**Purpose**: Allows the qualifying condition to match the project's actual checkout/payment lifecycle rather than an assumed state.
**Allowed values**: A valid, deployment-supported order/payment state identifier.
**Default**: "Invoice generated (payment captured)", resolved via /speckit-clarify session 2026-09-15. Deployments may override this default with a different valid order/payment state.
**Validation**: Must reference a state that is reachable and observable within the project's order-processing lifecycle.
**Runtime effect**: Order items reaching this state trigger evaluation for credit posting (see [product-configuration.md](./product-configuration.md) QCC-PROD-007/008).
**Behavior when invalid/missing**: No credit posting occurs; an operational error is logged.

**Acceptance Criteria**:
- AC-1: Given the setting is unset, when any order is processed, then no credit posting occurs and an operational error is logged.
- AC-2: Given the setting is left at its default, when an order's invoice is generated (payment captured), then the order item qualifies for credit-posting evaluation.

### QCC-CONFIG-004 — Customer history page size

**Statement (EARS)**: The system shall provide a configuration setting controlling the number of transaction-history rows displayed per page on the customer dashboard.

**Source**: SRS §5.1; Technical Architecture §12, §19

**Purpose**: Allows pagination size to be tuned per deployment.
**Allowed values**: A positive integer.
**Default**: 20 (per Technical Architecture §19, "20 or project standard").
**Validation**: Must be a positive integer.
**Runtime effect**: Controls page size used by [customer-dashboard.md](./customer-dashboard.md) QCC-CUSTOMER-004.
**Behavior when invalid/missing**: Falls back to the default of 20.

**Acceptance Criteria**:
- AC-1: Given the setting is unset, when the dashboard paginates history, then it uses a page size of 20.
- AC-2: Given the setting is set to a valid positive integer N, when the dashboard paginates history, then it uses page size N.

## Related Specifications

- [product-configuration.md](./product-configuration.md) — consumes QCC-CONFIG-002/003.
- [customer-dashboard.md](./customer-dashboard.md) — consumes QCC-CONFIG-004.
- [clarifications.md](./clarifications.md) — CLA-003 (qualifying condition default value, resolved).
