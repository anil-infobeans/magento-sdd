# Traceability Matrix: Quick Consult Credit

This matrix maps every requirement identifier introduced in this specification set back to its source (SRS §/Architecture §), the specification file that defines it, and the acceptance test(s) that verify it. Acceptance test references use the format `<file>#AC<n>` referring to the numbered item in that file's "Acceptance Criteria" section.

## Cross-Cutting

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-CROSS-001 | Business logic behind service contracts only | Architecture §6.2, §8 | spec.md | non-functional.md#AC2, all domain files |
| QCC-CROSS-002 | Ledger = audit source; balance = materialized state | Architecture §3, §6 | spec.md | account.md#AC3 |
| QCC-CROSS-003 | No manual SQL for routine ops; corrections via compensating entries | Architecture §25 | spec.md | ledger.md#AC3 |

## Product & Denomination Configuration (product.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-PROD-001 | Product purchasable via standard checkout | SRS §3.1, §2.2; Architecture §1 | product.md | product.md#AC1 |
| QCC-PROD-002 | Assigned to Consultation Services attribute set | SRS §3.1 | product.md | product.md#AC1 |
| QCC-PROD-002a | Create attribute set from Default if absent | Clarified 2026-09-12 | product.md | product.md#AC2 |
| QCC-PROD-003 | Default denominations $25/$50/$100/$250 | SRS §3.1 | product.md | product.md#AC2 |
| QCC-PROD-004 | Denominations configurable without code change | SRS §3.1 | product.md | product.md#AC3 |
| QCC-PROD-005 | No hard-coded denomination set | SRS §3.1 | product.md | product.md#AC3 |
| QCC-PROD-006 | No credit on cart add | SRS §3.3.1 | product.md | product.md#AC4 |
| QCC-PROD-007 | No credit on pending/non-qualifying order | SRS §3.3.1 | product.md | product.md#AC5 |
| QCC-PROD-008 | Credit amount = denomination × quantity | Architecture §10 | product.md | product.md#AC6 |
| QCC-PROD-009 | Denomination value expressed in account currency | SRS §3.1; Architecture §3 | product.md | account.md#AC4 |

## Customer Credit Account & Currency (account.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-ACCOUNT-001 | One account per customer per currency | SRS §3.2; Architecture §6 | account.md | account.md#AC2 |
| QCC-ACCOUNT-002 | Account fields: balance, total credited, total debited | SRS §3.2 | account.md | account.md#AC2 |
| QCC-ACCOUNT-003 | Account auto-created on demand (zero balance) | Architecture §8 | account.md | account.md#AC2 |
| QCC-ACCOUNT-004 | No account -> balance 0.00, no error | SRS §4.1 | account.md | account.md#AC1 |
| QCC-ACCOUNT-005 | created_at/updated_at recorded | Architecture §7.1 | account.md | ledger.md#AC1 |
| QCC-ACCOUNT-006 | Balance never negative | SRS §7.2; Architecture §16 | account.md | account.md#AC3 |
| QCC-ACCOUNT-007 | Every balance change has one ledger entry (no divergence) | Architecture §9, §16 | account.md | account.md#AC3 |
| QCC-ACCOUNT-008 | Credit op increases balance + total credited exactly | SRS §3.2; Architecture §9.1 | account.md | account.md#AC2 |
| QCC-ACCOUNT-009 | Debit op decreases balance, increases total debited exactly | SRS §3.2; Architecture §9.2 | account.md | redemption.md#AC1 |
| QCC-CURR-001 | Single account currency established at creation | Architecture §3, §19 | account.md | account.md#AC2 |
| QCC-CURR-002 | Two-decimal monetary precision | Architecture §3, §7.1/§7.2 | account.md | account.md#AC5 |
| QCC-CURR-003 | Exact decimal comparison, no float approximation | Architecture §3 | account.md | account.md#AC5 |
| QCC-CURR-004 | Currency mismatch -> reject, no balance change | Architecture §19 | account.md | account.md#AC4 |
| QCC-CURR-005 | No currency conversion performed | Architecture §26 | account.md | non-functional.md#AC3 |
| QCC-CURR-006 | One account currency per customer (initial release) | Architecture §3, §26 | account.md | non-functional.md#AC3 |

## Immutable Ledger (ledger.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-LEDGER-001 | Every successful op produces a new ledger record | SRS §3.3.1/§3.3.2; Architecture §9 | ledger.md | ledger.md#AC1 |
| QCC-LEDGER-002 | Minimum required ledger fields | SRS §6.1; Architecture §7.2 | ledger.md | ledger.md#AC1 |
| QCC-LEDGER-003 | transaction_type enum (PURCHASE/REDEEM/ADMIN_ADD/ADMIN_REMOVE) | SRS §3.2, §6.1 | ledger.md | ledger.md#AC1 |
| QCC-LEDGER-004 | direction CREDIT/DEBIT mapping | Architecture §7.2 | ledger.md | ledger.md#AC1 |
| QCC-LEDGER-005 | amount stored as positive magnitude | Architecture §7.2 | ledger.md | ledger.md#AC1 |
| QCC-LEDGER-006 | balance_before/after computed & stored correctly | Architecture §7.2, §9 | ledger.md | ledger.md#AC1 |
| QCC-LEDGER-007 | No field mutation of existing entries | SRS §6.1; Architecture §6 | ledger.md | ledger.md#AC2 |
| QCC-LEDGER-008 | No deletion of existing entries | SRS §6.1 | ledger.md | ledger.md#AC2 |
| QCC-LEDGER-009 | Corrections via compensating transactions only | Architecture §25 | ledger.md | ledger.md#AC3 |
| QCC-LEDGER-010 | Retrieval ordered most-recent-first | SRS §5.1; Architecture §8 | ledger.md | customer-dashboard.md#AC3 |
| QCC-LEDGER-011 | Retrieval by transaction ID | Architecture §8 | ledger.md | ledger.md#AC4 |
| QCC-LEDGER-012 | Paginated retrieval support | Architecture §12 | ledger.md | ledger.md#AC4 |

## Purchase Posting (purchase.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-PURCHASE-001 | Configurable qualifying condition | SRS §3.1, §3.3.1; Architecture §19 | purchase.md | purchase.md#AC1 |
| QCC-PURCHASE-002 | No posting before qualifying condition | SRS §3.3.1 | purchase.md | purchase.md#AC3 |
| QCC-PURCHASE-003 | Illustrated default: invoice + order complete | SRS §3.1 | purchase.md | purchase.md#AC1 |
| QCC-PURCHASE-004 | Posting sequence: amount, one txn per reference | SRS §3.3.1; Architecture §10 | purchase.md | purchase.md#AC1 |
| QCC-PURCHASE-005 | Reference = order/order item recorded | SRS §3.3.1 | purchase.md | purchase.md#AC1 |
| QCC-PURCHASE-006 | Balance + ledger update atomic with posting | Architecture §9.1 | purchase.md | purchase.md#AC1 |
| QCC-PURCHASE-007 | Idempotent w.r.t. deterministic reference | Architecture §10, §15 | purchase.md | purchase.md#AC6 |
| QCC-PURCHASE-008 | Duplicate qualifying event -> skip, no second credit | Architecture §10 | purchase.md | purchase.md#AC6 |
| QCC-PURCHASE-009 | DB-level uniqueness constraint on purchase reference | Architecture §15 | purchase.md | purchase.md#AC6 |
| QCC-PURCHASE-010 | Cart-only -> no credit | SRS §3.3.1 | purchase.md | purchase.md#AC2 |
| QCC-PURCHASE-011 | Quote-only -> no credit | SRS §3.3.1 | purchase.md | purchase.md#AC2 |
| QCC-PURCHASE-012 | Pending/non-qualifying order -> no credit | SRS §3.1, §3.3.1 | purchase.md | purchase.md#AC3 |
| QCC-PURCHASE-013 | Failed payment -> no credit | SRS §3.1 | purchase.md | purchase.md#AC4 |
| QCC-PURCHASE-014 | Cancelled order -> no credit | SRS §3.1; Architecture §2 | purchase.md | purchase.md#AC5 |
| QCC-PURCHASE-015 | Duplicate delivered event -> post at most once | Architecture §24 | purchase.md | purchase.md#AC6 |
| QCC-PURCHASE-016 | Partial failure -> full rollback | Architecture §9.1, §17 | purchase.md | purchase.md#AC7 |

## Redemption (redemption.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-REDEEM-001 | Customer must exist | SRS §3.3.2 | redemption.md | redemption.md#AC4 |
| QCC-REDEEM-002 | Amount must be > 0 | SRS §4.2 | redemption.md | redemption.md#AC3 |
| QCC-REDEEM-003 | Amount must be <= balance | SRS §3.3.2 | redemption.md | redemption.md#AC2 |
| QCC-REDEEM-004 | Balance reduced by exact amount | SRS §3.3.2 | redemption.md | redemption.md#AC1 |
| QCC-REDEEM-005 | Total debited increased by exact amount | SRS §3.2, §3.3.2 | redemption.md | redemption.md#AC1 |
| QCC-REDEEM-006 | Exactly one REDEEM ledger row | SRS §3.3.2; Architecture §7.2 | redemption.md | redemption.md#AC1 |
| QCC-REDEEM-007 | Response includes balance + reference | SRS §3.3.2, §4.2 | redemption.md | redemption.md#AC1 |
| QCC-REDEEM-008 | Insufficient balance -> reject, no change | SRS §3.3.2, §4.2, §7.2 | redemption.md | redemption.md#AC2 |
| QCC-REDEEM-009 | Invalid amount -> reject | SRS §4.2 | redemption.md | redemption.md#AC3 |
| QCC-REDEEM-010 | Unknown customer -> reject | SRS §4.1, §4.2 | redemption.md | redemption.md#AC4 |
| QCC-REDEEM-011 | Unauthorized caller -> deny, no disclosure | SRS §7.1 | redemption.md | redemption.md#AC5 |
| QCC-REDEEM-012 | Duplicate request -> no second debit | SRS §7.2; Architecture §15 | redemption.md | redemption.md#AC7 |
| QCC-REDEEM-013 | Concurrent requests -> at most one succeeds | Architecture §16, §24 | redemption.md | redemption.md#AC6 |

## REST API (api.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-API-001 | GET balance endpoint | SRS §4.1; Architecture §11.1 | api.md | api.md#AC1 |
| QCC-API-002 | customerId path parameter | SRS §4.1 | api.md | api.md#AC1 |
| QCC-API-003 | Token authentication required | SRS §4.1 | api.md | api.md#AC4 |
| QCC-API-004 | Response fields: customer_id, balance, currency | SRS §4.1 | api.md | api.md#AC1 |
| QCC-API-005 | Unknown customer -> CUSTOMER_NOT_FOUND | SRS §4.1 | api.md | api.md#AC2 |
| QCC-API-006 | No account -> balance 0.00 | SRS §4.1 | api.md | api.md#AC3 |
| QCC-API-007 | Unauthorized -> HTTP 401, no disclosure | SRS §4.1 | api.md | api.md#AC4 |
| QCC-API-008 | POST transaction endpoint | SRS §4.2; Architecture §11.2 | api.md | api.md#AC5 |
| QCC-API-009 | Mandatory/optional request fields | SRS §4.2; Architecture §11.2 | api.md | api.md#AC5 |
| QCC-API-010 | Integration/admin auth required | SRS §4.2 | api.md | api.md#AC5 |
| QCC-API-011 | amount > 0 validation | SRS §4.2, §7.2 | api.md | api.md#AC6 |
| QCC-API-012 | customer_id existence validation | SRS §4.2 | api.md | api.md#AC6 |
| QCC-API-013 | idempotency_key required (when enabled) | Architecture §15, §19 | api.md | api.md#AC8 |
| QCC-API-014 | Success response fields | SRS §4.2; Architecture §11.2 | api.md | api.md#AC5 |
| QCC-API-015 | INSUFFICIENT_BALANCE mapping | SRS §4.2; Architecture §11.3 | api.md | api.md#AC6 |
| QCC-API-016 | External callers restricted to authorized transaction types | derived; SRS §3.2, §7.1 | api.md | ambiguity-register.md AMB-004 |
| QCC-API-017 | Duplicate idempotency key -> deterministic replay response | Architecture §11.3, §15 | api.md | api.md#AC7 |
| QCC-API-018 | Concurrent requests -> retry-safe, no partial update | Architecture §11.3 | api.md | idempotency-concurrency.md#AC2 |
| QCC-API-019 | Standardized error payload | SRS §7.2 | api.md | api.md#AC6 |
| QCC-API-020 | No secrets in API responses | SRS §7.1 | api.md | security.md#AC5 |

## Customer Dashboard (customer-dashboard.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-CUSTOMER-001 | Nav entry in My Account | SRS §5.1 | customer-dashboard.md | customer-dashboard.md#AC1 |
| QCC-CUSTOMER-002 | Current balance displayed | SRS §5.1 | customer-dashboard.md | customer-dashboard.md#AC2 |
| QCC-CUSTOMER-003 | Balance shown with currency | SRS §5.1, §4.1 | customer-dashboard.md | customer-dashboard.md#AC2 |
| QCC-CUSTOMER-004 | History table columns | SRS §5.1; Architecture §12 | customer-dashboard.md | customer-dashboard.md#AC3 |
| QCC-CUSTOMER-005 | Pagination | SRS §5.1; Architecture §12, §19 | customer-dashboard.md | customer-dashboard.md#AC4 |
| QCC-CUSTOMER-006 | Identity from authenticated session only | Architecture §12 | customer-dashboard.md | customer-dashboard.md#AC5 |
| QCC-CUSTOMER-007 | Client-supplied customer id not authoritative | Architecture §12, §16 | customer-dashboard.md | customer-dashboard.md#AC5 |
| QCC-CUSTOMER-008 | Customer isolation (A cannot view B) | SRS §5.1; Architecture §24 | customer-dashboard.md | customer-dashboard.md#AC5 |

## Admin (admin.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-ADMIN-001 | Display balance + lifetime totals | SRS §5.2 | admin.md | admin.md#AC1 |
| QCC-ADMIN-002 | Display full ledger history | SRS §5.2 | admin.md | admin.md#AC1 |
| QCC-ADMIN-003 | Add credit inputs | SRS §5.2 | admin.md | admin.md#AC2 |
| QCC-ADMIN-004 | Add amount > 0 | SRS §5.2 | admin.md | admin.md#AC2 |
| QCC-ADMIN-005 | Add requires reason | SRS §5.2 | admin.md | admin.md#AC4 |
| QCC-ADMIN-006 | Remove credit inputs | SRS §5.2 | admin.md | admin.md#AC3 |
| QCC-ADMIN-007 | Remove amount > 0 and <= balance | SRS §5.2 | admin.md | admin.md#AC3 |
| QCC-ADMIN-008 | Remove requires reason | SRS §5.2 | admin.md | admin.md#AC4 |
| QCC-ADMIN-009 | Atomic balance+stat+ledger commit | SRS §5.2; Architecture §9 | admin.md | admin.md#AC2 |
| QCC-ADMIN-010 | Admin identity recorded | SRS §5.2, §7.1 | admin.md | admin.md#AC2 |
| QCC-ADMIN-011 | Reason recorded on ledger | SRS §5.2 | admin.md | admin.md#AC2 |
| QCC-ADMIN-012 | Optional reference recorded | SRS §5.2 | admin.md | admin.md#AC2 |
| QCC-ADMIN-013 | Dedicated ACL independent of UI visibility | Architecture §13, §14 | admin.md | admin.md#AC5 |
| QCC-ADMIN-014 | Deny action without ACL, even direct invocation | Architecture §14, §16 | admin.md | admin.md#AC5 |

## Security (security.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-SEC-001 | Magento-native authentication only | SRS §7.1; Architecture §14 | security.md | security.md#AC1 |
| QCC-SEC-002 | Dedicated ACL resource hierarchy | SRS §7.1; Architecture §14 | security.md | security.md#AC2 |
| QCC-SEC-003 | Least-privilege per actor class | Architecture §14 | security.md | security.md#AC2 |
| QCC-SEC-004 | Authenticated identity, not client-supplied id | Architecture §12, §16 | security.md | security.md#AC3 |
| QCC-SEC-005 | Admin identity recorded on ledger | SRS §7.1 | security.md | security.md#AC4 |
| QCC-SEC-006 | No secrets in logs/ledger/responses | Architecture §18 | security.md | security.md#AC5 |
| QCC-SEC-007 | Invalid/absent auth -> reject | SRS §4.1, §4.2 | security.md | security.md#AC1 |
| QCC-SEC-008 | Unauthorized -> deny without enumeration (see AMB-005) | SRS §4.1, §4.2 | security.md | ambiguity-register.md AMB-005 |

## Idempotency & Concurrency (idempotency-concurrency.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-CONC-001 | Account row is synchronization point | Architecture §16 | idempotency-concurrency.md | idempotency-concurrency.md#AC2 |
| QCC-CONC-002 | Atomic commit of balance+stat+ledger+idempotency key | SRS §6.2; Architecture §9 | idempotency-concurrency.md | idempotency-concurrency.md#AC1 |
| QCC-CONC-003 | Full rollback on any failure | Architecture §9.1, §17 | idempotency-concurrency.md | idempotency-concurrency.md#AC1 |
| QCC-CONC-004 | No ledger-without-balance or balance-without-ledger state | Architecture §16 | idempotency-concurrency.md | idempotency-concurrency.md#AC1 |
| QCC-CONC-005 | Row-level lock before read; no stale-read-then-update | Architecture §9.2, §16 | idempotency-concurrency.md | idempotency-concurrency.md#AC2 |
| QCC-CONC-006 | Serialized effect under concurrent requests | Architecture §9.2, §16 | idempotency-concurrency.md | idempotency-concurrency.md#AC2 |
| QCC-CONC-007 | $100 balance, two concurrent $80 redeems scenario | Architecture §24 | idempotency-concurrency.md | idempotency-concurrency.md#AC2 |
| QCC-IDEMP-001 | Deterministic purchase reference + DB uniqueness | Architecture §15 | idempotency-concurrency.md | idempotency-concurrency.md#AC3 |
| QCC-IDEMP-002 | Duplicate qualifying event posted at most once | Architecture §10, §24 | idempotency-concurrency.md | idempotency-concurrency.md#AC3 |
| QCC-IDEMP-003 | idempotency_key required for external writes (when enabled) | Architecture §15, §19 | idempotency-concurrency.md | idempotency-concurrency.md#AC4 |
| QCC-IDEMP-004 | Replay returns original result | Architecture §11.3, §15 | idempotency-concurrency.md | idempotency-concurrency.md#AC4 |
| QCC-IDEMP-005 | DB-level uniqueness constraint (not app-level only) | Architecture §15 | idempotency-concurrency.md | idempotency-concurrency.md#AC4 |
| QCC-IDEMP-006 | Conflicting-payload replay behavior undefined (AMB-006) | n/a (gap) | idempotency-concurrency.md | ambiguity-register.md AMB-006 |

## Audit & Observability (audit-observability.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-AUDIT-001 | Ledger sufficient to reconstruct history | SRS §6.1; Architecture §3 | audit-observability.md | audit-observability.md#AC1 |
| QCC-AUDIT-002 | Admin identity on admin-initiated ledger rows | SRS §7.1 | audit-observability.md | audit-observability.md#AC2 |
| QCC-AUDIT-003 | Failure logging with sufficient context | Architecture §18 | audit-observability.md | audit-observability.md#AC3 |
| QCC-AUDIT-004 | No secrets in logs | Architecture §18 | audit-observability.md | audit-observability.md#AC3 |
| QCC-AUDIT-005 | Derivable operational metrics | Architecture §18 | audit-observability.md | audit-observability.md#AC4 |
| QCC-AUDIT-006 | Reconciliation support (ledger vs balance) | Architecture §3, §16 | audit-observability.md | audit-observability.md#AC1 |
| QCC-AUDIT-007 | Log negative-balance-prevention & duplicate-rejection events | Architecture §18, §24 | audit-observability.md | audit-observability.md#AC4 |

## Non-Functional (non-functional.md)

| Requirement ID | Requirement (summary) | Source | Specification | Acceptance Test |
|---|---|---|---|---|
| QCC-PERF-001 | Balance retrieval latency target (approved 2026-09-12) | Not in SRS/Architecture — proposed and approved via clarification | non-functional.md | non-functional.md#AC1 |
| QCC-PERF-002 | History retrieval latency target (approved 2026-09-12) | Not in SRS/Architecture — proposed and approved via clarification | non-functional.md | non-functional.md#AC1 |
| QCC-PERF-003 | Redemption latency target (approved 2026-09-12) | Not in SRS/Architecture — proposed and approved via clarification | non-functional.md | non-functional.md#AC1 |
| QCC-PERF-004 | Admin view latency target (approved 2026-09-12) | Not in SRS/Architecture — proposed and approved via clarification | non-functional.md | non-functional.md#AC1 |
| QCC-PERF-005 | Correctness unconditional on performance | Architecture §16, §24 | non-functional.md | idempotency-concurrency.md#AC2 |
| QCC-PERF-006 | Default page size 20 | Architecture §19 | non-functional.md | customer-dashboard.md#AC4 |
| QCC-COMPLY-001 | Retention/privacy policy support (TBD period) | Task instruction; no SRS regulation named | non-functional.md | ambiguity-register.md AMB-009 |
| QCC-COMPLY-002 | Access restricted to owner + authorized actors | SRS §5.1, §7.1 | non-functional.md | security.md#AC1 |
| QCC-COMPLY-003 | Admin accountability via reason + identity | SRS §5.2, §7.1 | non-functional.md | admin.md#AC2 |
| QCC-COMPLY-004 | Data integrity/traceability via immutable ledger | SRS §6.1 | non-functional.md | ledger.md#AC2 |
| QCC-COMPLY-005 | Aligned with existing payment/order compliance controls | SRS §2.5 | non-functional.md | purchase.md#AC1 |
| QCC-COMPLY-006 | No specific jurisdiction/regulation assumed (TBD) | Not identified in source docs | non-functional.md | ambiguity-register.md AMB-010 |
| QCC-TEST-001 | Unit test coverage | Architecture §23 | non-functional.md | non-functional.md#AC2 |
| QCC-TEST-002 | Integration test coverage | Architecture §23 | non-functional.md | non-functional.md#AC2 |
| QCC-TEST-003 | API test coverage | Architecture §23 | non-functional.md | non-functional.md#AC2 |
| QCC-TEST-004 | Magento functional test coverage | Architecture §23, §24 | non-functional.md | non-functional.md#AC2 |
| QCC-TEST-005 | Concurrency test coverage | Architecture §23 | non-functional.md | non-functional.md#AC2 |
| QCC-TEST-006 | Regression test coverage | Architecture §23 | non-functional.md | non-functional.md#AC2 |
| QCC-TEST-007 | E2E regression/performance/security testing | SRS §8.1 | non-functional.md | non-functional.md#AC2 |
| QCC-TEST-008 | 20 minimum acceptance scenarios | User-specified checklist | non-functional.md | non-functional.md#AC2 |
| QCC-SCOPE-001 | Credit expiry excluded | SRS §8.1; Architecture §2 | non-functional.md | non-functional.md#AC3 |
| QCC-SCOPE-002 | Automated refund/reversal excluded | SRS §8.1; Architecture §2, §26 | non-functional.md | non-functional.md#AC3 |
| QCC-SCOPE-003 | Recurring credit subscriptions excluded | SRS §8.1; Architecture §2 | non-functional.md | non-functional.md#AC3 |
| QCC-SCOPE-004 | Promotional/loyalty campaigns excluded | SRS §8.1; Architecture §2 | non-functional.md | non-functional.md#AC3 |
| QCC-SCOPE-005 | Peer-to-peer transfers excluded | SRS §8.1 | non-functional.md | non-functional.md#AC3 |
| QCC-SCOPE-006 | Split payment checkout excluded | SRS §8.1; Architecture §2 | non-functional.md | non-functional.md#AC3 |
| QCC-SCOPE-007 | Real-time notifications excluded | SRS §8.1 | non-functional.md | non-functional.md#AC3 |
| QCC-SCOPE-008 | Advanced merchant analytics excluded | SRS §8.1 | non-functional.md | non-functional.md#AC3 |
| QCC-SCOPE-009 | Multi-currency conversion excluded | Architecture §3, §26 | non-functional.md | non-functional.md#AC3 |

## Coverage Summary

- Total requirement identifiers: 3 (CROSS) + 9 (PROD) + 15 (ACCOUNT/CURR) + 12 (LEDGER) + 16 (PURCHASE) + 13 (REDEEM) + 20 (API) + 8 (CUSTOMER) + 14 (ADMIN) + 8 (SEC) + 13 (CONC/IDEMP) + 7 (AUDIT) + 29 (PERF/COMPLY/TEST/SCOPE) = **167**.
- Every requirement above has at least one acceptance test reference, either within its own specification file or a cross-referenced ambiguity-register/related-file item where the requirement itself documents an open decision rather than a bound behavior (QCC-API-016, QCC-SEC-008, QCC-IDEMP-006).
