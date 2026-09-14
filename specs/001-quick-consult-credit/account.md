# Specification: Customer Credit Account, Balance, Currency & Monetary Precision

## Metadata

- **Specification name**: Customer Credit Account, Balance, Currency & Monetary Precision
- **Specification identifier**: `QCC-ACCOUNT` / `QCC-CURR`
- **Version**: 1.0.0
- **Status**: Draft
- **Source SRS version**: Quick Consult Credit SRS v1.0, §3.2, §6.1
- **Related architecture document/version**: Quick Consult Credit Technical Architecture, 10 Sep 2026, §3, §6, §7.1
- **Dependencies**: [ledger.md](./ledger.md), [idempotency-concurrency.md](./idempotency-concurrency.md)
- **Impacted existing specifications**: None found

## Purpose

Define measurable, testable requirements for the customer credit account: its structure, lifecycle, balance invariants, and monetary/currency rules, independent of the transaction types that change it.

## Scope

**In scope**: Account structure, one-account-per-customer-and-currency-context rule, balance/lifetime-statistic semantics, account creation behavior, currency and precision rules, cross-currency behavior.

**Out of scope**: The specific business operations that change the balance (purchase, redemption, admin adjustment — see [purchase.md](./purchase.md), [redemption.md](./redemption.md), [admin.md](./admin.md)); ledger record structure (see [ledger.md](./ledger.md)).

**Actors**: Registered customer, authorized administrator, authorized external integration, Magento system/process (all as consumers of the account's current state).

**System boundaries**: The account represents materialized current state only; the ledger (see [ledger.md](./ledger.md)) is the audit source of truth from which the account balance is derived (Architecture §3, §6).

**External dependencies**: Magento customer entity (`customer_id` as defined in Architecture §3 "Customer identity").

## Definitions

- **Credit account**: The per-customer record holding current balance and lifetime statistics (Architecture §6, `qcc_customer_credit`).
- **Available balance**: The current spendable credit amount for a customer.
- **Total credited**: Cumulative lifetime sum of all successful credit (inbound) movements.
- **Total debited**: Cumulative lifetime sum of all successful debit (outbound) movements.
- **Account currency**: The single currency context associated with a customer's credit account.

## Actors

- **Registered customer**: Owns exactly one credit account per supported currency context.
- **Authorized administrator**: Views and adjusts account state through [admin.md](./admin.md) operations.
- **Authorized external integration**: Reads balance and initiates debit/credit operations through [api.md](./api.md).
- **Magento system/process**: Creates/updates the account as a side effect of purchase posting.

## Functional Requirements

### Account Structure & Lifecycle

- **QCC-ACCOUNT-001**: THE SYSTEM SHALL maintain at most one credit account per customer per currency context. *(Source: SRS §3.2; Architecture §6 "Balance model")*
- **QCC-ACCOUNT-002**: THE SYSTEM SHALL record, for each credit account: the owning customer identifier, current available balance, cumulative total credited amount, and cumulative total debited amount. *(Source: SRS §3.2)*
- **QCC-ACCOUNT-003**: WHEN a customer has no existing credit account and a qualifying credit-worthy event occurs (purchase posting, admin add, or an explicit balance query per [api.md](./api.md) QCC-API-004), THE SYSTEM SHALL create the credit account on demand with a zero balance prior to applying any pending balance change. *(Source: Architecture §8 "getOrCreateBalance")*
- **QCC-ACCOUNT-004**: IF a customer has never had a credit account created, THEN THE SYSTEM SHALL treat their available balance as zero for read operations without requiring an error. *(Source: SRS §4.1 "Customer has no credit account -> Return balance 0.00")*
- **QCC-ACCOUNT-005**: THE SYSTEM SHALL record an account creation timestamp and a last-updated timestamp for each credit account. *(Source: Architecture §7.1 `created_at`/`updated_at`)*

### Balance Invariants

- **QCC-ACCOUNT-006**: THE SYSTEM SHALL NOT allow any supported operation to result in an available balance less than zero. *(Source: SRS §7.2 "Negative Balance Prevention"; Architecture §16)*
- **QCC-ACCOUNT-007**: THE SYSTEM SHALL ensure that every successful balance-changing operation (purchase, redeem, admin add, admin remove) has exactly one corresponding ledger transaction (see [ledger.md](./ledger.md)), such that the account balance and the sum of ledger movements never diverge after a successful operation. *(Source: Architecture §9, §16 "Ledger exists but balance not updated" / "Balance updated but ledger missing" — both are prevented failure states)*
- **QCC-ACCOUNT-008**: WHEN a credit operation succeeds, THE SYSTEM SHALL increase both the available balance and the total credited amount by exactly the credited amount. *(Source: SRS §3.2; Architecture §9.1)*
- **QCC-ACCOUNT-009**: WHEN a debit operation succeeds, THE SYSTEM SHALL decrease the available balance and increase the total debited amount by exactly the debited amount. *(Source: SRS §3.2; Architecture §9.2)*

### Currency & Monetary Precision

- **QCC-CURR-001**: THE SYSTEM SHALL associate each credit account with a single account currency established at account creation time, using the store's configured currency for that customer/account context. *(Source: Architecture §3 "Balance model", §19 "Default currency behavior")*
- **QCC-CURR-002**: THE SYSTEM SHALL represent balance, total credited, total debited, and all transaction amounts using a decimal type with exactly two decimal places of precision (e.g., 14 total digits, 2 fractional digits). *(Source: Architecture §3 "Credit unit", §7.1, §7.2)*
- **QCC-CURR-003**: THE SYSTEM SHALL compare monetary amounts (e.g., redemption amount vs. available balance) using exact decimal comparison at two-decimal precision, without floating-point approximation. *(Source: Architecture §3 "Credit unit"; general monetary-precision best practice required for testable equality/inequality checks)*
- **QCC-CURR-004**: IF a state-changing request specifies a currency that does not match the customer's account currency, THEN THE SYSTEM SHALL reject the request with a deterministic currency-mismatch error and SHALL NOT apply any balance change. *(Source: Architecture §19 "Default currency behavior — recommended single configured currency per account"; no conversion is specified)*
- **QCC-CURR-005**: THE SYSTEM SHALL NOT perform currency conversion between a transaction's stated currency and the account currency. *(Source: Architecture §26 "Future Extension Points — Multi-currency"; explicitly out of scope for this release)*
- **QCC-CURR-006**: THE SYSTEM SHALL support exactly one account currency per customer for the initial release; multi-currency accounts per customer are out of scope. *(Source: Architecture §3, §26)*

## Non-Functional / Constraint Notes

- Rounding behavior for any calculation that could produce more than two decimal places (there are none defined in the current SRS/Architecture, since denominations and amounts are always specified to two decimals) is not exercised in-scope; if a future calculation requires rounding, the rounding rule is undefined and is recorded as **AMB-002**.

## Acceptance Criteria

1. **Given** a customer with no prior Quick Consult Credit activity, **When** their balance is queried, **Then** the response indicates a balance of 0.00 without an error. *(Validates QCC-ACCOUNT-004)*
2. **Given** a customer's first qualifying purchase, **When** the purchase is posted, **Then** a credit account is created with the correct currency and the balance/total-credited reflect the posted amount. *(Validates QCC-ACCOUNT-003, QCC-ACCOUNT-008)*
3. **Given** any sequence of successful purchase, redeem, admin-add, and admin-remove operations, **When** the account state is reconciled against the ledger, **Then** the balance always equals the net sum of ledger movements and is never negative. *(Validates QCC-ACCOUNT-006, QCC-ACCOUNT-007)*
4. **Given** a customer account configured in USD, **When** a transaction request specifies a different currency, **Then** the request is rejected and the balance is unchanged. *(Validates QCC-CURR-004)*
5. **Given** two amounts that differ only beyond the second decimal place due to floating-point representation, **When** they are compared for a balance-sufficiency check, **Then** the comparison uses exact decimal semantics and produces a deterministic, correct result. *(Validates QCC-CURR-003)*

## Traceability

See [traceability.md](./traceability.md). Contributes `QCC-ACCOUNT-001`…`QCC-ACCOUNT-009` and `QCC-CURR-001`…`QCC-CURR-006`.

## Change History

| Version | Date | Change | Cause |
|---|---|---|---|
| 1.0.0 | 2026-09-12 | Initial specification created | Quick Consult Credit SRS v1.0 baseline (new capability) |
